<?php

namespace App\Models;

use CodeIgniter\Model;

class VolunteerWorkloadScoreModel extends Model
{
    protected $table            = 'volunteer_workload_scores';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'user_id', 'period_start', 'period_end', 'total_activities_assigned',
        'total_hours_committed', 'total_hours_completed', 'attendance_rate',
        'workload_score', 'conflict_count', 'predicted_availability_score',
        'last_calculated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get workload details for a volunteer for a specific period.
     */
    public function getForUserPeriod(int $userId, string $start, string $end): ?array
    {
        return $this->where('user_id', $userId)
            ->where('period_start', $start)
            ->where('period_end', $end)
            ->first();
    }
}
