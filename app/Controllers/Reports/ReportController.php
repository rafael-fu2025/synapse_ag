<?php

namespace App\Controllers\Reports;

use App\Controllers\BaseController;
use App\Libraries\ReportSummarizer;

/**
 * Reports & Analytics Controller.
 *
 * Renders module-level analytics dashboards and provides CSV export.
 * All read-only queries are grouped under date-range filters.
 *
 * Schema references (from Database/synapse_ag.sql):
 *   consultations(consultation_date, status, triage_priority, chief_complaint, attending_user_id, student_id)
 *   counselling_appointments(appointment_date, status, type, counsellor_id, student_id, no_show_probability)
 *   crisis_alerts(created_at, status, severity, trigger_source, assigned_counsellor_id)
 *   assessment_responses(submitted_at, total_score, template_id, student_id)
 *   referrals(created_at, direction, status, priority)
 *   medicines(reorder_threshold, is_active, generic_name, brand_name, unit, category)
 *   medicine_batches(quantity_remaining, expiration_date, status, medicine_id)
 *   inventory_transactions(transaction_date, transaction_type, quantity, medicine_batch_id)
 */
class ReportController extends BaseController
{
    /**
     * Validated date range used by every analytics method.
     *
     * @return array{start:string,end:string}
     */
    private function getDateRange(): array
    {
        $end   = $this->request->getGet('end')   ?? date('Y-m-d');
        $start = $this->request->getGet('start') ?? date('Y-m-d', strtotime('-30 days', strtotime($end)));

        // Clamp / sanitize — reject anything that isn't YYYY-MM-DD
        $startValid = preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) === 1 ? $start : date('Y-m-d', strtotime('-30 days'));
        $endValid   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)   === 1 ? $end   : date('Y-m-d');

        // Ensure start <= end
        if (strtotime($startValid) > strtotime($endValid)) {
            [$startValid, $endValid] = [$endValid, $startValid];
        }

        return ['start' => $startValid, 'end' => $endValid];
    }

    // ----------------------------------------------------------------
    // Landing — module picker
    // ----------------------------------------------------------------

    public function index()
    {
        $range = $this->getDateRange();
        $db    = \Config\Database::connect();

        // Module-level at-a-glance counts (single grouped query each — light)
        $modules = [
            'clinic' => [
                'icon'        => 'fa-stethoscope',
                'label'       => 'Clinic Operations',
                'description' => 'Consultations, triage priority, chief complaints, referrals.',
                'kpi'         => (int) $db->table('consultations')
                    ->where('consultation_date >=', $range['start'] . ' 00:00:00')
                    ->where('consultation_date <=', $range['end']   . ' 23:59:59')
                    ->countAllResults(),
                'url'         => base_url('reports/clinic'),
            ],
            'counselling' => [
                'icon'        => 'fa-hand-holding-heart',
                'label'       => 'Counselling Services',
                'description' => 'Appointments, no-show trends, screening severity, crisis alerts.',
                'kpi'         => (int) $db->table('counselling_appointments')
                    ->where('appointment_date >=', $range['start'])
                    ->where('appointment_date <=', $range['end'])
                    ->countAllResults(),
                'url'         => base_url('reports/counselling'),
            ],
            'inventory' => [
                'icon'        => 'fa-pills',
                'label'       => 'Inventory',
                'description' => 'Stock levels, low-stock items, expiring batches, dispensing trends.',
                'kpi'         => (int) $db->table('medicine_batches')
                    ->where('status', 'active')
                    ->where('quantity_remaining >', 0)
                    ->countAllResults(),
                'url'         => base_url('reports/inventory'),
            ],
        ];

        return view('reports/index', [
            'title'   => 'Reports & Analytics — SYNAPSE',
            'heading' => 'Reports & Analytics',
            'modules' => $modules,
            'range'   => $range,
        ]);
    }

    // ----------------------------------------------------------------
    // Clinic analytics
    // ----------------------------------------------------------------

    public function clinic()
    {
        $range    = $this->getDateRange();
        $db       = \Config\Database::connect();
        $startDT  = $range['start'] . ' 00:00:00';
        $endDT    = $range['end']   . ' 23:59:59';

        // KPIs
        $totalConsultations = (int) $db->table('consultations')
            ->where('consultation_date >=', $startDT)
            ->where('consultation_date <=', $endDT)
            ->countAllResults();

        $triageBreakdown = $db->table('consultations')
            ->select('COALESCE(triage_priority, "unset") AS priority, COUNT(*) AS cnt', false)
            ->where('consultation_date >=', $startDT)
            ->where('consultation_date <=', $endDT)
            ->groupBy('priority')
            ->orderBy('cnt', 'DESC')
            ->get()->getResultArray();

        $statusBreakdown = $db->table('consultations')
            ->select('status, COUNT(*) AS cnt')
            ->where('consultation_date >=', $startDT)
            ->where('consultation_date <=', $endDT)
            ->groupBy('status')
            ->get()->getResultArray();

        // Time-series: consultations per day (within range, gap-fill in view)
        $dailyTrend = $db->table('consultations')
            ->select('DATE(consultation_date) AS day, COUNT(*) AS cnt', false)
            ->where('consultation_date >=', $startDT)
            ->where('consultation_date <=', $endDT)
            ->groupBy('day')
            ->orderBy('day', 'ASC')
            ->get()->getResultArray();

        // Top chief complaints (top 8)
        $topComplaints = $db->table('consultations')
            ->select('chief_complaint, COUNT(*) AS cnt')
            ->where('consultation_date >=', $startDT)
            ->where('consultation_date <=', $endDT)
            ->where('chief_complaint IS NOT NULL')
            ->groupBy('chief_complaint')
            ->orderBy('cnt', 'DESC')
            ->limit(8)
            ->get()->getResultArray();

        // Referrals outgoing
        $referralsCount = (int) $db->table('referrals')
            ->where('created_at >=', $startDT)
            ->where('created_at <=', $endDT)
            ->countAllResults();

        $referralsByDirection = $db->table('referrals')
            ->select('direction, COUNT(*) AS cnt')
            ->where('created_at >=', $startDT)
            ->where('created_at <=', $endDT)
            ->groupBy('direction')
            ->get()->getResultArray();

        // AI narrative summary (existing library)
        $topComplaint = $topComplaints[0]['chief_complaint'] ?? 'general check-up';
        $triageHigh   = 0;
        $triageUrgent = 0;
        foreach ($triageBreakdown as $t) {
            if ($t['priority'] === 'high')   $triageHigh   = (int) $t['cnt'];
            if ($t['priority'] === 'urgent') $triageUrgent = (int) $t['cnt'];
        }

        $summarizer = new ReportSummarizer();
        $aiSummary = $summarizer->generateSummary('clinic', $range['start'], $range['end'], [
            'total_consultations' => $totalConsultations,
            'triage_high'         => $triageHigh,
            'triage_urgent'       => $triageUrgent,
            'referrals_count'     => $referralsCount,
            'top_complaint'       => $topComplaint,
        ], null, session()->get('user_id'));

        return view('reports/clinic', [
            'title'               => 'Clinic Analytics — SYNAPSE',
            'heading'             => 'Clinic Operations',
            'range'               => $range,
            'totalConsultations'  => $totalConsultations,
            'triageBreakdown'     => $triageBreakdown,
            'statusBreakdown'     => $statusBreakdown,
            'dailyTrend'          => $dailyTrend,
            'topComplaints'       => $topComplaints,
            'referralsCount'      => $referralsCount,
            'referralsByDirection'=> $referralsByDirection,
            'aiSummary'           => $aiSummary['summary_text'],
            'module'              => 'clinic',
        ]);
    }

    // ----------------------------------------------------------------
    // Counselling analytics
    // ----------------------------------------------------------------

    public function counselling()
    {
        $range   = $this->getDateRange();
        $db      = \Config\Database::connect();

        // KPIs
        $totalAppts   = (int) $db->table('counselling_appointments')
            ->where('appointment_date >=', $range['start'])
            ->where('appointment_date <=', $range['end'])
            ->countAllResults();

        $statusBreakdown = $db->table('counselling_appointments')
            ->select('status, COUNT(*) AS cnt')
            ->where('appointment_date >=', $range['start'])
            ->where('appointment_date <=', $range['end'])
            ->groupBy('status')
            ->get()->getResultArray();

        $typeBreakdown = $db->table('counselling_appointments')
            ->select('type, COUNT(*) AS cnt')
            ->where('appointment_date >=', $range['start'])
            ->where('appointment_date <=', $range['end'])
            ->groupBy('type')
            ->get()->getResultArray();

        $dailyTrend = $db->table('counselling_appointments')
            ->select('appointment_date AS day, COUNT(*) AS cnt', false)
            ->where('appointment_date >=', $range['start'])
            ->where('appointment_date <=', $range['end'])
            ->groupBy('day')
            ->orderBy('day', 'ASC')
            ->get()->getResultArray();

        // No-show metrics
        $noShowCount = 0;
        foreach ($statusBreakdown as $s) {
            if ($s['status'] === 'no_show') $noShowCount = (int) $s['cnt'];
        }
        $noShowRate = $totalAppts > 0 ? round(($noShowCount / $totalAppts) * 100, 1) : 0.0;

        // Crisis alerts within range
        $crisisAlerts = (int) $db->table('crisis_alerts')
            ->where('created_at >=', $range['start'] . ' 00:00:00')
            ->where('created_at <=', $range['end']   . ' 23:59:59')
            ->countAllResults();

        $crisisBySeverity = $db->table('crisis_alerts')
            ->select('severity, COUNT(*) AS cnt')
            ->where('created_at >=', $range['start'] . ' 00:00:00')
            ->where('created_at <=', $range['end']   . ' 23:59:59')
            ->groupBy('severity')
            ->get()->getResultArray();

        // Severe screenings (PHQ-9 / GAD-7 with total_score >= 15)
        $severeScreenings = (int) $db->table('assessment_responses')
            ->where('submitted_at >=', $range['start'] . ' 00:00:00')
            ->where('submitted_at <=', $range['end']   . ' 23:59:59')
            ->where('total_score >=', 15)
            ->countAllResults();

        // AI narrative summary
        $summarizer = new ReportSummarizer();
        $aiSummary = $summarizer->generateSummary('counselling', $range['start'], $range['end'], [
            'total_appointments'      => $totalAppts,
            'total_no_shows'          => $noShowCount,
            'crisis_alerts_count'     => $crisisAlerts,
            'severe_screenings_count' => $severeScreenings,
        ], null, session()->get('user_id'));

        return view('reports/counselling', [
            'title'            => 'Counselling Analytics — SYNAPSE',
            'heading'          => 'Counselling Services',
            'range'            => $range,
            'totalAppts'       => $totalAppts,
            'noShowCount'      => $noShowCount,
            'noShowRate'       => $noShowRate,
            'statusBreakdown'  => $statusBreakdown,
            'typeBreakdown'    => $typeBreakdown,
            'dailyTrend'       => $dailyTrend,
            'crisisAlerts'     => $crisisAlerts,
            'crisisBySeverity' => $crisisBySeverity,
            'severeScreenings' => $severeScreenings,
            'aiSummary'        => $aiSummary['summary_text'],
            'module'           => 'counselling',
        ]);
    }

    // ----------------------------------------------------------------
    // Inventory analytics
    // ----------------------------------------------------------------

    public function inventory()
    {
        $range   = $this->getDateRange();
        $db      = \Config\Database::connect();

        // Active stock snapshot
        $totalMedicines = (int) $db->table('medicines')->where('is_active', true)->countAllResults();

        $lowStock = $db->table('medicines m')
            ->select('m.generic_name, m.brand_name, m.unit, m.reorder_threshold, COALESCE(SUM(mb.quantity_remaining), 0) AS total_stock', false)
            ->join('medicine_batches mb', "mb.medicine_id = m.id AND mb.status = 'active'", 'left')
            ->where('m.is_active', true)
            ->groupBy('m.id')
            ->having('total_stock <= m.reorder_threshold')
            ->orderBy('total_stock', 'ASC')
            ->get()->getResultArray();

        // Expiring within 90 days (snapshot, not date-filtered)
        $futureDate = date('Y-m-d', strtotime('+90 days'));
        $expiring = $db->table('medicine_batches mb')
            ->select('mb.*, m.generic_name, m.brand_name, m.unit')
            ->join('medicines m', 'm.id = mb.medicine_id')
            ->where('mb.status', 'active')
            ->where('mb.quantity_remaining >', 0)
            ->where('mb.expiration_date <=', $futureDate)
            ->orderBy('mb.expiration_date', 'ASC')
            ->get()->getResultArray();

        // Dispensing trend by day (within range)
        $dispensingTrend = $db->table('inventory_transactions')
            ->select('DATE(transaction_date) AS day, SUM(quantity) AS qty', false)
            ->where('transaction_type', 'dispensed')
            ->where('transaction_date >=', $range['start'] . ' 00:00:00')
            ->where('transaction_date <=', $range['end']   . ' 23:59:59')
            ->groupBy('day')
            ->orderBy('day', 'ASC')
            ->get()->getResultArray();

        $totalDispensed = 0;
        foreach ($dispensingTrend as $d) {
            $totalDispensed += (int) $d['qty'];
        }

        // Top medicines dispensed
        $topDispensed = $db->table('inventory_transactions t')
            ->select('m.generic_name, m.brand_name, m.unit, SUM(t.quantity) AS qty', false)
            ->join('medicine_batches mb', 'mb.id = t.medicine_batch_id')
            ->join('medicines m', 'm.id = mb.medicine_id')
            ->where('t.transaction_type', 'dispensed')
            ->where('t.transaction_date >=', $range['start'] . ' 00:00:00')
            ->where('t.transaction_date <=', $range['end']   . ' 23:59:59')
            ->groupBy('m.id')
            ->orderBy('qty', 'DESC')
            ->limit(8)
            ->get()->getResultArray();

        // Category breakdown (active medicines)
        $categoryBreakdown = $db->table('medicines')
            ->select('COALESCE(category, "uncategorized") AS category, COUNT(*) AS cnt', false)
            ->where('is_active', true)
            ->groupBy('category')
            ->orderBy('cnt', 'DESC')
            ->get()->getResultArray();

        // AI narrative
        $summarizer = new ReportSummarizer();
        $aiSummary = $summarizer->generateSummary('inventory', $range['start'], $range['end'], [
            'total_medicines'        => $totalMedicines,
            'low_stock_count'        => count($lowStock),
            'expiring_batches_count' => count($expiring),
            'total_dispensed'        => $totalDispensed,
        ], null, session()->get('user_id'));

        return view('reports/inventory', [
            'title'             => 'Inventory Analytics — SYNAPSE',
            'heading'           => 'Medicine Inventory',
            'range'             => $range,
            'totalMedicines'    => $totalMedicines,
            'lowStock'          => $lowStock,
            'expiring'          => $expiring,
            'dispensingTrend'   => $dispensingTrend,
            'totalDispensed'    => $totalDispensed,
            'topDispensed'      => $topDispensed,
            'categoryBreakdown' => $categoryBreakdown,
            'aiSummary'         => $aiSummary['summary_text'],
            'module'            => 'inventory',
        ]);
    }

    // ----------------------------------------------------------------
    // CSV export (per module)
    // ----------------------------------------------------------------

    public function export(string $module)
    {
        $allowed = ['clinic', 'counselling', 'inventory'];
        if (! in_array($module, $allowed, true)) {
            return $this->response->setStatusCode(404)->setBody('Unknown report module.');
        }

        $range = $this->getDateRange();
        $db    = \Config\Database::connect();

        // Resolve rows + headers per module
        [$headers, $rows, $filename] = match ($module) {
            'clinic' => $this->exportClinic($db, $range),
            'counselling' => $this->exportCounselling($db, $range),
            'inventory' => $this->exportInventory($db, $range),
        };

        // Audit log
        $audit = new \App\Models\AuditLogModel();
        $audit->logAction(
            (int) session()->get('user_id'),
            'export',
            'reports',
            $module . '_export',
            null,
            null,
            ['range' => $range, 'row_count' => count($rows)]
        );

        // Build CSV in memory
        $handle = fopen('php://temp', 'r+');
        // BOM for Excel UTF-8 compatibility
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, array_map(
                static fn($v) => is_array($v) ? json_encode($v) : (string) ($v ?? ''),
                $row
            ));
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'no-store, no-cache')
            ->setBody($csv);
    }

    private function exportClinic($db, array $range): array
    {
        $rows = $db->table('consultations c')
            ->select('c.consultation_date, c.status, COALESCE(c.triage_priority, "") AS triage_priority, c.chief_complaint, COALESCE(c.diagnosis, "") AS diagnosis, u.first_name AS staff_first, u.last_name AS staff_last, s.student_number', false)
            ->join('users u', 'u.id = c.attending_user_id')
            ->join('students s', 's.id = c.student_id')
            ->where('c.consultation_date >=', $range['start'] . ' 00:00:00')
            ->where('c.consultation_date <=', $range['end']   . ' 23:59:59')
            ->orderBy('c.consultation_date', 'ASC')
            ->get()->getResultArray();

        $headers = ['Date', 'Status', 'Triage', 'Chief Complaint', 'Diagnosis', 'Attending Staff', 'Student #'];
        $filename = 'synapse_clinic_' . $range['start'] . '_to_' . $range['end'] . '.csv';

        return [$headers, $rows, $filename];
    }

    private function exportCounselling($db, array $range): array
    {
        $rows = $db->table('counselling_appointments a')
            ->select('a.appointment_date, a.start_time, a.end_time, a.type, a.status, COALESCE(a.reason, "") AS reason, u_couns.first_name AS counsellor_first, u_couns.last_name AS counsellor_last, s.student_number', false)
            ->join('users u_couns', 'u_couns.id = a.counsellor_id')
            ->join('students s', 's.id = a.student_id')
            ->where('a.appointment_date >=', $range['start'])
            ->where('a.appointment_date <=', $range['end'])
            ->orderBy('a.appointment_date', 'ASC')
            ->orderBy('a.start_time', 'ASC')
            ->get()->getResultArray();

        $headers = ['Date', 'Start', 'End', 'Type', 'Status', 'Reason', 'Counsellor', 'Student #'];
        $filename = 'synapse_counselling_' . $range['start'] . '_to_' . $range['end'] . '.csv';

        return [$headers, $rows, $filename];
    }

    private function exportInventory($db, array $range): array
    {
        $rows = $db->table('inventory_transactions t')
            ->select('t.transaction_date, t.transaction_type, t.quantity, COALESCE(t.notes, "") AS notes, mb.batch_number, m.generic_name, m.brand_name, m.unit, u.first_name, u.last_name', false)
            ->join('medicine_batches mb', 'mb.id = t.medicine_batch_id')
            ->join('medicines m', 'm.id = mb.medicine_id')
            ->join('users u', 'u.id = t.performed_by')
            ->where('t.transaction_date >=', $range['start'] . ' 00:00:00')
            ->where('t.transaction_date <=', $range['end']   . ' 23:59:59')
            ->orderBy('t.transaction_date', 'ASC')
            ->get()->getResultArray();

        $headers = ['Date', 'Type', 'Quantity', 'Notes', 'Batch', 'Generic Name', 'Brand', 'Unit', 'Performed By'];
        $filename = 'synapse_inventory_' . $range['start'] . '_to_' . $range['end'] . '.csv';

        return [$headers, $rows, $filename];
    }
}