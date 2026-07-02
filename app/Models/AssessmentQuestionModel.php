<?php

namespace App\Models;

use CodeIgniter\Model;

class AssessmentQuestionModel extends Model
{
    protected $table            = 'assessment_questions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'template_id', 'question_text', 'question_type',
        'options', 'order_index', 'is_required',
    ];

    protected $useTimestamps = false;

    /**
     * Get questions for a template, ordered.
     */
    public function getByTemplate(int $templateId): array
    {
        $questions = $this->where('template_id', $templateId)
            ->orderBy('order_index', 'ASC')
            ->findAll();

        // Decode JSON options
        foreach ($questions as &$q) {
            if (is_string($q['options'])) {
                $q['options'] = json_decode($q['options'], true);
            }
        }

        return $questions;
    }
}
