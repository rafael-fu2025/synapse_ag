<?php

namespace App\Models;

use CodeIgniter\Model;

class CounsellorAvailabilityModel extends Model
{
    protected $table            = 'counsellor_availability';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'counsellor_id', 'day_of_week', 'start_time', 'end_time',
        'max_slots', 'is_active',
    ];

    protected $useTimestamps = false;

    /**
     * Get availability for a specific counsellor.
     */
    public function getByCounsellor(int $counsellorId): array
    {
        return $this->where('counsellor_id', $counsellorId)
            ->where('is_active', true)
            ->orderBy('day_of_week', 'ASC')
            ->orderBy('start_time', 'ASC')
            ->findAll();
    }

    /**
     * Get all active availability grouped by day.
     */
    public function getGroupedByDay(int $counsellorId): array
    {
        $slots = $this->getByCounsellor($counsellorId);
        $grouped = [];
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        foreach ($slots as $slot) {
            $day = (int) $slot['day_of_week'];
            $grouped[$day] = $grouped[$day] ?? ['name' => $dayNames[$day], 'slots' => []];
            $grouped[$day]['slots'][] = $slot;
        }

        return $grouped;
    }

    /**
     * Get available slots for booking on a specific date.
     * Excludes already-booked slots.
     */
    public function getAvailableSlots(string $date): array
    {
        $dayOfWeek = (int) date('w', strtotime($date));

        $slots = $this->select('counsellor_availability.*, users.first_name, users.last_name')
            ->join('users', 'users.id = counsellor_availability.counsellor_id')
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->findAll();

        // Check existing bookings for this date
        $appointmentModel = new CounsellingAppointmentModel();

        foreach ($slots as &$slot) {
            $booked = $appointmentModel
                ->where('counsellor_id', $slot['counsellor_id'])
                ->where('appointment_date', $date)
                ->where('start_time', $slot['start_time'])
                ->whereIn('status', ['scheduled', 'confirmed'])
                ->countAllResults(false);

            $slot['booked_count'] = $booked;
            $slot['available'] = $booked < (int) $slot['max_slots'];
        }

        return $slots;
    }

    /**
     * Set counsellor schedule (replace all for a given day).
     */
    public function setCounsellorSchedule(int $counsellorId, int $dayOfWeek, array $slots): void
    {
        // Deactivate existing
        $this->where('counsellor_id', $counsellorId)
            ->where('day_of_week', $dayOfWeek)
            ->set('is_active', false)
            ->update();

        // Insert new
        foreach ($slots as $slot) {
            $this->insert([
                'counsellor_id' => $counsellorId,
                'day_of_week'   => $dayOfWeek,
                'start_time'    => $slot['start_time'],
                'end_time'      => $slot['end_time'],
                'max_slots'     => $slot['max_slots'] ?? 1,
                'is_active'     => true,
            ]);
        }
    }
}
