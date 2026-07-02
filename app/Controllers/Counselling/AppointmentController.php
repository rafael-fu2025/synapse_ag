<?php

namespace App\Controllers\Counselling;

use App\Controllers\BaseController;
use App\Models\CounsellingAppointmentModel;
use App\Models\CounsellorAvailabilityModel;
use App\Models\StudentModel;
use App\Models\CrisisAlertModel;
use App\Models\ReferralModel;
use App\Models\AuditLogModel;
use App\Models\NotificationModel;

class AppointmentController extends BaseController
{
    protected CounsellingAppointmentModel $apptModel;

    public function __construct()
    {
        $this->apptModel = new CounsellingAppointmentModel();
    }

    /**
     * Today's schedule + dashboard.
     */
    public function index()
    {
        $userId = session()->get('user_id');
        $roles  = session()->get('roles') ?? [];
        $isAdmin = in_array('admin', $roles);

        // Recalculate time-slot no-show analytics for this counsellor on dashboard load
        if (! $isAdmin) {
            $optimizer = new \App\Libraries\SchedulingOptimizer();
            $optimizer->recalculateSlotAnalytics($userId);
        }

        $schedule = $isAdmin
            ? $this->apptModel->getAllToday()
            : $this->apptModel->getTodaySchedule($userId);

        $stats = $this->apptModel->getTodayStats($isAdmin ? null : $userId);

        // Crisis alerts (unacknowledged)
        $crisisModel = new CrisisAlertModel();
        $crisisAlerts = $crisisModel->getUnacknowledged();

        // Pending incoming referrals
        $referralModel = new ReferralModel();
        $pendingReferrals = $referralModel->getPending('clinic_to_counselling');

        return view('counselling/appointments/index', [
            'title'            => 'Counselling — SYNAPSE',
            'heading'          => "Today's Appointments",
            'schedule'         => $schedule,
            'stats'            => $stats,
            'crisisAlerts'     => $crisisAlerts,
            'pendingReferrals' => $pendingReferrals,
        ]);
    }

    /**
     * Book appointment form.
     */
    public function create(int $studentId)
    {
        $studentModel = new StudentModel();
        $student = $studentModel->getWithProfile($studentId);
        if ($student === null) {
            return redirect()->to('/counselling')->with('error', 'Student not found.');
        }

        $date = $this->request->getGet('date') ?? date('Y-m-d', strtotime('+1 day'));

        $availModel = new CounsellorAvailabilityModel();
        $slots = $availModel->getAvailableSlots($date);

        return view('counselling/appointments/create', [
            'title'   => 'Book Appointment — SYNAPSE',
            'heading' => 'Book Counselling Appointment',
            'student' => $student,
            'slots'   => $slots,
            'date'    => $date,
        ]);
    }

    /**
     * Store appointment.
     */
    public function store()
    {
        $rules = [
            'student_id'       => 'required|is_natural_no_zero',
            'counsellor_id'    => 'required|is_natural_no_zero',
            'appointment_date' => 'required|valid_date',
            'start_time'       => 'required',
            'end_time'         => 'required',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $apptId = $this->apptModel->book([
            'student_id'       => $this->request->getPost('student_id'),
            'counsellor_id'    => $this->request->getPost('counsellor_id'),
            'appointment_date' => $this->request->getPost('appointment_date'),
            'start_time'       => $this->request->getPost('start_time'),
            'end_time'         => $this->request->getPost('end_time'),
            'type'             => $this->request->getPost('type') ?: 'initial',
            'reason'           => $this->request->getPost('reason'),
        ]);

        if ($apptId) {
            // Predict no-show probability and store it
            $optimizer = new \App\Libraries\SchedulingOptimizer();
            $prob = $optimizer->predictNoShowProbability(
                (int) $this->request->getPost('student_id'),
                (int) $this->request->getPost('counsellor_id'),
                $this->request->getPost('appointment_date'),
                $this->request->getPost('start_time')
            );
            $this->apptModel->update($apptId, ['no_show_probability' => $prob]);

            // Notify student
            $notifModel = new NotificationModel();
            $notifModel->createNotification(
                null,
                'appointment_booked',
                'Counselling Appointment Booked',
                'Your counselling appointment is scheduled for ' . $this->request->getPost('appointment_date') . ' at ' . $this->request->getPost('start_time') . '.',
                'counselling',
                'counselling_appointments',
                $apptId
            );

            $auditModel = new AuditLogModel();
            $auditModel->logAction(session()->get('user_id'), 'create', 'counselling', 'counselling_appointments', $apptId);

            return redirect()->to("/counselling/appointments/{$apptId}")
                ->with('success', 'Appointment booked successfully. Predicted No-Show Risk: ' . round($prob * 100, 1) . '%.');
        }

        return redirect()->back()->withInput()->with('error', 'Failed to book appointment.');
    }

    /**
     * View appointment detail.
     */
    public function show(int $id)
    {
        $appt = $this->apptModel->getFullAppointment($id);
        if ($appt === null) {
            return redirect()->to('/counselling')->with('error', 'Appointment not found.');
        }

        return view('counselling/appointments/show', [
            'title'   => "Appointment #{$id} — SYNAPSE",
            'heading' => "Appointment #{$id}",
            'appt'    => $appt,
        ]);
    }

    /**
     * Start/confirm session.
     */
    public function startSession(int $id)
    {
        $this->apptModel->update($id, ['status' => 'confirmed']);

        return redirect()->to("/counselling/appointments/{$id}")
            ->with('success', 'Session started. Student has checked in.');
    }

    /**
     * Complete session with notes.
     */
    public function completeSession(int $id)
    {
        $notes = $this->request->getPost('session_notes');

        $this->apptModel->update($id, [
            'session_notes' => $notes,
            'status'        => 'completed',
        ]);

        // Reset no-show counter
        $appt = $this->apptModel->find($id);
        if ($appt) {
            $studentModel = new StudentModel();
            $studentModel->update($appt['student_id'], ['consecutive_no_shows' => 0]);
        }

        $auditModel = new AuditLogModel();
        $auditModel->logAction(session()->get('user_id'), 'complete', 'counselling', 'counselling_appointments', $id);

        return redirect()->to('/counselling')->with('success', 'Session completed.');
    }

    /**
     * Mark no-show.
     */
    public function markNoShow(int $id)
    {
        $result = $this->apptModel->markNoShow($id);

        $msg = 'Marked as no-show.';
        if ($result['welfare_alert'] ?? false) {
            $msg .= ' ⚠️ THREE-STRIKE ALERT: Welfare follow-up notification sent.';
        }

        return redirect()->to('/counselling')->with('warning', $msg);
    }

    /**
     * Cancel appointment.
     */
    public function cancel(int $id)
    {
        $this->apptModel->update($id, [
            'status'              => 'cancelled',
            'cancellation_reason' => $this->request->getPost('cancellation_reason'),
        ]);

        return redirect()->to('/counselling')->with('success', 'Appointment cancelled.');
    }
}
