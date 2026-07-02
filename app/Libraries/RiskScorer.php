<?php

namespace App\Libraries;

use App\Models\AssessmentResponseModel;

class RiskScorer
{
    /**
     * Analyze a student's screening score history to detect trends and anomalies.
     */
    public function analyzeHistory(int $studentId, string $scoreType = 'phq9_trend'): array
    {
        $db = \Config\Database::connect();
        
        // 1. Fetch historical assessment scores for the student in chronological order
        // phq-9 (template id 1) or gad-7 (template id 2)
        $templateId = ($scoreType === 'phq9_trend') ? 1 : 2;

        $responses = $db->table('assessment_responses')
            ->where('student_id', $studentId)
            ->where('template_id', $templateId)
            ->orderBy('submitted_at', 'ASC')
            ->select('id, total_score, submitted_at')
            ->get()->getResultArray();

        $count = count($responses);
        if ($count === 0) {
            return [
                'student_id'             => $studentId,
                'score_type'             => $scoreType,
                'risk_level'             => 'low',
                'current_score'          => 0,
                'trend_slope'            => 0.0,
                'trend_direction'        => 'stable',
                'anomaly_detected'       => false,
                'anomaly_magnitude'      => 0.0,
                'data_points_used'       => 0,
                'projected_score'        => 0,
                'model_version'          => 'linear_regression_v1.0',
            ];
        }

        $scores = array_column($responses, 'total_score');
        $latestScore = (int) end($scores);
        $latestResponseId = (int) end($responses)['id'];

        $slope = 0.0;
        $anomalyDetected = false;
        $anomalyMagnitude = 0.0;

        // 2. Linear Regression Slope (if at least 2 data points)
        if ($count >= 2) {
            $x = range(0, $count - 1);
            $y = $scores;

            $sumX  = array_sum($x);
            $sumY  = array_sum($y);
            $sumXX = 0;
            $sumXY = 0;

            for ($i = 0; $i < $count; $i++) {
                $sumXX += $x[$i] * $x[$i];
                $sumXY += $x[$i] * $y[$i];
            }

            $denominator = ($count * $sumXX) - ($sumX * $sumX);
            if ($denominator != 0) {
                $slope = (($count * $sumXY) - ($sumX * $sumY)) / $denominator;
            }

            // 3. Anomaly Detection (distress spike)
            // Clinically, a change of >= 5 points is considered a significant anomaly.
            // Z-score calculation on the score differences
            $prevScore = (int) $scores[$count - 2];
            $diff = $latestScore - $prevScore;
            
            // If the latest score jumps by 5+ points, flag as distress anomaly
            if ($diff >= 5) {
                $anomalyDetected = true;
                
                // Estimate Z-score of jump based on typical historical standard deviation (approx 2.5)
                $anomalyMagnitude = round($diff / 2.5, 2);
            }
        }

        // 4. Trend Direction Classification
        $trendDirection = 'stable';
        if ($slope > 1.2) {
            $trendDirection = 'rapid_decline';
        } elseif ($slope > 0.3) {
            $trendDirection = 'worsening';
        } elseif ($slope < -0.3) {
            $trendDirection = 'improving';
        }

        // 5. Determine Risk Level
        // Low: < 5
        // Mild/Moderate: 5-9
        // Elevated: 10-14, or worsening trend
        // High: 15-19, or rapid decline
        // Critical: >= 20, or rapid decline with high score
        $riskLevel = 'low';
        if ($latestScore >= 20) {
            $riskLevel = 'critical';
        } elseif ($latestScore >= 15 || $trendDirection === 'rapid_decline') {
            $riskLevel = 'high';
        } elseif ($latestScore >= 10 || $trendDirection === 'worsening') {
            $riskLevel = 'elevated';
        } elseif ($latestScore >= 5) {
            $riskLevel = 'moderate';
        }

        // Project score 30 days out (assumes average 2 weeks between screenings, projecting 2 steps)
        $projectedScore = min(27, max(0, $latestScore + round($slope * 2)));

        return [
            'student_id'             => $studentId,
            'assessment_response_id' => $latestResponseId,
            'score_type'             => $scoreType,
            'risk_level'             => $riskLevel,
            'current_score'          => $latestScore,
            'trend_slope'            => round($slope, 4),
            'trend_direction'        => $trendDirection,
            'anomaly_detected'       => $anomalyDetected,
            'anomaly_magnitude'      => $anomalyMagnitude,
            'data_points_used'       => $count,
            'prediction_window_days' => 30,
            'projected_score'        => $projectedScore,
            'model_version'          => 'linear_regression_v1.0',
        ];
    }
}
