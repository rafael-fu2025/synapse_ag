<?php

namespace App\Models;

use CodeIgniter\Model;

class AiTriagePredictionModel extends Model
{
    protected $table            = 'ai_triage_predictions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'consultation_id', 'student_id', 'input_text',
        'predicted_priority', 'confidence_score', 'model_version',
        'features_used', 'staff_decision', 'staff_priority',
        'decided_by', 'decided_at',
    ];

    protected $useTimestamps = false; // database handles created_at natively

    /**
     * Log a triage prediction.
     */
    public function logPrediction(
        int $consultationId,
        int $studentId,
        string $inputText,
        string $predictedPriority,
        float $confidenceScore,
        string $modelVersion,
        ?array $features = null
    ): int {
        return $this->insert([
            'consultation_id'    => $consultationId,
            'student_id'         => $studentId,
            'input_text'         => $inputText,
            'predicted_priority' => $predictedPriority,
            'confidence_score'   => $confidenceScore,
            'model_version'      => $modelVersion,
            'features_used'      => $features ? json_encode($features) : null,
        ]);
    }

    /**
     * Record a staff override or acceptance.
     */
    public function recordStaffDecision(int $id, string $decision, ?string $priority = null, ?int $userId = null): bool
    {
        return $this->update($id, [
            'staff_decision' => $decision,
            'staff_priority' => $decision === 'overridden' ? $priority : null,
            'decided_by'     => $userId,
            'decided_at'     => date('Y-m-d H:i:s'),
        ]);
    }
}
