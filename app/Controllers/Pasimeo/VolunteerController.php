<?php

namespace App\Controllers\Pasimeo;

use App\Controllers\BaseController;
use App\Models\VolunteerAssignmentModel;
use App\Models\OutreachActivityModel;

class VolunteerController extends BaseController
{
    protected VolunteerAssignmentModel $volunteerModel;

    public function __construct()
    {
        $this->volunteerModel = new VolunteerAssignmentModel();
    }

    /**
     * Assign volunteer form.
     */
    public function assign(int $activityId)
    {
        $activityModel = new OutreachActivityModel();
        $activity = $activityModel->getWithDetails($activityId);

        if ($activity === null) {
            return redirect()->to('/pasimeo')->with('error', 'Activity not found.');
        }

        // Get available users (exclude already assigned)
        $assignedUserIds = array_column($activity['volunteers'], 'user_id');

        $db = \Config\Database::connect();
        $usersQuery = $db->table('users')
            ->select('users.id, users.first_name, users.last_name, users.email')
            ->where('is_active', true);

        if (! empty($assignedUserIds)) {
            $usersQuery->whereNotIn('id', $assignedUserIds);
        }

        $availableUsers = $usersQuery->orderBy('last_name', 'ASC')->get()->getResultArray();

        // Integrate Workload Scores & Conflict Detector
        $conflictDetector = new \App\Libraries\ConflictDetector();
        $periodStart = date('Y-m-d', strtotime('-7 days', strtotime($activity['activity_date'])));
        $periodEnd = date('Y-m-d', strtotime('+7 days', strtotime($activity['activity_date'])));

        foreach ($availableUsers as &$user) {
            $userId = (int) $user['id'];
            
            // Check conflict
            $conflict = $conflictDetector->detectConflicts($userId, $activity['activity_date'], $activity['start_time'], $activity['end_time']);
            $user['has_conflict'] = $conflict['has_conflict'];
            $user['conflict_reason'] = $conflict['conflict_reason'];

            // Get workload score
            $workload = $conflictDetector->calculateWorkload($userId, $periodStart, $periodEnd);
            $user['workload_score'] = $workload['workload_score'];
            $user['hours_committed'] = $workload['total_hours_committed'];
        }

        // Suggest alternatives
        $alternatives = $conflictDetector->suggestAlternatives($activityId, 5);

        return view('pasimeo/volunteers/assign', [
            'title'          => 'Assign Volunteers — SYNAPSE',
            'heading'        => 'Assign Volunteers',
            'activity'       => $activity,
            'availableUsers' => $availableUsers,
            'alternatives'   => $alternatives,
        ]);
    }

    /**
     * Store volunteer assignment.
     */
    public function store()
    {
        $activityId = (int) $this->request->getPost('activity_id');
        $userIds    = $this->request->getPost('user_ids') ?? [];
        $role       = $this->request->getPost('assigned_role');

        if (empty($userIds)) {
            return redirect()->back()->with('error', 'Please select at least one volunteer.');
        }

        $activityModel = new OutreachActivityModel();
        $activity = $activityModel->find($activityId);
        if (!$activity) {
            return redirect()->to('/pasimeo')->with('error', 'Activity not found.');
        }

        $results = [];
        $conflictDetector = new \App\Libraries\ConflictDetector();
        $periodStart = date('Y-m-d', strtotime('-7 days', strtotime($activity['activity_date'])));
        $periodEnd = date('Y-m-d', strtotime('+7 days', strtotime($activity['activity_date'])));

        foreach ($userIds as $userId) {
            $result = $this->volunteerModel->assignVolunteer(
                $activityId,
                (int) $userId,
                session()->get('user_id'),
                $role
            );
            
            if ($result['success']) {
                $conflictDetector->calculateWorkload((int) $userId, $periodStart, $periodEnd);
            }
            $results[] = $result;
        }

        $conflictCount = count(array_filter($results, fn($r) => !empty($r['conflicts'])));
        $successCount  = count(array_filter($results, fn($r) => $r['success']));

        $msg = "{$successCount} volunteer(s) assigned.";
        if ($conflictCount > 0) {
            $msg .= " ⚠️ {$conflictCount} had scheduling conflicts.";
        }

        return redirect()->to("/pasimeo/activities/{$activityId}")
            ->with($conflictCount > 0 ? 'warning' : 'success', $msg);
    }

    /**
     * Confirm assignment.
     */
    public function confirm(int $id)
    {
        $this->volunteerModel->confirm($id);
        $assignment = $this->volunteerModel->find($id);

        return redirect()->to("/pasimeo/activities/{$assignment['activity_id']}")
            ->with('success', 'Assignment confirmed.');
    }

    /**
     * Decline assignment.
     */
    public function decline(int $id)
    {
        $this->volunteerModel->decline($id);
        $assignment = $this->volunteerModel->find($id);

        return redirect()->to("/pasimeo/activities/{$assignment['activity_id']}")
            ->with('warning', 'Assignment declined.');
    }
}
