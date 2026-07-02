<?php

namespace App\Models;

use CodeIgniter\Model;

class AllergyModel extends Model
{
    protected $table            = 'allergies';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'student_id', 'allergen', 'severity', 'reaction', 'noted_at',
    ];

    protected $useTimestamps = false;

    protected $validationRules = [
        'student_id' => 'required|is_natural_no_zero',
        'allergen'   => 'required|max_length[200]',
        'severity'   => 'required|in_list[mild,moderate,severe]',
    ];

    /**
     * Get all allergies for a student.
     */
    public function getByStudent(int $studentId): array
    {
        return $this->where('student_id', $studentId)
            ->orderBy('severity', 'DESC') // severe first
            ->findAll();
    }

    /**
     * Check if a student has any severe allergies.
     */
    public function hasSevere(int $studentId): bool
    {
        return $this->where('student_id', $studentId)
            ->where('severity', 'severe')
            ->countAllResults() > 0;
    }
}
