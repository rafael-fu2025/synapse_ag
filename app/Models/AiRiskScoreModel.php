<?php

namespace App\Models;

use CodeIgniter\Model;

class AiRiskScoreModel extends Model
{
    protected $table            = 'ai_risk_scores';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'student_id', 'assessment_response_id', 'score_type', 'risk_level',
        'current_score', 'trend_slope', 'trend_direction', 'anomaly_detected',
        'anomaly_magnitude', 'data_points_used', 'prediction_window_days',
        'projected_score', 'model_version', 'counsellor_notified', 'notified_at',
    ];

    protected $useTimestamps = false;

    /**
     * Get latest risk score details for a student.
     */
    public function getLatestForStudent(int $studentId): ?array
    {
        return $this->where('student_id', $studentId)
            ->orderBy('created_at', 'DESC')
            ->first();
    }
}
