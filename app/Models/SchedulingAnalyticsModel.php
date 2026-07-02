<?php

namespace App\Models;

use CodeIgniter\Model;

class SchedulingAnalyticsModel extends Model
{
    protected $table            = 'scheduling_analytics';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'counsellor_id', 'day_of_week', 'time_slot', 'total_appointments',
        'total_no_shows', 'no_show_rate', 'avg_utilization', 'recommended_overbooking',
        'last_calculated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get counselling analytics for specific slots of a counsellor.
     */
    public function getForCounsellor(int $counsellorId): array
    {
        return $this->where('counsellor_id', $counsellorId)
            ->orderBy('day_of_week', 'ASC')
            ->orderBy('time_slot', 'ASC')
            ->findAll();
    }
}
