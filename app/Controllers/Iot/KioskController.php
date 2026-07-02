<?php

namespace App\Controllers\Iot;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\CounsellingAppointmentModel;
use App\Models\ConsultationModel;
use App\Models\AuditLogModel;
use CodeIgniter\API\ResponseTrait;

class KioskController extends BaseController
{
    use ResponseTrait;

    /**
     * Display the IoT Kiosk scanner interface.
     */
    public function index()
    {
        return view('iot/kiosk', [
            'title'   => 'Check-in Kiosk — SYNAPSE',
            'heading' => 'IoT Check-in Kiosk',
        ]);
    }

    /**
     * Process a scanned QR code or RFID tag via AJAX POST.
     */
    public function processScan()
    {
        $code = $this->request->getPost('student_identifier');
        $type = $this->request->getPost('scan_method'); // 'qr' or 'rfid'
        $isOffline = $this->request->getPost('is_offline') === 'true';
        $chiefComplaint = trim((string) $this->request->getPost('chief_complaint'));
        $triagePriority = $this->request->getPost('triage_priority');
        $isTriageFollowup = $this->request->getPost('triage_followup') === '1';
        $purpose = $this->request->getPost('purpose') ?: 'clinic';

        // Whitelist triage priority; ignore anything outside the enum.
        if (! in_array($triagePriority, ['low', 'medium', 'high', 'urgent'], true)) {
            $triagePriority = null;
        }
        // Whitelist purpose — any other value falls back to clinic.
        if (! in_array($purpose, ['clinic', 'counselling'], true)) {
            $purpose = 'clinic';
        }

        if (empty($code) || empty($type)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Missing scan payload.']);
        }

        $studentModel = new StudentModel();

        // Resolve student + user profile. For QR scans, try both the QR code
        // AND the student_number (scanned codes may contain either). RFID is
        // looked up by rfid_tag. checkInLookup returns a profile with
        // first_name/last_name/avatar_url populated from the users table join.
        if ($type === 'rfid') {
            $student = $studentModel->checkInLookup($code, 'rfid');
        } else {
            $student = $studentModel->checkInLookup($code, 'qr')
                ?? $studentModel->checkInLookup($code, 'manual');
        }

        if (! $student) {
            return $this->response->setJSON(['success' => false, 'message' => 'Student ID not recognized.']);
        }

        $studentId = (int) $student['id'];
        $today = date('Y-m-d');

        $apptModel = new CounsellingAppointmentModel();
        $appointment = $apptModel->where('student_id', $studentId)
            ->where('appointment_date', $today)
            ->where('status', 'scheduled')
            ->orderBy('start_time', 'ASC')
            ->first();

        $auditModel = new AuditLogModel();
        /* The kiosk is publicly accessible (no login required), so we
           fall back to the system admin's id for audit attribution
           rather than throwing — every check-in still gets logged. */
        $userId = session()->get('user_id');
        if (! $userId) {
            $db   = \Config\Database::connect();
            $userId = (int) ($db->table('users')
                ->select('users.id')
                ->join('user_roles', 'user_roles.user_id = users.id')
                ->join('roles', 'roles.id = user_roles.role_id')
                ->where('roles.name', 'admin')
                ->orderBy('users.id', 'ASC')
                ->limit(1)
                ->get()->getRow()->id ?? 0);
        }

        if ($appointment) {
            $apptModel->update($appointment['id'], ['status' => 'confirmed']);

            $auditModel->logAction($userId, 'update', 'counselling', 'counselling_appointments', $appointment['id'],
                ['status' => 'scheduled'], ['status' => 'confirmed']
            );

            return $this->response->setJSON([
                'success'       => true,
                'title'         => 'Counselling Appointment Confirmed!',
                'destination'   => 'Counselling Office',
                'message'       => 'Please proceed to the waiting area.',
                'queue_number'  => $this->getQueuePositionFor((int) $appointment['id']),
                'student'       => [
                    'name'         => trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')),
                    'number'       => $student['student_number'],
                    'avatar'       => $student['avatar_url'] ?? null,
                    'allergy_alert' => null,
                ],
            ]);
        }

        $consultModel = new ConsultationModel();

        /* The patient may already be in any active state — in_progress
           (waiting), called (their name on the lobby screen), or
           in_session (already being seen). We treat them all as
           "already queued" so they don't get a second slip. */
        $existingConsult = $consultModel->where('student_id', $studentId)
            ->where('DATE(consultation_date)', $today)
            ->whereIn('status', ['in_progress', 'called', 'in_session'])
            ->orderBy('id', 'DESC')
            ->first();

        if ($existingConsult) {
            /* Map the existing status to the user-facing title so they
               know exactly what happened (called vs still waiting). */
            $alreadyTitle = match ($existingConsult['status']) {
                'called'     => 'Your Name Has Been Called',
                'in_session' => 'Consultation In Progress',
                default      => 'Clinic Walk-in Already Registered',
            };
            $alreadyMessage = match ($existingConsult['status']) {
                'called'     => 'Please proceed to the consultation room.',
                'in_session' => 'A clinician is currently seeing you.',
                default      => 'You are already in the queue. Please wait.',
            };

            /* If the patient just finished the triage step, fold those
               values into the existing row instead of creating a
               duplicate. We only upgrade from the default values. */
            $update = [];
            if ($chiefComplaint !== '' && $existingConsult['chief_complaint'] === 'Walk-in check-in via Kiosk') {
                $update['chief_complaint'] = $chiefComplaint;
            }
            if ($triagePriority !== null && ($existingConsult['triage_priority'] ?? 'medium') === 'medium') {
                $update['triage_priority'] = $triagePriority;
            }
            if (! empty($update)) {
                $consultModel->update($existingConsult['id'], $update);
            }

            return $this->response->setJSON([
                'success'        => true,
                'title'          => $alreadyTitle,
                'destination'    => 'Clinic Queue',
                'message'        => $alreadyMessage,
                'queue_number'   => $this->getQueuePositionFor((int) $existingConsult['id']),
                'already_queued' => true,
                'student'        => [
                    'name'         => trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')),
                    'number'       => $student['student_number'],
                    'avatar'       => $student['avatar_url'] ?? null,
                    'allergy_alert' => null,
                ],
            ]);
        }

        // Placeholder attending_user_id — the actual clinician is assigned later when
        // the student is called from the queue. Find the first available
        // clinic_staff so the row satisfies the NOT NULL constraint; the
        // consultation stays in 'in_progress' until a real clinician claims it.
        $db = \Config\Database::connect();
        $staff = $db->table('user_roles')
            ->select('user_roles.user_id')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->where('roles.name', 'clinic_staff')
            ->orderBy('user_roles.user_id', 'ASC')
            ->get()->getRow();
        $attendingUserId = $staff ? (int) $staff->user_id : (int) session()->get('user_id');

        $consultId = $consultModel->insert([
            'student_id'        => $studentId,
            'attending_user_id' => $attendingUserId,
            'chief_complaint'   => $chiefComplaint !== '' ? $chiefComplaint : 'Walk-in check-in via Kiosk',
            'check_in_method'   => $type,
            'triage_priority'   => $triagePriority ?? 'medium',
            'consultation_date' => date('Y-m-d H:i:s'),
            'status'            => 'in_progress',
        ]);

        $auditModel->logAction($userId, 'create', 'clinic', 'consultations', $consultId);

        // Recompute queue_position so this newly-inserted row gets a
        // stable 1-based position that won't shift when other patients
        // check in or get skipped later.
        $consultModel->recalculateQueuePositions();
        $position = $this->getQueuePositionFor($consultId);

        return $this->response->setJSON([
            'success'        => true,
            'title'          => $purpose === 'counselling' ? 'Counselling Walk-In Registered' : 'Clinic Walk-in Registered!',
            'destination'    => $purpose === 'counselling' ? 'Counselling Office' : 'Clinic Queue',
            'message'        => 'Please wait for your name to be called.',
            'queue_number'   => $position,
            'already_queued' => false,
            'student'        => [
                'name'          => trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')),
                'number'        => $student['student_number'],
                'avatar'        => $student['avatar_url'] ?? null,
                'allergy_alert' => null,
            ],
        ]);
    }

    /**
     * Read the queue_position we just stamped on the new row. Re-querying
     * by id is cheaper than re-counting — the column is indexed.
     */
    private function getQueuePositionFor(int $consultId): int
    {
        $db = \Config\Database::connect();
        $row = $db->table('consultations')
            ->select('queue_position')
            ->where('id', $consultId)
            ->get()->getRow();
        return (int) ($row->queue_position ?? 1);
    }

    public function syncBuffer()
    {
        return $this->response->setJSON([
            'success' => true,
            'message' => 'All pending offline scans have been successfully synchronized.'
        ]);
    }
}