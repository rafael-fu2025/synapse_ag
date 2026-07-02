<?php

namespace App\Models;

use CodeIgniter\Model;

class ConsultationVitalsModel extends Model
{
    protected $table            = 'consultation_vitals';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'consultation_id', 'temperature', 'blood_pressure_sys',
        'blood_pressure_dia', 'heart_rate', 'respiratory_rate',
        'weight_kg', 'height_cm',
    ];

    protected $useTimestamps = false;

    /**
     * Get vitals for a specific consultation.
     */
    public function getByConsultation(int $consultationId): ?array
    {
        return $this->where('consultation_id', $consultationId)->first();
    }

    /**
     * Get vitals history for a student (via consultations join).
     */
    public function getStudentVitalsHistory(int $studentId, int $limit = 10): array
    {
        return $this->select('consultation_vitals.*, consultations.consultation_date')
            ->join('consultations', 'consultations.id = consultation_vitals.consultation_id')
            ->where('consultations.student_id', $studentId)
            ->orderBy('consultations.consultation_date', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
