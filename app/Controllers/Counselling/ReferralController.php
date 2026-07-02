<?php

namespace App\Controllers\Counselling;

use App\Controllers\BaseController;
use App\Models\ReferralModel;
use App\Models\NotificationModel;

class ReferralController extends BaseController
{
    protected ReferralModel $referralModel;

    public function __construct()
    {
        $this->referralModel = new ReferralModel();
    }

    /**
     * Incoming referrals list.
     */
    public function index()
    {
        $referrals = $this->referralModel
            ->select('referrals.*, students.student_number, u_student.first_name as student_first, u_student.last_name as student_last, u_referrer.first_name as referrer_first, u_referrer.last_name as referrer_last')
            ->join('students', 'students.id = referrals.student_id')
            ->join('users as u_student', 'u_student.id = students.user_id')
            ->join('users as u_referrer', 'u_referrer.id = referrals.referred_by')
            ->where('direction', 'clinic_to_counselling')
            ->orderBy('FIELD(status, "pending", "accepted", "in_progress", "completed", "declined")')
            ->orderBy('created_at', 'DESC')
            ->findAll();

        return view('counselling/referrals/index', [
            'title'     => 'Incoming Referrals — SYNAPSE',
            'heading'   => 'Incoming Referrals',
            'referrals' => $referrals,
        ]);
    }

    /**
     * Accept a referral.
     */
    public function accept(int $id)
    {
        $this->referralModel->update($id, [
            'status'      => 'accepted',
            'referred_to' => session()->get('user_id'),
        ]);

        $notifModel = new NotificationModel();
        $notifModel->createNotification(
            null,
            'referral_accepted',
            'Referral Accepted',
            "Your referral (#{$id}) has been accepted by the counselling team.",
            'counselling',
            'referrals',
            $id
        );

        return redirect()->to('/counselling/referrals')->with('success', 'Referral accepted.');
    }

    /**
     * Decline a referral.
     */
    public function decline(int $id)
    {
        $this->referralModel->update($id, ['status' => 'declined']);

        return redirect()->to('/counselling/referrals')->with('warning', 'Referral declined.');
    }
}
