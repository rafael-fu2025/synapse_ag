<?php

namespace App\Controllers;

class DashboardController extends BaseController
{
    /**
     * Main dashboard — redirects to role-specific dashboard.
     */
    public function index()
    {
        $primaryRole = session()->get('primary_role');

        return match ($primaryRole) {
            'admin'                => $this->admin(),
            'clinic_staff'         => $this->clinic(),
            'counsellor'           => $this->counsellor(),
            // 'pasimeo_coordinator' removed July 2026 — fall through to admin.
            'student'              => $this->student(),
            default                => $this->admin(),
        };
    }

    /**
     * Admin Dashboard.
     */
    public function admin()
    {
        $db = \Config\Database::connect();
        $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
        $today = date('Y-m-d');

        // Fetch Clinic summaries
        $totalClinic = $db->table('consultations')->where('consultation_date >=', $thirtyDaysAgo)->countAllResults(false);
        $triageHigh = $db->table('consultations')->where('consultation_date >=', $thirtyDaysAgo)->where('triage_priority', 'high')->countAllResults(false);
        $triageUrgent = $db->table('consultations')->where('consultation_date >=', $thirtyDaysAgo)->where('triage_priority', 'urgent')->countAllResults(false);
        $referrals = $db->table('referrals')->where('created_at >=', $thirtyDaysAgo)->countAllResults(false);
        $complaintRow = $db->table('consultations')->where('consultation_date >=', $thirtyDaysAgo)->select('chief_complaint, COUNT(id) as cnt')->groupBy('chief_complaint')->orderBy('cnt', 'DESC')->limit(1)->get()->getRowArray();
        
        $clinicData = [
            'total_consultations' => $totalClinic,
            'triage_high'         => $triageHigh,
            'triage_urgent'       => $triageUrgent,
            'referrals_count'     => $referrals,
            'top_complaint'       => $complaintRow ? $complaintRow['chief_complaint'] : 'general check-up',
        ];

        $summarizer = new \App\Libraries\ReportSummarizer();
        $clinicSummary = $summarizer->generateSummary('clinic', $thirtyDaysAgo, $today, $clinicData, null, session()->get('user_id'));

        // Fetch Counselling summaries
        $totalAppts = $db->table('counselling_appointments')->where('appointment_date >=', $thirtyDaysAgo)->countAllResults(false);
        $noShows = $db->table('counselling_appointments')->where('appointment_date >=', $thirtyDaysAgo)->where('status', 'no_show')->countAllResults(false);
        $crisisAlerts = $db->table('crisis_alerts')->where('created_at >=', $thirtyDaysAgo)->countAllResults(false);
        $severeScreenings = $db->table('assessment_responses')->where('submitted_at >=', $thirtyDaysAgo)->where('total_score >=', 15)->countAllResults(false);

        $counsellData = [
            'total_appointments'      => $totalAppts,
            'total_no_shows'          => $noShows,
            'crisis_alerts_count'     => $crisisAlerts,
            'severe_screenings_count' => $severeScreenings,
        ];
        $counsellingSummary = $summarizer->generateSummary('counselling', $thirtyDaysAgo, $today, $counsellData, null, session()->get('user_id'));

        // Dashboard stats
        $totalUsers = $db->table('users')->countAllResults();
        $consultationsToday = $db->table('consultations')->where('DATE(consultation_date)', date('Y-m-d'))->countAllResults();
        
        $startOfWeek = date('Y-m-d', strtotime('monday this week'));
        $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
        $appointmentsThisWeek = $db->table('counselling_appointments')
            ->where('appointment_date >=', $startOfWeek)
            ->where('appointment_date <=', $endOfWeek)
            ->countAllResults();

        $medicineModel = new \App\Models\MedicineModel();
        $lowStockMedicines = count($medicineModel->getLowStock());

        return view('dashboard/admin', [
            'title'                => 'Admin Dashboard — SYNAPSE',
            'heading'              => 'System Administration',
            'clinicSummary'        => $clinicSummary['summary_text'],
            'counsellingSummary'   => $counsellingSummary['summary_text'],
            'totalUsers'           => $totalUsers,
            'consultationsToday'   => $consultationsToday,
            'appointmentsThisWeek' => $appointmentsThisWeek,
            'lowStockMedicines'    => $lowStockMedicines,
        ]);
    }

    /**
     * Clinic Staff Dashboard.
     */
    public function clinic()
    {
        $db = \Config\Database::connect();
        $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
        $today = date('Y-m-d');

        $totalClinic = $db->table('consultations')->where('consultation_date >=', $thirtyDaysAgo)->countAllResults(false);
        $triageHigh = $db->table('consultations')->where('consultation_date >=', $thirtyDaysAgo)->where('triage_priority', 'high')->countAllResults(false);
        $triageUrgent = $db->table('consultations')->where('consultation_date >=', $thirtyDaysAgo)->where('triage_priority', 'urgent')->countAllResults(false);
        $referrals = $db->table('referrals')->where('created_at >=', $thirtyDaysAgo)->countAllResults(false);
        $complaintRow = $db->table('consultations')->where('consultation_date >=', $thirtyDaysAgo)->select('chief_complaint, COUNT(id) as cnt')->groupBy('chief_complaint')->orderBy('cnt', 'DESC')->limit(1)->get()->getRowArray();
        
        $clinicData = [
            'total_consultations' => $totalClinic,
            'triage_high'         => $triageHigh,
            'triage_urgent'       => $triageUrgent,
            'referrals_count'     => $referrals,
            'top_complaint'       => $complaintRow ? $complaintRow['chief_complaint'] : 'general check-up',
        ];

        $summarizer = new \App\Libraries\ReportSummarizer();
        $summary = $summarizer->generateSummary('clinic', $thirtyDaysAgo, $today, $clinicData, null, session()->get('user_id'));

        // Dashboard stats
        $consultationModel = new \App\Models\ConsultationModel();
        $todayStats = $consultationModel->getTodayStats();
        
        $medicineModel = new \App\Models\MedicineModel();
        $lowStockAlerts = count($medicineModel->getLowStock());

        return view('dashboard/clinic', [
            'title'             => 'Clinic Dashboard — SYNAPSE',
            'heading'           => 'Clinic Management',
            'aiSummary'         => $summary['summary_text'],
            'patientsToday'     => $todayStats['total'],
            'completedConsults' => $todayStats['completed'],
            'inProgress'        => $todayStats['in_progress'],
            'lowStockAlerts'    => $lowStockAlerts,
        ]);
    }

    /**
     * Counsellor Dashboard.
     */
    public function counsellor()
    {
        $db = \Config\Database::connect();
        $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
        $today = date('Y-m-d');

        $totalAppts = $db->table('counselling_appointments')->where('appointment_date >=', $thirtyDaysAgo)->countAllResults(false);
        $noShows = $db->table('counselling_appointments')->where('appointment_date >=', $thirtyDaysAgo)->where('status', 'no_show')->countAllResults(false);
        $crisisAlerts = $db->table('crisis_alerts')->where('created_at >=', $thirtyDaysAgo)->countAllResults(false);
        $severeScreenings = $db->table('assessment_responses')->where('submitted_at >=', $thirtyDaysAgo)->where('total_score >=', 15)->countAllResults(false);

        $counsellData = [
            'total_appointments'      => $totalAppts,
            'total_no_shows'          => $noShows,
            'crisis_alerts_count'     => $crisisAlerts,
            'severe_screenings_count' => $severeScreenings,
        ];

        $summarizer = new \App\Libraries\ReportSummarizer();
        $summary = $summarizer->generateSummary('counselling', $thirtyDaysAgo, $today, $counsellData, null, session()->get('user_id'));

        // Dashboard stats
        $counsellorId = session()->get('user_id');
        $appointmentModel = new \App\Models\CounsellingAppointmentModel();
        $todayStats = $appointmentModel->getTodayStats($counsellorId);

        $crisisAlertModel = new \App\Models\CrisisAlertModel();
        $crisisAlertsCount = count($crisisAlertModel->getActive());

        $referralModel = new \App\Models\ReferralModel();
        $pendingReferrals = count($referralModel->getPending('clinic_to_counselling'));

        $activeCaseload = $db->table('counselling_appointments')
            ->select('COUNT(DISTINCT student_id) as count')
            ->where('counsellor_id', $counsellorId)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->get()->getRowArray()['count'] ?? 0;

        return view('dashboard/counsellor', [
            'title'             => 'Counselling Dashboard — SYNAPSE',
            'heading'           => 'Counselling Management',
            'aiSummary'         => $summary['summary_text'],
            'appointmentsToday' => $todayStats['total'],
            'crisisAlerts'      => $crisisAlertsCount,
            'pendingReferrals'  => $pendingReferrals,
            'activeCaseload'    => $activeCaseload,
        ]);
    }

    /**
     * Student Dashboard.
     */
    public function student()
    {
        $db = \Config\Database::connect();
        $userId = session()->get('user_id');
        $student = $db->table('students')->where('user_id', $userId)->get()->getRowArray();

        $stats = [
            'appointments'  => 0,
            'consultations' => 0,
            'hours'         => 0,
        ];
        $upcoming = [];
        $templates = [];
        $activeQueue = null;   // today's in-clinic queue row (if any)
        $queueAhead  = 0;      // how many people are ahead of them

        if ($student) {
            $stats['appointments'] = $db->table('counselling_appointments')
                ->where('student_id', $student['id'])
                ->countAllResults();

            $stats['consultations'] = $db->table('consultations')
                ->where('student_id', $student['id'])
                ->countAllResults();

            $hoursRow = $db->table('outreach_attendance')
                ->where('user_id', $userId)
                ->selectSum('hours_credited')
                ->get()->getRowArray();
            $stats['hours'] = (float) ($hoursRow['hours_credited'] ?? 0);

            // Fetch upcoming appointments with counsellor names
            $upcoming = $db->table('counselling_appointments')
                ->select('counselling_appointments.*, users.first_name, users.last_name')
                ->join('users', 'users.id = counselling_appointments.counsellor_id')
                ->where('student_id', $student['id'])
                ->where('appointment_date >=', date('Y-m-d'))
                ->whereIn('status', ['scheduled', 'confirmed'])
                ->orderBy('appointment_date', 'ASC')
                ->orderBy('start_time', 'ASC')
                ->get()->getResultArray();

            $templates = $db->table('assessment_templates')
                ->where('is_active', true)
                ->get()->getResultArray();

            /* Live queue banner — if this student has an active
               consultation today (waiting / called / in_session), show
               their number and how many people are ahead of them.
               Uses MySQL CURDATE() so it matches the kiosk's day
               regardless of PHP's timezone. */
            $activeQueue = $db->table('consultations')
                ->where('student_id', $student['id'])
                ->where('DATE(consultation_date) = CURDATE()', null, false)
                ->whereIn('status', ['in_progress', 'called', 'in_session'])
                ->orderBy('id', 'DESC')
                ->get()
                ->getRowArray();

            if ($activeQueue && $activeQueue['status'] === 'in_progress' && $activeQueue['queue_position'] !== null) {
                /* People ahead = everyone with a smaller queue_position
                   AND status = in_progress. (Called/in_session are
                   already being seen or about to be.) */
                $queueAhead = $db->table('consultations')
                    ->where('DATE(consultation_date) = CURDATE()', null, false)
                    ->where('status', 'in_progress')
                    ->where('queue_position <', (int) $activeQueue['queue_position'])
                    ->countAllResults();
            }
        }

        return view('dashboard/student', [
            'title'       => 'Student Portal — SYNAPSE',
            'heading'     => 'Student Portal',
            'student'     => $student,
            'stats'       => $stats,
            'upcoming'    => $upcoming,
            'templates'   => $templates,
            'activeQueue' => $activeQueue,
            'queueAhead'  => $queueAhead,
        ]);
    }
}
