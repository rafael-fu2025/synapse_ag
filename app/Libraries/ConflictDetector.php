<?php

namespace App\Libraries;

/**
 * Scheduling-conflict detector.
 *
 * Detects overlapping time-slot conflicts between:
 *   1. Counselling appointments (student or counsellor)
 *   2. Clinic staff duty shifts (for clinical staff / admin)
 *
 * History: this class previously also detected outreach-activity overlaps
 * via the PASIMEO `volunteer_assignments` / `outreach_activities` tables
 * and exposed `calculateWorkload()` + `suggestAlternatives()` helpers for
 * the volunteer-program module. PASIMEO was dropped from capstone scope
 * in July 2026 — the outreach-specific logic and helpers were removed.
 */
class ConflictDetector
{
    /**
     * Detect scheduling conflicts for a user on a given date + time window.
     *
     * Looks at counselling appointments and clinic duty shifts. Returns a
     * structured array with `has_conflict` (bool) and `conflict_reason`
     * (string|null) describing the first overlap found.
     *
     * @param int    $userId    SYNAPSE users.id
     * @param string $date      Date in 'Y-m-d' format
     * @param string $startTime Start time in 'H:i:s' format
     * @param string $endTime   End time in 'H:i:s' format
     */
    public function detectConflicts(int $userId, string $date, string $startTime, string $endTime): array
    {
        $db = \Config\Database::connect();

        // 1. Check for overlapping Counselling Appointments
        // (for both counsellor and student roles).
        //
        // CounsellingAppointments.student_id references the students table
        // (profile id), while counsellor_id references users.id directly.
        // We resolve the student's profile id first if the user is a student.
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

        // 2. Check for overlapping Clinic Staff Schedule Shifts.
        // clinic_staff_schedules uses: user_id, day_of_week (0=Sun…6=Sat),
        // shift_start, shift_end.
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
}