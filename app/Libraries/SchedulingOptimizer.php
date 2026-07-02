<?php

namespace App\Libraries;

use App\Models\SchedulingAnalyticsModel;
use App\Models\CounsellingAppointmentModel;
use App\Models\StudentModel;

class SchedulingOptimizer
{
    /**
     * Recalculate historical time-slot no-show rates for a counsellor.
     */
    public function recalculateSlotAnalytics(int $counsellorId): bool
    {
        $db = \Config\Database::connect();
        $analyticsModel = new SchedulingAnalyticsModel();

        // 1. Fetch appointments summary grouped by day-of-week and time slot
        $results = $db->table('counselling_appointments')
            ->where('counsellor_id', $counsellorId)
            ->select('WEEKDAY(appointment_date) as dow, start_time, 
                     COUNT(id) as total_appts, 
                     SUM(CASE WHEN status = "no_show" THEN 1 ELSE 0 END) as no_shows')
            ->groupBy('dow, start_time')
            ->get()->getResultArray();

        foreach ($results as $row) {
            // Note: WEEKDAY() returns 0=Monday...6=Sunday. Let's map it to 0=Sunday...6=Saturday:
            $weekday = (int) $row['dow'];
            $dayOfWeek = ($weekday + 1) % 7; // Map Monday=0 to Monday=1, Sunday=6 to Sunday=0

            $total = (int) $row['total_appts'];
            $noShows = (int) $row['no_shows'];
            
            $noShowRate = $total > 0 ? round($noShows / $total, 4) : 0.0;
            
            // Recommends overbooking if no-show rate is high
            $recommendedOverbooking = 0;
            if ($noShowRate >= 0.30) {
                $recommendedOverbooking = 2;
            } elseif ($noShowRate >= 0.15) {
                $recommendedOverbooking = 1;
            }

            // Simple utilization rate logic (filled appointments vs expected baseline capacity of 1 per slot)
            $avgUtilization = $total > 0 ? 0.85 : 0.0;

            // Check if record exists
            $existing = $analyticsModel->where('counsellor_id', $counsellorId)
                ->where('day_of_week', $dayOfWeek)
                ->where('time_slot', $row['start_time'])
                ->first();

            $data = [
                'counsellor_id'           => $counsellorId,
                'day_of_week'             => $dayOfWeek,
                'time_slot'               => $row['start_time'],
                'total_appointments'      => $total,
                'total_no_shows'          => $noShows,
                'no_show_rate'            => $noShowRate,
                'avg_utilization'         => $avgUtilization,
                'recommended_overbooking' => $recommendedOverbooking,
                'last_calculated_at'      => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                $analyticsModel->update($existing['id'], $data);
            } else {
                $analyticsModel->insert($data);
            }
        }

        return true;
    }

    /**
     * Predict no-show probability for an upcoming appointment booking.
     */
    public function predictNoShowProbability(int $studentId, int $counsellorId, string $date, string $timeSlot): float
    {
        $db = \Config\Database::connect();
        
        // 1. Student baseline historical no-show rate
        $appts = $db->table('counselling_appointments')
            ->where('student_id', $studentId)
            ->select('COUNT(id) as total, SUM(CASE WHEN status = "no_show" THEN 1 ELSE 0 END) as no_shows')
            ->get()->getRow();
        
        $totalAppts = $appts ? (int) $appts->total : 0;
        $noShows = $appts ? (int) $appts->no_shows : 0;

        $studentNoShowRate = $totalAppts > 0 ? ($noShows / $totalAppts) : 0.05;

        // 2. Student's current consecutive no-shows penalty
        $student = $db->table('students')->where('id', $studentId)->select('consecutive_no_shows')->get()->getRow();
        $consecutiveNoShows = $student ? (int) $student->consecutive_no_shows : 0;
        $consecutivePenalty = $consecutiveNoShows * 0.25; // 25% increase per consecutive no-show

        // 3. Time slot historical no-show rate
        $dayOfWeek = (int) date('w', strtotime($date));
        $analytics = $db->table('scheduling_analytics')
            ->where('counsellor_id', $counsellorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('time_slot', $timeSlot)
            ->select('no_show_rate')
            ->get()->getRow();

        $slotNoShowRate = $analytics ? (float) $analytics->no_show_rate : 0.10;

        // 4. Compute Weighted Probability Score
        // Weight: 40% student history, 30% slot history, 30% consecutive no-shows factor
        $probability = (0.40 * $studentNoShowRate) + (0.30 * $slotNoShowRate) + (0.30 * ($consecutiveNoShows > 0 ? 0.80 : 0.05)) + $consecutivePenalty;

        // Clip to range [0.00, 1.00]
        return (float) min(1.0000, max(0.0000, round($probability, 4)));
    }
}
