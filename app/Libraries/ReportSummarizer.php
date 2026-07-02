<?php

namespace App\Libraries;

use App\Models\AiGeneratedSummaryModel;

class ReportSummarizer
{
    /**
     * Generate narrative natural language summary for a report.
     */
    public function generateSummary(string $module, string $start, string $end, array $data, ?int $reportId = null, ?int $userId = null): array
    {
        $narrative = '';
        $startStr = date('M d, Y', strtotime($start));
        $endStr = date('M d, Y', strtotime($end));

        if ($module === 'clinic') {
            $total = (int) ($data['total_consultations'] ?? 0);
            $triageHigh = (int) ($data['triage_high'] ?? 0);
            $triageUrgent = (int) ($data['triage_urgent'] ?? 0);
            $referrals = (int) ($data['referrals_count'] ?? 0);
            $topComplaint = $data['top_complaint'] ?? 'general check-up';

            // topComplaint comes from the DB (consultations.chief_complaint) and
            // is rendered into HTML by callers. Anything we interpolate into the
            // narrative MUST be safe to emit as HTML, so coerce it to plain text
            // first.
            // NOTE: never write the literal sequence "? >" (no space) or
            // "< ?=" inside a single-line comment, even one that begins with
            // the PHP // sigil. The PHP parser does not recognise the // until
            // it is already in PHP mode, so the HTML parser sees the close-tag
            // and ends the PHP block, triggering a parse error.
            $topComplaintSafe = trim(strip_tags((string) $topComplaint));
            if ($topComplaintSafe === '') {
                $topComplaintSafe = 'general check-up';
            }
            // Cap length so a malicious row can't blast the dashboard with text.
            if (mb_strlen($topComplaintSafe) > 80) {
                $topComplaintSafe = mb_substr($topComplaintSafe, 0, 77) . '...';
            }
            $topComplaintHtml = htmlspecialchars($topComplaintSafe, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $narrative = "During the period from {$startStr} to {$endStr}, the clinic processed a total of **{$total} consultations**. ";
            if ($total > 0) {
                $criticalPct = round((($triageHigh + $triageUrgent) / $total) * 100, 1);
                $narrative .= "Out of these, {$criticalPct}% were classified as high-priority or urgent cases, requiring immediate clinical attention. ";
                $narrative .= "The most frequent reason for patient check-in was **{$topComplaintHtml}**. ";
                if ($referrals > 0) {
                    $narrative .= "In addition, **{$referrals} cases** were referred to counselling services, demonstrating active collaboration between the clinic and mental health departments. ";
                } else {
                    $narrative .= "No critical referrals to counselling were required during this interval. ";
                }
            } else {
                $narrative .= "No clinical consultations were recorded during this period.";
            }

        } elseif ($module === 'counselling') {
            $totalAppts = $data['total_appointments'] ?? 0;
            $noShows = $data['total_no_shows'] ?? 0;
            $crisisAlerts = $data['crisis_alerts_count'] ?? 0;
            $severeScreenings = $data['severe_screenings_count'] ?? 0;

            $narrative = "Counselling services managed **{$totalAppts} appointments** between {$startStr} and {$endStr}. ";
            if ($totalAppts > 0) {
                $noShowPct = round(($noShows / $totalAppts) * 100, 1);
                $narrative .= "The system logged a **{$noShowPct}% no-show rate**, highlighting a key area for scheduling optimization. ";
                if ($severeScreenings > 0) {
                    $narrative .= "Screening tools flagged **{$severeScreenings} instances** of severe anxiety or depression scores. ";
                }
                if ($crisisAlerts > 0) {
                    $narrative .= "Crucially, the system triggered **{$crisisAlerts} critical crisis alerts** due to positive flags on suicide ideation. The 30-minute counsellor response protocol was initiated for all triggered alerts.";
                } else {
                    $narrative .= "No crisis alerts or suicide risk flags were triggered during this assessment window.";
                }
            } else {
                $narrative .= "No counselling sessions were scheduled in this date range.";
            }

        } elseif ($module === 'inventory') {
            $totalItems = $data['total_medicines'] ?? 0;
            $lowStock = $data['low_stock_count'] ?? 0;
            $expiring = $data['expiring_batches_count'] ?? 0;
            $dispensed = $data['total_dispensed'] ?? 0;

            $narrative = "The medical inventory report for {$startStr} to {$endStr} indicates **{$dispensed} medicine items** were dispensed. ";
            if ($lowStock > 0 || $expiring > 0) {
                $narrative .= "Currently, **{$lowStock} medicines** are below their configured reorder thresholds and require replenishment. ";
                if ($expiring > 0) {
                    $narrative .= "Additionally, **{$expiring} batches** are expiring within 90 days. Staff should prioritize dispensing these batches under FEFO guidelines to minimize waste.";
                }
            } else {
                $narrative .= "Inventory levels remain healthy with no low-stock alerts or near-expiry batches detected.";
            }
        } else {
            $moduleSafe  = htmlspecialchars((string) $module, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $narrative = "Report generated for module {$moduleSafe} covering {$startStr} to {$endStr}.";
        }

        $summaryModel = new AiGeneratedSummaryModel();
        $summaryModel->insert([
            'report_id'         => $reportId,
            'module'            => $module,
            'period_start'      => $start,
            'period_end'        => $end,
            'input_data'        => json_encode($data),
            'generated_summary' => $narrative,
            'generation_method' => 'template_nlg',
            'model_used'        => 'template_nlg_v2.0',
            'generated_by'      => $userId,
        ]);

        return [
            'summary_text'      => $narrative,
            'generation_method' => 'template_nlg',
            'model_used'        => 'template_nlg_v2.0',
            'created_at'        => date('Y-m-d H:i:s')
        ];
    }
}
