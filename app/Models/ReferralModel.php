<?php

namespace App\Models;

use CodeIgniter\Model;

class ReferralModel extends Model
{
    protected $table            = 'referrals';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'student_id', 'referred_by', 'referred_to',
        'direction', 'source_consultation_id', 'source_appointment_id',
        'reason', 'priority', 'status', 'response_notes', 'responded_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'student_id'  => 'required|is_natural_no_zero',
        'referred_by' => 'required|is_natural_no_zero',
        'direction'   => 'required|in_list[clinic_to_counselling,counselling_to_clinic]',
        'reason'      => 'required|min_length[3]',
        'priority'    => 'required|in_list[routine,urgent,emergency]',
    ];

    /**
     * Create a clinic-to-counselling referral.
     */
    public function createClinicReferral(array $data): int|false
    {
        $data['direction'] = 'clinic_to_counselling';
        $data['status']    = 'pending';

        $this->insert($data);
        return $this->getInsertID() ?: false;
    }

    /**
     * Get pending referrals.
     */
    public function getPending(?string $direction = null): array
    {
        $builder = $this->select('referrals.*, students.student_number, u_student.first_name as student_first, u_student.last_name as student_last, u_referrer.first_name as referrer_first, u_referrer.last_name as referrer_last')
            ->join('students', 'students.id = referrals.student_id')
            ->join('users as u_student', 'u_student.id = students.user_id')
            ->join('users as u_referrer', 'u_referrer.id = referrals.referred_by')
            ->where('referrals.status', 'pending');

        if ($direction !== null) {
            $builder->where('referrals.direction', $direction);
        }

        return $builder->orderBy('referrals.created_at', 'DESC')->findAll();
    }

    /**
     * Respond to a referral (accept/decline).
     */
    public function respond(int $id, string $status, ?string $notes = null): bool
    {
        return $this->update($id, [
            'status'         => $status,
            'response_notes' => $notes,
            'responded_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get all referrals with filtering.
     */
    public function getFiltered(?string $status = null, ?string $direction = null, int $limit = 50): array
    {
        $builder = $this->select('referrals.*, students.student_number, u_student.first_name as student_first, u_student.last_name as student_last, u_referrer.first_name as referrer_first, u_referrer.last_name as referrer_last')
            ->join('students', 'students.id = referrals.student_id')
            ->join('users as u_student', 'u_student.id = students.user_id')
            ->join('users as u_referrer', 'u_referrer.id = referrals.referred_by');

        if ($status !== null) {
            $builder->where('referrals.status', $status);
        }

        if ($direction !== null) {
            $builder->where('referrals.direction', $direction);
        }

        return $builder->orderBy('referrals.created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
