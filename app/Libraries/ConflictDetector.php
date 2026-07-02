<?php

namespace App\Libraries;

use App\Models\VolunteerAssignmentModel;
use App\Models\OutreachActivityModel;
use App\Models\VolunteerWorkloadScoreModel;

class ConflictDetector
{
    /**
     * Detect volunteer scheduling conflicts across outreach activities, counselling appointments, and clinic schedules.
     */
    public function detectConflicts(int $userId, string $date, string $startTime, string $endTime): array
    {
        $db = \Config\Database::connect();
        
        // 1. Check for overlapping Outreach Activities
        $overlapActivity = $db->table('volunteer_assignments')
            ->join('outreach_activities', 'outreach_activities.id = volunteer_assignments.activity_id')
            ->where('volunteer_assignments.user_id', $userId)
            ->where('volunteer_assignments.status !=', 'declined')
            ->where('outreach_activities.activity_date', $date)
            ->where("NOT ('{$endTime}' <= outreach_activities.start_time OR '{$startTime}' >= outreach_activities.end_time)")
            ->select('outreach_activities.title, outreach_activities.start_time, outreach_activities.end_time')
            ->get()->getRowArray();

        if ($overlapActivity) {
            return [
                'has_conflict'    => true,
                'conflict_reason' => "Schedule overlap with Outreach Activity: '{$overlapActivity['title']}' ({$overlapActivity['start_time']} - {$overlapActivity['end_time']})."
            ];
        }

        // 2. Check for overlapping Counselling Appointments (for both counsellor and student roles)
        // Check if the user has a counselling appointment today
        // Note: CounsellingAppointments table uses student_id (profile id) or counsellor_id (user id).
        // Let's resolve the student profile id first if the user is a student.
        $student = $db->table('students')->where('user_id', $userId)->select('id')->get()->getRowArray();
        $studentId = $student ? (int) $student['id'] : null;

        $apptQuery = $db->table('counselling_appointments')
            ->where('appointment_date', $date)
            ->whereIn('status', ['scheduled', 'confirmed']);
            
        if ($studentId) {
            $apptQuery->groupStart()
                ->where('student_id', $studentId)
                ->orWhere('counsellor_id', $userId)
                ->groupEnd();
        } else {
            $apptQuery->where('counsellor_id', $userId);
        }

        $overlapAppt = $apptQuery
            ->where("NOT ('{$endTime}' <= start_time OR '{$startTime}' >= end_time)")
            ->select('start_time, end_time, status')
            ->get()->getRowArray();

        if ($overlapAppt) {
            return [
                'has_conflict'    => true,
                'conflict_reason' => "Schedule overlap with Counselling Session: {$overlapAppt['start_time']} - {$overlapAppt['end_time']} (Status: {$overlapAppt['status']})."
            ];
        }

        // 3. Check for overlapping Clinic Staff Schedule Shifts
        // ClinicStaffSchedule uses: user_id, day_of_week (0=Sun...6=Sat), shift_start, shift_end
        $dayOfWeek = (int) date('w', strtotime($date));
        $overlapShift = $db->table('clinic_staff_schedules')
            ->where('user_id', $userId)
            ->where('day_of_week', $dayOfWeek)
            ->where("NOT ('{$endTime}' <= shift_start OR '{$startTime}' >= shift_end)")
            ->select('shift_start, shift_end')
            ->get()->getRowArray();

        if ($overlapShift) {
            return [
                'has_conflict'    => true,
                'conflict_reason' => "Schedule conflict with Clinic Duty Shift: {$overlapShift['shift_start']} - {$overlapShift['shift_end']}."
            ];
        }

        return [
            'has_conflict'    => false,
            'conflict_reason' => null
        ];
    }

    /**
     * Calculate and store the volunteer workload score for a student user within a period.
     */
    public function calculateWorkload(int $userId, string $startPeriod, string $endPeriod): array
    {
        $db = \Config\Database::connect();
        
        // 1. Calculate committed hours and assigned activity count
        $assignments = $db->table('volunteer_assignments')
            ->join('outreach_activities', 'outreach_activities.id = volunteer_assignments.activity_id')
            ->where('volunteer_assignments.user_id', $userId)
            ->where('volunteer_assignments.status', 'confirmed')
            ->where('outreach_activities.activity_date >=', $startPeriod)
            ->where('outreach_activities.activity_date <=', $endPeriod)
            ->select('outreach_activities.start_time, outreach_activities.end_time')
            ->get()->getResultArray();

        $totalActivities = count($assignments);
        $totalHoursCommitted = 0.0;

        foreach ($assignments as $a) {
            $start = strtotime($a['start_time']);
            $end = strtotime($a['end_time']);
            if ($end > $start) {
                $totalHoursCommitted += round(($end - $start) / 3600, 2);
            }
        }

        // 2. Calculate completed hours from outreach_attendance.
        // Clamp to non-negative so stale or corrupted rows (e.g. negative
        // hours_credited from a buggy check-out timestamp) cannot poison the
        // division in step 3. The DECIMAL column allows negatives; the
        // downstream CHECK constraint on volunteer_workload_scores does not.
        $attendance = $db->table('outreach_attendance')
            ->where('user_id', $userId)
            ->where('check_out_time IS NOT NULL')
            ->selectSum('hours_credited')
            ->get()->getRow();

        $rawHoursCompleted = $attendance ? (float) $attendance->hours_credited : 0.0;
        $totalHoursCompleted = max(0.0, $rawHoursCompleted);

        // 3. Attendance Rate — clamped to [0, 1] for the CHECK constraint
        // chk_vws_attendance. Default to 1.0 when there's nothing to measure.
        if ($totalHoursCommitted > 0) {
            $ratio = $totalHoursCompleted / $totalHoursCommitted;
            $attendanceRate = max(0.0000, min(1.0000, round($ratio, 4)));
        } else {
            $attendanceRate = 1.0000;
        }

        // 4. Workload Score (0-100, where 30 hours in a 2-week period is 100% capacity)
        // Clamp to [0, 100] for chk_vws_score.
        $workloadScore = max(0.00, min(100.00, round(($totalHoursCommitted / 30.0) * 100, 2)));

        // 5. Predict Availability Score (simple probability using historical attendance rate)
        // Clamp to [0, 1] for chk_vws_availability.
        $predictedAvailability = $attendanceRate;

        // Count calendar conflicts in this period
        $conflicts = $db->table('volunteer_assignments')
            ->where('user_id', $userId)
            ->where('status', 'conflict')
            ->countAllResults(false);

        $workloadModel = new VolunteerWorkloadScoreModel();
        
        $existing = $workloadModel->getForUserPeriod($userId, $startPeriod, $endPeriod);
        
        $data = [
            'user_id'                      => $userId,
            'period_start'                 => $startPeriod,
            'period_end'                   => $endPeriod,
            'total_activities_assigned'    => $totalActivities,
            'total_hours_committed'        => $totalHoursCommitted,
            'total_hours_completed'        => $totalHoursCompleted,
            'attendance_rate'              => $attendanceRate,
            'workload_score'               => $workloadScore,
            'conflict_count'               => $conflicts,
            'predicted_availability_score' => $predictedAvailability,
            'last_calculated_at'           => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $workloadModel->update($existing['id'], $data);
        } else {
            $workloadModel->insert($data);
        }

        return $data;
    }

    /**
     * Suggest conflict-free alternative volunteers for an activity.
     */
    public function suggestAlternatives(int $activityId, int $limit = 5): array
    {
        $db = \Config\Database::connect();
        
        // 1. Get target activity details
        $activity = $db->table('outreach_activities')->where('id', $activityId)->get()->getRowArray();
        if (!$activity) return [];

        $date      = $activity['activity_date'];
        $startTime = $activity['start_time'];
        $endTime   = $activity['end_time'];

        // 2. Fetch all student user accounts
        $students = $db->table('users')
            ->join('user_roles', 'user_roles.user_id = users.id')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->where('roles.name', 'student')
            ->select('users.id, users.first_name, users.last_name, users.email')
            ->get()->getResultArray();

        $alternatives = [];
        $periodStart  = date('Y-m-d', strtotime('-7 days', strtotime($date)));
        $periodEnd    = date('Y-m-d', strtotime('+7 days', strtotime($date)));

        foreach ($students as $student) {
            $userId = (int) $student['id'];

            // Check if already assigned to this activity
            $alreadyAssigned = $db->table('volunteer_assignments')
                ->where('activity_id', $activityId)
                ->where('user_id', $userId)
                ->countAllResults(false);

            if ($alreadyAssigned > 0) continue;

            // Check for conflict
            $conflict = $this->detectConflicts($userId, $date, $startTime, $endTime);
            if ($conflict['has_conflict']) continue;

            // Get workload score
            $workload = $this->calculateWorkload($userId, $periodStart, $periodEnd);

            $alternatives[] = [
                'user_id'            => $userId,
                'name'               => $student['first_name'] . ' ' . $student['last_name'],
                'email'              => $student['email'],
                'workload_score'     => $workload['workload_score'],
                'hours_committed'    => $workload['total_hours_committed'],
                'availability_score' => $workload['predicted_availability_score']
            ];
        }

        // Sort by workload score (ascending - lowest loaded first)
        usort($alternatives, function ($a, $b) {
            return $a['workload_score'] <=> $b['workload_score'];
        });

        return array_slice($alternatives, 0, $limit);
    }
}
