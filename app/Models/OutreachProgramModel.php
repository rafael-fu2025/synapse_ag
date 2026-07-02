<?php

namespace App\Models;

use CodeIgniter\Model;

class OutreachProgramModel extends Model
{
    protected $table            = 'outreach_programs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'name', 'description', 'coordinator_id',
        'start_date', 'end_date', 'status',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'name'           => 'required|min_length[3]',
        'coordinator_id' => 'required|is_natural_no_zero',
    ];

    /**
     * Get all programs with activity counts and volunteer hours.
     */
    public function getAllWithStats(): array
    {
        $programs = $this->select('outreach_programs.*, users.first_name as coord_first, users.last_name as coord_last')
            ->join('users', 'users.id = outreach_programs.coordinator_id')
            ->orderBy('FIELD(status, "active", "planning", "completed", "cancelled")')
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $activityModel = new OutreachActivityModel();
        $attendanceModel = new OutreachAttendanceModel();

        foreach ($programs as &$p) {
            $p['activity_count'] = $activityModel->where('program_id', $p['id'])->countAllResults(false);
            $p['completed_count'] = $activityModel->where('program_id', $p['id'])->where('status', 'completed')->countAllResults(false);
            $p['total_hours'] = $attendanceModel->getTotalHoursForProgram((int) $p['id']);
        }

        return $programs;
    }

    /**
     * Get a program with full details.
     */
    public function getWithDetails(int $id): ?array
    {
        $program = $this->select('outreach_programs.*, users.first_name as coord_first, users.last_name as coord_last')
            ->join('users', 'users.id = outreach_programs.coordinator_id')
            ->find($id);

        if ($program === null) return null;

        $activityModel = new OutreachActivityModel();
        $program['activities'] = $activityModel->getByProgram($id);
        $program['activity_count'] = count($program['activities']);
        $program['completed_count'] = count(array_filter($program['activities'], fn($a) => $a['status'] === 'completed'));

        $attendanceModel = new OutreachAttendanceModel();
        $program['total_hours'] = $attendanceModel->getTotalHoursForProgram($id);

        return $program;
    }

    /**
     * Get dashboard stats.
     */
    public function getDashboardStats(): array
    {
        $active    = $this->where('status', 'active')->countAllResults(false);
        $planning  = $this->where('status', 'planning')->countAllResults(false);
        $completed = $this->where('status', 'completed')->countAllResults(false);

        $activityModel = new OutreachActivityModel();
        $upcoming = $activityModel->where('status', 'upcoming')->where('activity_date >=', date('Y-m-d'))->countAllResults(false);

        return compact('active', 'planning', 'completed', 'upcoming');
    }
}
