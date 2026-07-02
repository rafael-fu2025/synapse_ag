<?php

namespace App\Controllers\Counselling;

use App\Controllers\BaseController;
use App\Models\CounsellorAvailabilityModel;

class AvailabilityController extends BaseController
{
    protected CounsellorAvailabilityModel $availModel;

    public function __construct()
    {
        $this->availModel = new CounsellorAvailabilityModel();
    }

    /**
     * Availability schedule view/edit.
     */
    public function index()
    {
        $userId   = session()->get('user_id');
        $schedule = $this->availModel->getGroupedByDay($userId);

        return view('counselling/availability/index', [
            'title'    => 'My Availability — SYNAPSE',
            'heading'  => 'My Weekly Availability',
            'schedule' => $schedule,
        ]);
    }

    /**
     * Save availability for a day.
     */
    public function store()
    {
        $userId    = session()->get('user_id');
        $dayOfWeek = (int) $this->request->getPost('day_of_week');
        $slotsJson = $this->request->getPost('slots');

        $slots = json_decode($slotsJson, true);

        if (! is_array($slots) || empty($slots)) {
            return redirect()->back()->with('error', 'Please add at least one time slot.');
        }

        $this->availModel->setCounsellorSchedule($userId, $dayOfWeek, $slots);

        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return redirect()->to('/counselling/availability')
            ->with('success', "Availability updated for {$dayNames[$dayOfWeek]}.");
    }

    /**
     * Quick add a single slot.
     */
    public function addSlot()
    {
        $userId = session()->get('user_id');

        $rules = [
            'day_of_week' => 'required|in_list[0,1,2,3,4,5,6]',
            'start_time'  => 'required',
            'end_time'    => 'required',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->availModel->insert([
            'counsellor_id' => $userId,
            'day_of_week'   => (int) $this->request->getPost('day_of_week'),
            'start_time'    => $this->request->getPost('start_time'),
            'end_time'      => $this->request->getPost('end_time'),
            'max_slots'     => (int) ($this->request->getPost('max_slots') ?: 1),
            'is_active'     => true,
        ]);

        return redirect()->to('/counselling/availability')
            ->with('success', 'Time slot added.');
    }

    /**
     * Remove a slot.
     */
    public function removeSlot(int $id)
    {
        $slot = $this->availModel->find($id);

        if ($slot && (int) $slot['counsellor_id'] === (int) session()->get('user_id')) {
            $this->availModel->update($id, ['is_active' => false]);
        }

        return redirect()->to('/counselling/availability')
            ->with('success', 'Time slot removed.');
    }
}
