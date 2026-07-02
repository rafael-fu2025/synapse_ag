<?php

namespace App\Models;

use CodeIgniter\Model;

class OutreachActivityModel extends Model
{
    protected $table            = 'outreach_activities';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'program_id', 'title', 'description', 'location',
        'activity_date', 'start_time', 'end_time',
        'max_volunteers', 'status',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'program_id'    => 'required|is_natural_no_zero',
        'title'         => 'required|min_length[3]',
        'activity_date' => 'required|valid_date',
        'start_time'    => 'required',
        'end_time'      => 'required',
    ];

    /**
     * Get activities for a program.
     */
    public function getByProgram(int $programId): array
    {
        return $this->where('program_id', $programId)
            ->orderBy('activity_date', 'ASC')
            ->orderBy('start_time', 'ASC')
            ->findAll();
    }

    /**
     * Get upcoming activities across all programs.
     */
    public function getUpcoming(int $limit = 10): array
    {
        return $this->select('outreach_activities.*, outreach_programs.name as program_name')
            ->join('outreach_programs', 'outreach_programs.id = outreach_activities.program_id')
            ->where('activity_date >=', date('Y-m-d'))
            ->whereIn('outreach_activities.status', ['upcoming', 'ongoing'])
            ->orderBy('activity_date', 'ASC')
            ->orderBy('start_time', 'ASC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get activity with full details (volunteers, attendance).
     */
    public function getWithDetails(int $id): ?array
    {
        $activity = $this->select('outreach_activities.*, outreach_programs.name as program_name')
            ->join('outreach_programs', 'outreach_programs.id = outreach_activities.program_id')
            ->find($id);

        if ($activity === null) return null;

        // Load volunteers
        $volunteerModel = new VolunteerAssignmentModel();
        $activity['volunteers'] = $volunteerModel->getByActivity($id);
        $activity['volunteer_count'] = count($activity['volunteers']);
        $activity['confirmed_count'] = count(array_filter($activity['volunteers'], fn($v) => $v['status'] === 'confirmed'));

        // Load attendance
        $attendanceModel = new OutreachAttendanceModel();
        $activity['attendance'] = $attendanceModel->getByActivity($id);

        return $activity;
    }
}
