<?php

namespace App\Models;

use CodeIgniter\Model;

class OutreachAttendanceModel extends Model
{
    protected $table            = 'outreach_attendance';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'activity_id', 'user_id', 'check_in_time', 'check_out_time',
        'check_in_method', 'hours_credited', 'verified_by',
    ];

    protected $useTimestamps = false;

    /**
     * Get attendance for an activity.
     */
    public function getByActivity(int $activityId): array
    {
        return $this->select('outreach_attendance.*, users.first_name, users.last_name, users.email, v.first_name as verifier_first, v.last_name as verifier_last')
            ->join('users', 'users.id = outreach_attendance.user_id')
            ->join('users as v', 'v.id = outreach_attendance.verified_by', 'left')
            ->where('activity_id', $activityId)
            ->orderBy('check_in_time', 'ASC')
            ->findAll();
    }

    /**
     * Check in a volunteer (manual or QR).
     */
    public function checkIn(int $activityId, int $userId, string $method = 'manual'): int|false
    {
        // Check if already checked in
        $existing = $this->where('activity_id', $activityId)
            ->where('user_id', $userId)
            ->first();

        if ($existing) return false;

        $this->insert([
            'activity_id'     => $activityId,
            'user_id'         => $userId,
            'check_in_time'   => date('Y-m-d H:i:s'),
            'check_in_method' => $method,
        ]);

        return $this->getInsertID() ?: false;
    }

    /**
     * Check out and auto-calculate hours.
     *
     * Hours calculation handles next-day checkouts: if the recorded
     * check_out falls on an earlier wall-clock date than check_in (raw
     * diff is negative), we assume the volunteer stayed past midnight
     * and roll check_out forward by 1 day. Using the raw-diff check
     * rather than a time-of-day comparison prevents double-rolling when
     * PHP's date() already returned the absolute-correct next-day stamp.
     *
     * Output is clamped to [0, 24] to defend against any pathological
     * clock skew or bad seed data that would otherwise produce negative
     * hours — negative hours feed into workload_score calculations and
     * violate CHECK constraints on volunteer_workload_scores.
     */
    public function checkOut(int $id): bool
    {
        $record = $this->find($id);
        if ($record === null || $record['check_out_time'] !== null) return false;

        $checkInTs = strtotime($record['check_in_time']);
        $checkOutTs = strtotime(date('Y-m-d H:i:s'));

        // Only roll forward if the raw diff is negative. This catches the
        // case where PHP's date() formatted checkout as the same calendar
        // date as checkin (e.g. checkin=22:30 yesterday, checkout=00:40
        // today stored as "yesterday" by a buggy caller).
        if (($checkOutTs - $checkInTs) < 0) {
            $checkOutTs = strtotime('+1 day', $checkOutTs);
        }

        $hoursRaw = ($checkOutTs - $checkInTs) / 3600.0;
        $hours = max(0.0, min(24.0, round($hoursRaw, 2)));

        return $this->update($id, [
            'check_out_time'  => date('Y-m-d H:i:s', $checkOutTs),
            'hours_credited'  => $hours,
        ]);
    }

    /**
     * Verify attendance.
     */
    public function verify(int $id, int $verifierId): bool
    {
        return $this->update($id, ['verified_by' => $verifierId]);
    }

    /**
     * Get total volunteer hours for a program.
     */
    public function getTotalHoursForProgram(int $programId): float
    {
        $result = $this->select('SUM(outreach_attendance.hours_credited) as total')
            ->join('outreach_activities', 'outreach_activities.id = outreach_attendance.activity_id')
            ->where('outreach_activities.program_id', $programId)
            ->where('outreach_attendance.hours_credited IS NOT NULL')
            ->first();

        return (float) ($result['total'] ?? 0);
    }

    /**
     * Get volunteer hours for a user.
     */
    public function getUserHours(int $userId): array
    {
        $result = $this->select('SUM(hours_credited) as total_hours, COUNT(*) as total_activities')
            ->where('user_id', $userId)
            ->where('hours_credited IS NOT NULL')
            ->first();

        return [
            'total_hours'      => (float) ($result['total_hours'] ?? 0),
            'total_activities' => (int) ($result['total_activities'] ?? 0),
        ];
    }
}
