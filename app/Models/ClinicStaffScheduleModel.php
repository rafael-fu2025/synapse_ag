<?php

namespace App\Models;

use CodeIgniter\Model;

class ClinicStaffScheduleModel extends Model
{
    protected $table            = 'clinic_staff_schedules';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'user_id', 'day_of_week', 'shift_start', 'shift_end',
        'schedule_type', 'effective_from', 'effective_until',
        'is_active', 'notes',
    ];

    protected $useTimestamps = false;

    /**
     * Get today's on-duty staff.
     */
    public function getTodayStaff(): array
    {
        $dayOfWeek = (int) date('w'); // 0=Sun ... 6=Sat

        return $this->select('clinic_staff_schedules.*, users.first_name, users.last_name')
            ->join('users', 'users.id = clinic_staff_schedules.user_id')
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->findAll();
    }
}
