<?php

namespace App\Models;

use CodeIgniter\Model;

class VolunteerAssignmentModel extends Model
{
    protected $table            = 'volunteer_assignments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'activity_id', 'user_id', 'assigned_role',
        'status', 'conflict_reason', 'assigned_by',
    ];

    protected $useTimestamps = false;

    /**
     * Get volunteers for an activity.
     */
    public function getByActivity(int $activityId): array
    {
        return $this->select('volunteer_assignments.*, users.first_name, users.last_name, users.email')
            ->join('users', 'users.id = volunteer_assignments.user_id')
            ->where('activity_id', $activityId)
            ->orderBy('volunteer_assignments.status', 'ASC')
            ->findAll();
    }

    /**
     * Get assignments for a user.
     */
    public function getByUser(int $userId, int $limit = 20): array
    {
        return $this->select('volunteer_assignments.*, outreach_activities.title as activity_title, outreach_activities.activity_date, outreach_activities.start_time, outreach_activities.end_time, outreach_activities.location, outreach_programs.name as program_name')
            ->join('outreach_activities', 'outreach_activities.id = volunteer_assignments.activity_id')
            ->join('outreach_programs', 'outreach_programs.id = outreach_activities.program_id')
            ->where('user_id', $userId)
            ->orderBy('outreach_activities.activity_date', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Assign a volunteer to an activity with conflict checking.
     */
    public function assignVolunteer(int $activityId, int $userId, int $assignedBy, ?string $role = null): array
    {
        // Check for existing assignment
        $existing = $this->where('activity_id', $activityId)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            return ['success' => false, 'error' => 'Volunteer already assigned to this activity.'];
        }

        // Check for scheduling conflicts
        $conflicts = $this->checkConflicts($activityId, $userId);

        $status = empty($conflicts) ? 'assigned' : 'conflict';
        $conflictReason = empty($conflicts) ? null : implode('; ', $conflicts);

        $this->insert([
            'activity_id'     => $activityId,
            'user_id'         => $userId,
            'assigned_role'   => $role,
            'status'          => $status,
            'conflict_reason' => $conflictReason,
            'assigned_by'     => $assignedBy,
        ]);

        // Notify volunteer
        $notifModel = new NotificationModel();
        $notifModel->createNotification(
            $userId,
            'volunteer_assignment',
            'New Volunteer Assignment',
            "You have been assigned to an outreach activity. Please confirm or decline.",
            'pasimeo',
            'volunteer_assignments',
            $this->getInsertID()
        );

        return [
            'success'   => true,
            'status'    => $status,
            'conflicts' => $conflicts,
        ];
    }

    /**
     * Check for scheduling conflicts.
     */
    public function checkConflicts(int $activityId, int $userId): array
    {
        $activityModel = new OutreachActivityModel();
        $activity = $activityModel->find($activityId);
        if (! $activity) return [];

        $conflicts = [];
        $date = $activity['activity_date'];
        $start = $activity['start_time'];
        $end = $activity['end_time'];

        // Check 1: Other outreach activities on same date with overlapping time
        $otherActivities = $this->select('volunteer_assignments.*, outreach_activities.title, outreach_activities.start_time, outreach_activities.end_time')
            ->join('outreach_activities', 'outreach_activities.id = volunteer_assignments.activity_id')
            ->where('volunteer_assignments.user_id', $userId)
            ->where('outreach_activities.activity_date', $date)
            ->where('volunteer_assignments.activity_id !=', $activityId)
            ->whereIn('volunteer_assignments.status', ['assigned', 'confirmed'])
            ->findAll();

        foreach ($otherActivities as $oa) {
            if ($start < $oa['end_time'] && $end > $oa['start_time']) {
                $conflicts[] = "Conflicts with outreach activity: {$oa['title']} ({$oa['start_time']} - {$oa['end_time']})";
            }
        }

        // Check 2: Clinic staff schedule
        $dayOfWeek = (int) date('w', strtotime($date));
        $db = \Config\Database::connect();
        $clinicShifts = $db->table('clinic_staff_schedules')
            ->where('user_id', $userId)
            ->where('day_of_week', $dayOfWeek)
            ->get()->getResultArray();

        foreach ($clinicShifts as $shift) {
            if ($start < $shift['shift_end'] && $end > $shift['shift_start']) {
                $conflicts[] = "Conflicts with clinic shift ({$shift['shift_start']} - {$shift['shift_end']})";
            }
        }

        return $conflicts;
    }

    /**
     * Confirm assignment.
     */
    public function confirm(int $id): bool
    {
        return $this->update($id, ['status' => 'confirmed']);
    }

    /**
     * Decline assignment.
     */
    public function decline(int $id): bool
    {
        return $this->update($id, ['status' => 'declined']);
    }
}
