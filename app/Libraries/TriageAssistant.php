<?php

namespace App\Libraries;

use App\Models\AllergyModel;

class TriageAssistant
{
    private array $urgentKeywords = [
        'suicide', 'self-harm', 'chest pain', 'breathing', 'unconscious', 'poison',
        'active bleeding', 'stroke', 'seizure', 'heart attack', 'choking', 'anaphylaxis'
    ];

    private array $highKeywords = [
        'fever', 'severe pain', 'asthma', 'fracture', 'dizzy', 'vomiting',
        'bleeding', 'hypertension', 'abdominal pain', 'migraine', 'burn'
    ];

    private array $mediumKeywords = [
        'cough', 'cold', 'headache', 'mild pain', 'diarrhea', 'nausea', 'flu',
        'sprain', 'sore throat', 'allergy', 'rash', 'earache', 'constipation'
    ];

    private array $lowKeywords = [
        'refill', 'medical certificate', 'check-up', 'consultation', 'wound clean',
        'stitch removal', 'vaccine', 'clearance', 'prescription', 'general'
    ];

    /**
     * Run smart triage analysis on chief complaint, vitals, and allergy history.
     */
    public function analyze(string $complaint, ?array $vitals = null, ?array $allergies = null): array
    {
        $complaintLower = strtolower($complaint);
        
        // 1. Initial priority based on keyword matching
        $priority = 'low';
        $confidence = 0.60;
        $matches = 0;

        foreach ($this->urgentKeywords as $word) {
            if (str_contains($complaintLower, $word)) {
                $priority = 'urgent';
                $confidence = 0.90;
                $matches++;
            }
        }

        if ($priority !== 'urgent') {
            foreach ($this->highKeywords as $word) {
                if (str_contains($complaintLower, $word)) {
                    $priority = 'high';
                    $confidence = 0.85;
                    $matches++;
                }
            }
        }

        if ($priority === 'low') {
            foreach ($this->mediumKeywords as $word) {
                if (str_contains($complaintLower, $word)) {
                    $priority = 'medium';
                    $confidence = 0.75;
                    $matches++;
                }
            }
        }

        if ($priority === 'low') {
            foreach ($this->lowKeywords as $word) {
                if (str_contains($complaintLower, $word)) {
                    $priority = 'low';
                    $confidence = 0.80;
                    $matches++;
                }
            }
        }

        // Adjust confidence slightly based on number of keyword matches
        if ($matches > 1) {
            $confidence = min(0.98, $confidence + ($matches * 0.02));
        }

        $featuresUsed = [
            'keyword_matches' => $matches,
            'vitals_triggered' => false,
            'allergy_triggered' => false
        ];

        // 2. Vitals-based escalation
        if ($vitals) {
            $escalated = false;
            // High Temperature (Fever)
            if (isset($vitals['temperature']) && $vitals['temperature'] >= 38.5) {
                $escalated = true;
                $featuresUsed['high_temp'] = $vitals['temperature'];
            }
            // Extreme Heart Rate
            if (isset($vitals['heart_rate']) && ($vitals['heart_rate'] > 120 || $vitals['heart_rate'] < 50)) {
                $escalated = true;
                $featuresUsed['extreme_hr'] = $vitals['heart_rate'];
            }
            // Extreme Blood Pressure (Hypertensive Emergency check)
            if (isset($vitals['systolic_bp']) && $vitals['systolic_bp'] >= 160) {
                $escalated = true;
                $featuresUsed['extreme_sbp'] = $vitals['systolic_bp'];
            }

            if ($escalated) {
                $featuresUsed['vitals_triggered'] = true;
                $priority = $this->escalatePriority($priority);
                $confidence = min(0.99, $confidence + 0.05);
            }
        }

        // 3. Allergy-based escalation
        if ($allergies) {
            $hasSevereAllergy = false;
            foreach ($allergies as $allergy) {
                if (($allergy['severity'] ?? '') === 'severe') {
                    // Check if complaint mentions their allergen
                    $allergen = strtolower($allergy['allergen'] ?? '');
                    if (!empty($allergen) && (str_contains($complaintLower, $allergen) || str_contains($complaintLower, 'allerg'))) {
                        $hasSevereAllergy = true;
                        $featuresUsed['matched_allergen'] = $allergy['allergen'];
                        break;
                    }
                }
            }

            if ($hasSevereAllergy) {
                $featuresUsed['allergy_triggered'] = true;
                $priority = 'urgent'; // Force to urgent due to severe anaphylaxis risk
                $confidence = 0.95;
            }
        }

        return [
            'predicted_priority' => $priority,
            'confidence_score'   => $confidence,
            'model_version'      => 'tfidf_weighted_rules_v1.0',
            'features_used'      => $featuresUsed
        ];
    }

    /**
     * Escalate priority level by 1 step.
     */
    private function escalatePriority(string $current): string
    {
        return match ($current) {
            'low'    => 'medium',
            'medium' => 'high',
            default  => 'urgent'
        };
    }
}
