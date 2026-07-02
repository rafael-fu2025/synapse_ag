<?php

namespace App\Controllers\Pasimeo;

use App\Controllers\BaseController;
use App\Models\OutreachAttendanceModel;
use App\Models\OutreachActivityModel;
use App\Models\AuditLogModel;

class AttendanceController extends BaseController
{
    protected OutreachAttendanceModel $attendanceModel;

    public function __construct()
    {
        $this->attendanceModel = new OutreachAttendanceModel();
    }

    /**
     * Attendance tracking view for an activity.
     */
    public function index(int $activityId)
    {
        $activityModel = new OutreachActivityModel();
        $activity = $activityModel->getWithDetails($activityId);

        if ($activity === null) {
            return redirect()->to('/pasimeo')->with('error', 'Activity not found.');
        }

        return view('pasimeo/attendance/index', [
            'title'    => "Attendance — {$activity['title']}",
            'heading'  => "Attendance: {$activity['title']}",
            'activity' => $activity,
        ]);
    }

    /**
     * Manual check-in.
     */
    public function checkIn()
    {
        $activityId = (int) $this->request->getPost('activity_id');
        $userId     = (int) $this->request->getPost('user_id');

        $result = $this->attendanceModel->checkIn($activityId, $userId, 'manual');

        if ($result === false) {
            return redirect()->back()->with('warning', 'Already checked in.');
        }

        $auditModel = new AuditLogModel();
        $auditModel->logAction(session()->get('user_id'), 'check_in', 'pasimeo', 'outreach_attendance', $result);

        return redirect()->to("/pasimeo/attendance/{$activityId}")
            ->with('success', 'Volunteer checked in.');
    }

    /**
     * Check out + auto-calculate hours.
     */
    public function checkOut(int $id)
    {
        $this->attendanceModel->checkOut($id);

        $record = $this->attendanceModel->find($id);

        return redirect()->to("/pasimeo/attendance/{$record['activity_id']}")
            ->with('success', "Checked out. Hours credited: {$record['hours_credited']}h");
    }

    /**
     * Verify attendance.
     */
    public function verify(int $id)
    {
        $this->attendanceModel->verify($id, session()->get('user_id'));

        $record = $this->attendanceModel->find($id);

        return redirect()->to("/pasimeo/attendance/{$record['activity_id']}")
            ->with('success', 'Attendance verified.');
    }

    /**
     * Verify all unverified for an activity.
     */
    public function verifyAll(int $activityId)
    {
        $records = $this->attendanceModel
            ->where('activity_id', $activityId)
            ->where('verified_by IS NULL')
            ->findAll();

        $count = 0;
        foreach ($records as $r) {
            $this->attendanceModel->verify((int) $r['id'], session()->get('user_id'));
            $count++;
        }

        return redirect()->to("/pasimeo/attendance/{$activityId}")
            ->with('success', "{$count} attendance record(s) verified.");
    }
}
