<?php

namespace App\Models;

use CodeIgniter\Model;

class EmergencyContactModel extends Model
{
    protected $table            = 'emergency_contacts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'student_id', 'contact_name', 'relationship', 'phone', 'is_primary',
    ];

    protected $useTimestamps = false;

    protected $validationRules = [
        'student_id'   => 'required|is_natural_no_zero',
        'contact_name' => 'required|max_length[150]',
        'relationship' => 'required|max_length[50]',
        'phone'        => 'required|max_length[20]',
    ];

    /**
     * Get all emergency contacts for a student.
     */
    public function getByStudent(int $studentId): array
    {
        return $this->where('student_id', $studentId)
            ->orderBy('is_primary', 'DESC')
            ->findAll();
    }

    /**
     * Get the primary emergency contact for a student.
     */
    public function getPrimary(int $studentId): ?array
    {
        return $this->where('student_id', $studentId)
            ->where('is_primary', true)
            ->first();
    }
}
