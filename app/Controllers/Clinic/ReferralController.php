<?php

namespace App\Controllers\Clinic;

use App\Controllers\BaseController;
use App\Models\ReferralModel;
use App\Models\NotificationModel;
use App\Models\AuditLogModel;

class ReferralController extends BaseController
{
    protected ReferralModel $referralModel;

    public function __construct()
    {
        $this->referralModel = new ReferralModel();
    }

    /**
     * List referrals.
     */
    public function index()
    {
        $status    = $this->request->getGet('status');
        $direction = $this->request->getGet('direction');

        $referrals = $this->referralModel->getFiltered($status, $direction);

        return view('clinic/referrals/index', [
            'title'     => 'Referrals — SYNAPSE',
            'heading'   => 'Referrals',
            'referrals' => $referrals,
            'filters'   => ['status' => $status, 'direction' => $direction],
        ]);
    }

    /**
     * Create referral form.
     */
    public function create(int $consultationId)
    {
        $consultModel = new \App\Models\ConsultationModel();
        $consult = $consultModel->getFullConsultation($consultationId);

        if ($consult === null) {
            return redirect()->to('/clinic/consultations')->with('error', 'Consultation not found.');
        }

        return view('clinic/referrals/create', [
            'title'   => 'Create Referral — SYNAPSE',
            'heading' => 'Refer to Counselling',
            'consult' => $consult,
        ]);
    }

    /**
     * Store referral.
     */
    public function store()
    {
        $rules = [
            'student_id'              => 'required|is_natural_no_zero',
            'source_consultation_id'  => 'required|is_natural_no_zero',
            'reason'                  => 'required|min_length[3]',
            'priority'                => 'required|in_list[routine,urgent,emergency]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $referralId = $this->referralModel->createClinicReferral([
            'student_id'             => $this->request->getPost('student_id'),
            'referred_by'            => session()->get('user_id'),
            // referred_to is intentionally NULL on creation — the referral is
            // broadcast to all counsellors (via notification). The first
            // counsellor to accept the referral claims ownership by setting
            // referred_to = their user_id. See Counselling\ReferralController::accept.
            'referred_to'            => null,
            'source_consultation_id' => $this->request->getPost('source_consultation_id'),
            'reason'                 => $this->request->getPost('reason'),
            'priority'               => $this->request->getPost('priority'),
        ]);

        if ($referralId) {
            // Notify counsellors
            $notifModel = new NotificationModel();
            $notifModel->createNotification(
                null, // broadcast
                'referral',
                'New Referral Received',
                'A new clinic-to-counselling referral has been submitted. Priority: ' . $this->request->getPost('priority'),
                'counselling',
                'referrals',
                $referralId
            );

            $auditModel = new AuditLogModel();
            $auditModel->logAction(session()->get('user_id'), 'create', 'clinic', 'referrals', $referralId);

            $consultId = $this->request->getPost('source_consultation_id');
            return redirect()->to("/clinic/consultations/{$consultId}")
                ->with('success', 'Referral sent to counselling.');
        }

        return redirect()->back()->withInput()->with('error', 'Failed to create referral.');
    }
}
