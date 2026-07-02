<?php

namespace App\Models;

use CodeIgniter\Model;

class AssessmentResponseModel extends Model
{
    protected $table            = 'assessment_responses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'template_id', 'student_id', 'appointment_id',
        'responses', 'total_score',
    ];

    protected $useTimestamps = false;

    /**
     * Submit a screening response with auto-calculated score.
     */
    public function submit(array $data): int|false
    {
        // Ensure responses is JSON encoded
        if (is_array($data['responses'])) {
            $data['responses'] = json_encode($data['responses']);
        }

        $this->insert($data);
        return $this->getInsertID() ?: false;
    }

    /**
     * Get responses for a student (most recent first).
     */
    public function getByStudent(int $studentId, int $limit = 10): array
    {
        $responses = $this->select('assessment_responses.*, assessment_templates.title as template_title, assessment_templates.type as template_type')
            ->join('assessment_templates', 'assessment_templates.id = assessment_responses.template_id')
            ->where('student_id', $studentId)
            ->orderBy('submitted_at', 'DESC')
            ->limit($limit)
            ->findAll();

        foreach ($responses as &$r) {
            if (is_string($r['responses'])) {
                $r['responses'] = json_decode($r['responses'], true);
            }
        }

        return $responses;
    }

    /**
     * Get score history for a specific template+student (for trend charts).
     */
    public function getScoreHistory(int $studentId, int $templateId): array
    {
        return $this->select('total_score, submitted_at')
            ->where('student_id', $studentId)
            ->where('template_id', $templateId)
            ->orderBy('submitted_at', 'ASC')
            ->findAll();
    }

    /**
     * Get a response with template + questions for results display.
     */
    public function getWithTemplate(int $id): ?array
    {
        $response = $this->select('assessment_responses.*, assessment_templates.title as template_title, assessment_templates.type as template_type, assessment_templates.description as template_description')
            ->join('assessment_templates', 'assessment_templates.id = assessment_responses.template_id')
            ->find($id);

        if ($response === null) return null;

        if (is_string($response['responses'])) {
            $response['responses'] = json_decode($response['responses'], true);
        }

        // Load questions for display
        $questionModel = new AssessmentQuestionModel();
        $response['questions'] = $questionModel->getByTemplate((int) $response['template_id']);

        // Load student info
        $studentModel = new StudentModel();
        $response['student'] = $studentModel->getWithProfile((int) $response['student_id']);

        return $response;
    }

    /**
     * Calculate total score from Likert responses.
     */
    public static function calculateTotalScore(array $responses): int
    {
        $total = 0;
        foreach ($responses as $answer) {
            if (is_numeric($answer)) {
                $total += (int) $answer;
            }
        }
        return $total;
    }

    /**
     * Get severity interpretation for PHQ-9.
     */
    public static function getPHQ9Severity(int $score): array
    {
        if ($score <= 4)  return ['level' => 'minimal',           'color' => '#10B981', 'label' => 'Minimal (0-4)'];
        if ($score <= 9)  return ['level' => 'mild',              'color' => '#84CC16', 'label' => 'Mild (5-9)'];
        if ($score <= 14) return ['level' => 'moderate',          'color' => '#F59E0B', 'label' => 'Moderate (10-14)'];
        if ($score <= 19) return ['level' => 'moderately_severe', 'color' => '#EF4444', 'label' => 'Moderately Severe (15-19)'];
        return                   ['level' => 'severe',            'color' => '#DC2626', 'label' => 'Severe (20-27)'];
    }

    /**
     * Get severity interpretation for GAD-7.
     */
    public static function getGAD7Severity(int $score): array
    {
        if ($score <= 4)  return ['level' => 'minimal',  'color' => '#10B981', 'label' => 'Minimal (0-4)'];
        if ($score <= 9)  return ['level' => 'mild',     'color' => '#84CC16', 'label' => 'Mild (5-9)'];
        if ($score <= 14) return ['level' => 'moderate', 'color' => '#F59E0B', 'label' => 'Moderate (10-14)'];
        return                   ['level' => 'severe',   'color' => '#DC2626', 'label' => 'Severe (15-21)'];
    }
}
