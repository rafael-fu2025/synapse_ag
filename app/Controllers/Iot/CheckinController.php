<?php

namespace App\Controllers\Iot;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\CounsellingAppointmentModel;
use App\Models\ConsultationModel;
use App\Models\OfflineCheckinBufferModel;
use App\Models\AuditLogModel;
use App\Models\NotificationModel;

class CheckinController extends BaseController
{
    protected StudentModel $studentModel;
    protected OfflineCheckinBufferModel $bufferModel;

    public function __construct()
    {
        $this->studentModel = new StudentModel();
        $this->bufferModel = new OfflineCheckinBufferModel();
    }

    /**
     * Display Check-In Kiosk.
     */
    public function index()
    {
        return view('iot/kiosk', [
            'title' => 'Self-Service Kiosk — SYNAPSE',
        ]);
    }

    /**
     * Process Scan.
     */
    public function scan()
    {
        $identifier = $this->request->getPost('student_identifier');
        $method     = $this->request->getPost('scan_method') ?: 'qr';
        $stationId  = $this->request->getPost('station_id') ?: 'Kiosk-01';
        $isOffline  = $this->request->getPost('is_offline') === 'true' || $this->request->getPost('is_offline') === true;
        $scannedAt  = $this->request->getPost('scanned_at') ?: date('Y-m-d H:i:s');

        if (empty($identifier)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Student identifier is required.',
            ])->setStatusCode(400);
        }

        // 1. Resolve student
        $student = $this->studentModel->checkInLookup($identifier, $method);

        if (! $student) {
            if ($isOffline) {
                $this->bufferModel->saveScan($identifier, $method, $stationId, $scannedAt);
                return $this->response->setJSON([
                    'success' => true,
                    'offline' => true,
                    'message' => 'Scan buffered offline (unknown student).',
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Student not found / unregistered ID.',
            ])->setStatusCode(404);
        }

        if ($isOffline) {
            $this->bufferModel->saveScan($identifier, $method, $stationId, $scannedAt);
            return $this->response->setJSON([
                'success' => true,
                'offline' => true,
                'student' => [
                    'name' => $student['full_name'],
                    'number' => $student['student_number'],
                ],
                'message' => 'Check-in buffered offline.',
            ]);
        }

        $result = $this->dispatchCheckin($student, $method, $stationId, $scannedAt);

        return $this->response->setJSON($result);
    }

    /**
     * Dispatch logic for online check-in.
     */
    private function dispatchCheckin(array $student, string $method, ?string $stationId, string $scannedAt): array
    {
        $db = \Config\Database::connect();
        $studentId = (int) $student['id'];
        $userId = (int) $student['user_id'];
        $scanDate = date('Y-m-d', strtotime($scannedAt));

        // 1. Check for Counselling Appointment TODAY (scheduled/confirmed)
        $apptModel = new CounsellingAppointmentModel();
        $appt = $apptModel->where('student_id', $studentId)
            ->where('appointment_date', $scanDate)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->first();

        if ($appt) {
            if ($appt['status'] === 'scheduled') {
                $apptModel->update($appt['id'], ['status' => 'confirmed']);

                $auditModel = new AuditLogModel();
                $auditModel->logAction($userId, 'check_in', 'counselling', 'counselling_appointments', (int) $appt['id']);

                return [
                    'success'     => true,
                    'type'        => 'counselling',
                    'destination' => 'Counselling Appointment',
                    'title'       => 'Counselling Check-In',
                    'message'     => "Confirmed booking at {$appt['start_time']} - {$appt['end_time']}.",
                    'student'     => [
                        'name'           => $student['full_name'],
                        'number'         => $student['student_number'],
                        'course'         => $student['course'],
                        'year_level'     => $student['year_level'],
                        'avatar'         => $student['avatar_url'],
                        'allergy_alert'  => $this->hasSevereAllergy($student),
                    ],
                ];
            } else {
                return [
                    'success'     => true,
                    'type'        => 'counselling_already',
                    'destination' => 'Counselling Appointment',
                    'title'       => 'Counselling Check-In',
                    'message'     => "Counselling appointment already checked in.",
                    'student'     => [
                        'name'           => $student['full_name'],
                        'number'         => $student['student_number'],
                        'avatar'         => $student['avatar_url'],
                    ],
                ];
            }
        }

        // 2. Fallback: Clinic Consultation
        $consultModel = new ConsultationModel();

        $attendingUser = $db->table('user_roles')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->whereIn('roles.name', ['clinic_staff', 'admin'])
            ->select('user_roles.user_id')
            ->get()->getRow();

        $attendingUserId = $attendingUser ? (int) $attendingUser->user_id : 1;

        $consultModel->insert([
            'student_id'        => $studentId,
            'attending_user_id' => $attendingUserId,
            'chief_complaint'   => "Self-Service Kiosk Check-In (Pending Triage) - Station: {$stationId}",
            'status'            => 'in_progress',
            'check_in_method'   => $method,
            'consultation_date' => $scannedAt,
        ]);

        $consultId = $consultModel->getInsertID();

        $auditModel = new AuditLogModel();
        $auditModel->logAction($userId, 'check_in', 'clinic', 'consultations', $consultId);

        return [
            'success'     => true,
            'type'        => 'clinic',
            'destination' => 'Clinic Consultation',
            'title'       => 'Clinic Check-In',
            'message'     => 'Added to clinic consultation queue.',
            'student'     => [
                'name'           => $student['full_name'],
                'number'         => $student['student_number'],
                'course'         => $student['course'],
                'year_level'     => $student['year_level'],
                'avatar'         => $student['avatar_url'],
                'allergy_alert'  => $this->hasSevereAllergy($student),
            ],
        ];
    }

    /**
     * Check if student has a severe allergy.
     */
    private function hasSevereAllergy(array $student): ?string
    {
        if (empty($student['allergies'])) return null;
        foreach ($student['allergies'] as $a) {
            if ($a['severity'] === 'severe') {
                return "⚠️ SEVERE Allergy Alert: {$a['allergen']}!";
            }
        }
        return null;
    }

    /**
     * Background Sync pending scans.
     */
    public function sync()
    {
        $pending = $this->bufferModel->getPending();
        $syncedCount = 0;
        $failedCount = 0;
        $duplicateCount = 0;

        foreach ($pending as $scan) {
            $isDuplicate = $this->checkDuplicateSync($scan);
            if ($isDuplicate) {
                $this->bufferModel->markDuplicate((int) $scan['id']);
                $duplicateCount++;
                continue;
            }

            $student = $this->studentModel->checkInLookup($scan['student_identifier'], $scan['scan_method']);
            if (! $student) {
                $this->bufferModel->markFailed((int) $scan['id'], 'Student record not found during synchronization.');
                $failedCount++;
                continue;
            }

            $result = $this->dispatchCheckin($student, $scan['scan_method'], $scan['station_id'], $scan['scanned_at']);

            if ($result['success']) {
                $this->bufferModel->markSynced((int) $scan['id']);
                $syncedCount++;
            } else {
                $this->bufferModel->markFailed((int) $scan['id'], $result['message'] ?? 'Failed during dispatch processing.');
                $failedCount++;
            }
        }

        return $this->response->setJSON([
            'success'    => true,
            'synced'     => $syncedCount,
            'failed'     => $failedCount,
            'duplicates' => $duplicateCount,
            'message'    => "Sync complete. Synced: {$syncedCount}, Failed: {$failedCount}, Duplicates: {$duplicateCount}.",
        ]);
    }

    /**
     * Check duplicate scans.
     */
    private function checkDuplicateSync(array $scan): bool
    {
        $scannedTime = strtotime($scan['scanned_at']);
        $fiveMinutesAgo = date('Y-m-d H:i:s', $scannedTime - 300);
        $fiveMinutesLater = date('Y-m-d H:i:s', $scannedTime + 300);

        $existingBuffer = $this->bufferModel
            ->where('student_identifier', $scan['student_identifier'])
            ->where('sync_status', 'synced')
            ->where('scanned_at >=', $fiveMinutesAgo)
            ->where('scanned_at <=', $fiveMinutesLater)
            ->where('id !=', $scan['id'])
            ->first();

        if ($existingBuffer) return true;

        $student = $this->studentModel->checkInLookup($scan['student_identifier'], $scan['scan_method']);
        if ($student) {
            $consultModel = new ConsultationModel();
            $existingConsult = $consultModel
                ->where('student_id', $student['id'])
                ->where('consultation_date >=', $fiveMinutesAgo)
                ->where('consultation_date <=', $fiveMinutesLater)
                ->first();

            if ($existingConsult) return true;
        }

        return false;
    }
}
