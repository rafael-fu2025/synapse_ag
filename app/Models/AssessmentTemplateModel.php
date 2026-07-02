<?php

namespace App\Models;

use CodeIgniter\Model;

class AssessmentTemplateModel extends Model
{
    protected $table            = 'assessment_templates';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'title', 'description', 'type', 'is_active', 'created_by',
    ];

    protected $useTimestamps = false;

    /**
     * Get all active screening templates.
     */
    public function getActive(?string $type = null): array
    {
        $builder = $this->where('is_active', true);

        if ($type) {
            $builder->where('type', $type);
        }

        return $builder->orderBy('title', 'ASC')->findAll();
    }

    /**
     * Get a template with its questions (ordered).
     */
    public function getWithQuestions(int $id): ?array
    {
        $template = $this->find($id);
        if ($template === null) return null;

        $questionModel = new AssessmentQuestionModel();
        $template['questions'] = $questionModel->getByTemplate($id);

        return $template;
    }
}
