<?php

namespace App\Controllers\Counselling;

use App\Controllers\BaseController;
use App\Models\AssessmentTemplateModel;
use App\Models\AssessmentResponseModel;
use App\Models\AssessmentQuestionModel;
use App\Models\CrisisAlertModel;
use App\Models\ReferralModel;
use App\Models\NotificationModel;
use App\Models\StudentModel;

class ScreeningController extends BaseController
{
    /**
     * List available screenings.
     */
    public function index()
    {
        $templateModel = new AssessmentTemplateModel();
        $templates = $templateModel->getActive();

        return view('counselling/screenings/index', [
            'title'     => 'Screenings — SYNAPSE',
            'heading'   => 'Assessment & Screening Forms',
            'templates' => $templates,
        ]);
    }

    /**
     * Take a screening form.
     */
    public function take(int $templateId)
    {
        $templateModel = new AssessmentTemplateModel();
        $template = $templateModel->getWithQuestions($templateId);

        if ($template === null) {
            return redirect()->to('/counselling/screenings')->with('error', 'Screening form not found.');
        }

        $studentId    = $this->request->getGet('student_id');
        $appointmentId = $this->request->getGet('appointment_id');

        $student = null;
        if ($studentId) {
            $studentModel = new StudentModel();
            $student = $studentModel->getWithProfile((int) $studentId);
        }

        return view('counselling/screenings/take', [
            'title'         => "{$template['title']} — SYNAPSE",
            'heading'       => $template['title'],
            'template'      => $template,
            'student'       => $student,
            'appointmentId' => $appointmentId,
        ]);
    }

    /**
     * Submit screening responses.
     */
    public function submit()
    {
        $templateId    = (int) $this->request->getPost('template_id');
        $studentId     = (int) $this->request->getPost('student_id');
        $appointmentId = $this->request->getPost('appointment_id') ?: null;

        if (! $templateId || ! $studentId) {
            return redirect()->back()->with('error', 'Missing required fields.');
        }

        // Collect responses
        $templateModel = new AssessmentTemplateModel();
        $template = $templateModel->getWithQuestions($templateId);
        if ($template === null) {
            return redirect()->back()->with('error', 'Template not found.');
        }

        $responses = [];
        foreach ($template['questions'] as $q) {
            $responses[$q['id']] = $this->request->getPost('q_' . $q['id']);
        }

        $totalScore = AssessmentResponseModel::calculateTotalScore($responses);

        $responseModel = new AssessmentResponseModel();
        $responseId = $responseModel->submit([
            'template_id'    => $templateId,
            'student_id'     => $studentId,
            'appointment_id' => $appointmentId ? (int) $appointmentId : null,
            'responses'      => $responses,
            'total_score'    => $totalScore,
        ]);

        if (! $responseId) {
            return redirect()->back()->with('error', 'Failed to save responses.');
        }

        // === Crisis Check: PHQ-9 Item 9 (self-harm ideation) ===
        $isPHQ9 = stripos($template['title'], 'PHQ-9') !== false || stripos($template['title'], 'PHQ9') !== false;

        if ($isPHQ9) {
            // Find Item 9 (order_index = 8 for 0-indexed)
            $item9Value = 0;
            foreach ($template['questions'] as $q) {
                if ($q['order_index'] == 8) { // Item 9 is index 8
                    $item9Value = (int) ($responses[$q['id']] ?? 0);
                    break;
                }
            }

            if ($item9Value > 0) {
                // CRISIS PROTOCOL
                $crisisModel = new CrisisAlertModel();
                $crisisModel->createFromScreening(
                    $studentId,
                    $responseId,
                    'phq9_item9',
                    'critical'
                );
            }
        }

        // === Score Threshold Check (auto-referral if score >= 10) ===
        if ($totalScore >= 10) {
            $notifModel = new NotificationModel();
            $notifModel->createNotification(
                null,
                'screening_alert',
                'High Screening Score Alert',
                "Student screening score: {$totalScore}. Template: {$template['title']}. Review recommended.",
                'counselling',
                'assessment_responses',
                $responseId
            );
        }

        // === AI Risk Score Calculation & Trend Analysis ===
        $scoreType = $isPHQ9 ? 'phq9_trend' : (stripos($template['title'], 'GAD-7') !== false || stripos($template['title'], 'GAD7') !== false ? 'gad7_trend' : 'composite');
        
        $riskScorer = new \App\Libraries\RiskScorer();
        $riskResult = $riskScorer->analyzeHistory($studentId, $scoreType);

        if ($riskResult['data_points_used'] > 0) {
            $riskModel = new \App\Models\AiRiskScoreModel();
            $notifModel = new NotificationModel();

            $riskModel->insert([
                'student_id'             => $studentId,
                'assessment_response_id' => $responseId,
                'score_type'             => $scoreType,
                'risk_level'             => $riskResult['risk_level'],
                'current_score'          => $riskResult['current_score'],
                'trend_slope'            => $riskResult['trend_slope'],
                'trend_direction'        => $riskResult['trend_direction'],
                'anomaly_detected'       => $riskResult['anomaly_detected'] ? 1 : 0,
                'anomaly_magnitude'      => $riskResult['anomaly_magnitude'],
                'data_points_used'       => $riskResult['data_points_used'],
                'prediction_window_days' => 30,
                'projected_score'        => $riskResult['projected_score'],
                'model_version'          => $riskResult['model_version'],
                'counsellor_notified'    => ($riskResult['risk_level'] === 'critical' || $riskResult['anomaly_detected']) ? 1 : 0,
                'notified_at'            => ($riskResult['risk_level'] === 'critical' || $riskResult['anomaly_detected']) ? date('Y-m-d H:i:s') : null,
            ]);

            // Notify counsellor immediately if anomaly or critical risk is detected
            if ($riskResult['risk_level'] === 'critical' || $riskResult['anomaly_detected']) {
                $notifMsg = "AI Mental Health Alert: Student has a '" . strtoupper($riskResult['risk_level']) . "' risk trend (" . $riskResult['trend_direction'] . "). ";
                if ($riskResult['anomaly_detected']) {
                    $notifMsg .= "Anomalous score jump detected (magnitude: " . $riskResult['anomaly_magnitude'] . " SD)!";
                }
                $notifModel->createNotification(
                    null,
                    'screening_alert',
                    'AI Distress Trend Alert',
                    $notifMsg,
                    'counselling',
                    'ai_risk_scores',
                    $responseId
                );
            }
        }

        return redirect()->to("/counselling/screenings/results/{$responseId}")
            ->with('success', 'Screening submitted successfully.');
    }

    /**
     * Show screening results with interpretation.
     */
    public function results(int $responseId)
    {
        $responseModel = new AssessmentResponseModel();
        $response = $responseModel->getWithTemplate($responseId);

        if ($response === null) {
            return redirect()->to('/counselling/screenings')->with('error', 'Results not found.');
        }

        // Determine severity
        $severity = null;
        $isPHQ9 = stripos($response['template_title'], 'PHQ-9') !== false;
        $isGAD7 = stripos($response['template_title'], 'GAD-7') !== false;

        if ($isPHQ9) {
            $severity = AssessmentResponseModel::getPHQ9Severity((int) $response['total_score']);
        } elseif ($isGAD7) {
            $severity = AssessmentResponseModel::getGAD7Severity((int) $response['total_score']);
        }

        // Score history for trend
        $scoreHistory = $responseModel->getScoreHistory(
            (int) $response['student_id'],
            (int) $response['template_id']
        );

        // AI Risk Score
        $aiRiskModel = new \App\Models\AiRiskScoreModel();
        $aiRisk = $aiRiskModel->where('assessment_response_id', $responseId)->first();

        return view('counselling/screenings/results', [
            'title'        => 'Screening Results — SYNAPSE',
            'heading'      => 'Screening Results',
            'response'     => $response,
            'severity'     => $severity,
            'scoreHistory' => $scoreHistory,
            'aiRisk'       => $aiRisk,
        ]);
    }

    /**
     * Score history for a student.
     */
    public function history(int $studentId)
    {
        $studentModel = new StudentModel();
        $student = $studentModel->getWithProfile($studentId);

        if ($student === null) {
            return redirect()->to('/counselling')->with('error', 'Student not found.');
        }

        $responseModel = new AssessmentResponseModel();
        $responses = $responseModel->getByStudent($studentId, 20);

        return view('counselling/screenings/history', [
            'title'     => "Screening History — SYNAPSE",
            'heading'   => "Screening History: {$student['full_name']}",
            'student'   => $student,
            'responses' => $responses,
        ]);
    }
}
