<?php

namespace App\Models;

use CodeIgniter\Model;

class CounsellingAppointmentModel extends Model
{
    protected $table            = 'counselling_appointments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'student_id', 'counsellor_id', 'appointment_date', 'start_time',
        'end_time', 'type', 'status', 'reason', 'session_notes',
        'cancellation_reason', 'no_show_probability',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'student_id'       => 'required|is_natural_no_zero',
        'counsellor_id'    => 'required|is_natural_no_zero',
        'appointment_date' => 'required|valid_date',
        'start_time'       => 'required',
    ];

    /**
     * Get today's schedule for a counsellor.
     */
    public function getTodaySchedule(int $counsellorId): array
    {
        $today = date('Y-m-d');

        return $this->select('counselling_appointments.*, students.student_number, users.first_name as student_first, users.last_name as student_last')
            ->join('students', 'students.id = counselling_appointments.student_id')
            ->join('users', 'users.id = students.user_id')
            ->where('counsellor_id', $counsellorId)
            ->where('appointment_date', $today)
            ->orderBy('start_time', 'ASC')
            ->findAll();
    }

    /**
     * Get all today's appointments (admin/head counsellor view).
     */
    public function getAllToday(): array
    {
        $today = date('Y-m-d');

        return $this->select('counselling_appointments.*, students.student_number, u_student.first_name as student_first, u_student.last_name as student_last, u_counsellor.first_name as counsellor_first, u_counsellor.last_name as counsellor_last')
            ->join('students', 'students.id = counselling_appointments.student_id')
            ->join('users as u_student', 'u_student.id = students.user_id')
            ->join('users as u_counsellor', 'u_counsellor.id = counselling_appointments.counsellor_id')
            ->where('appointment_date', $today)
            ->orderBy('start_time', 'ASC')
            ->findAll();
    }

    /**
     * Get appointments for a specific student.
     */
    public function getByStudent(int $studentId, int $limit = 20): array
    {
        return $this->select('counselling_appointments.*, users.first_name as counsellor_first, users.last_name as counsellor_last')
            ->join('users', 'users.id = counselling_appointments.counsellor_id')
            ->where('student_id', $studentId)
            ->orderBy('appointment_date', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get full appointment detail with student + counsellor info.
     */
    public function getFullAppointment(int $id): ?array
    {
        $appt = $this->select('counselling_appointments.*, students.student_number, students.blood_type, u_student.first_name as student_first, u_student.last_name as student_last, u_student.email as student_email, u_counsellor.first_name as counsellor_first, u_counsellor.last_name as counsellor_last')
            ->join('students', 'students.id = counselling_appointments.student_id')
            ->join('users as u_student', 'u_student.id = students.user_id')
            ->join('users as u_counsellor', 'u_counsellor.id = counselling_appointments.counsellor_id')
            ->find($id);

        if ($appt === null) return null;

        // Load screening history
        $responseModel = new AssessmentResponseModel();
        $appt['screening_history'] = $responseModel->getByStudent((int) $appt['student_id'], 5);

        // Load referrals for this student
        $referralModel = new ReferralModel();
        $appt['referrals'] = $referralModel->where('student_id', $appt['student_id'])
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->findAll();

        return $appt;
    }

    /**
     * Get today's stats for a counsellor.
     */
    public function getTodayStats(?int $counsellorId = null): array
    {
        $today = date('Y-m-d');
        $builder = $this->where('appointment_date', $today);

        if ($counsellorId) {
            $builder->where('counsellor_id', $counsellorId);
        }

        $total     = (clone $builder)->countAllResults(false);
        $scheduled = (clone $builder)->where('status', 'scheduled')->countAllResults(false);
        $confirmed = (clone $builder)->where('status', 'confirmed')->countAllResults(false);
        $completed = (clone $builder)->where('status', 'completed')->countAllResults(false);
        $noShow    = (clone $builder)->where('status', 'no_show')->countAllResults(false);

        return compact('total', 'scheduled', 'confirmed', 'completed', 'noShow');
    }

    /**
     * Book an appointment.
     */
    public function book(array $data): int|false
    {
        $data['status'] = 'scheduled';
        $this->insert($data);
        return $this->getInsertID() ?: false;
    }

    /**
     * Mark appointment as no-show + increment student counter + 3-strike check.
     */
    public function markNoShow(int $id): array
    {
        $appt = $this->find($id);
        if ($appt === null) return ['success' => false];

        $this->update($id, ['status' => 'no_show']);

        // Increment student no-show counter
        $studentModel = new StudentModel();
        $student = $studentModel->find($appt['student_id']);
        $newCount = ((int) ($student['consecutive_no_shows'] ?? 0)) + 1;
        $studentModel->update($appt['student_id'], ['consecutive_no_shows' => $newCount]);

        // 3-strike welfare alert
        $welfareAlert = false;
        if ($newCount >= 3) {
            $notifModel = new NotificationModel();
            $notifModel->createNotification(
                null,
                'welfare_alert',
                'Three-Strike No-Show Alert',
                "Student #{$student['student_number']} has {$newCount} consecutive no-shows. Welfare follow-up recommended.",
                'counselling',
                'students',
                (int) $appt['student_id']
            );
            $welfareAlert = true;
        }

        return ['success' => true, 'no_show_count' => $newCount, 'welfare_alert' => $welfareAlert];
    }

    /**
     * Get appointments for a date range (calendar view).
     */
    public function getForDateRange(string $startDate, string $endDate, ?int $counsellorId = null): array
    {
        $builder = $this->select('counselling_appointments.*, students.student_number, u_student.first_name as student_first, u_student.last_name as student_last')
            ->join('students', 'students.id = counselling_appointments.student_id')
            ->join('users as u_student', 'u_student.id = students.user_id')
            ->where('appointment_date >=', $startDate)
            ->where('appointment_date <=', $endDate);

        if ($counsellorId) {
            $builder->where('counsellor_id', $counsellorId);
        }

        return $builder->orderBy('appointment_date', 'ASC')
            ->orderBy('start_time', 'ASC')
            ->findAll();
    }
}
