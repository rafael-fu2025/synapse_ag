<?php

namespace App\Models;

use CodeIgniter\Model;

class AiGeneratedSummaryModel extends Model
{
    protected $table            = 'ai_generated_summaries';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'report_id', 'module', 'period_start', 'period_end',
        'input_data', 'generated_summary', 'generation_method',
        'model_used', 'tokens_used', 'generated_by',
    ];

    protected $useTimestamps = false;

    /**
     * Get summaries by module.
     */
    public function getByModule(string $module, int $limit = 10): array
    {
        return $this->where('module', $module)
            ->orderBy('created_at', 'DESC')
            ->findAll($limit);
    }
}
