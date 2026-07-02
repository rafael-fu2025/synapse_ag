<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\StudentModel;
use App\Models\MedicineModel;
use Exception;

class TestAi extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'test:ai';
    protected $description = 'Runs all 6 AI module integration tests and algorithms.';

    public function run(array $params)
    {
        CLI::write("====================================================", 'cyan');
        CLI::write("SYNAPSE AI Features Integration Test Suite", 'yellow');
        CLI::write("====================================================", 'cyan');

        $db = \Config\Database::connect();
        $db->transStart(); // Under transactional isolation to rollback DB modifications

        try {
            // Find a seeded student for testing (Maria Santos 2024-00001)
            $studentModel = new StudentModel();
            $student = $studentModel->where('student_number', '2024-00001')->first();
            if (!$student) {
                throw new Exception("Maria Santos student profile not found. Make sure DB is seeded.");
            }
            $studentId = (int) $student['id'];
            $userId = (int) $student['user_id'];

            // Find a medicine for inventory testing (Paracetamol)
            $medModel = new MedicineModel();
            $medicine = $medModel->where('generic_name', 'Paracetamol')->first();
            if (!$medicine) {
                throw new Exception("Paracetamol medicine not found. Make sure DB is seeded.");
            }
            $medId = (int) $medicine['id'];

            // ----------------------------------------------------
            // 1. AI-A: SMART TRIAGE TEST
            // ----------------------------------------------------
            CLI::write("\n[1/6] Testing AI-A: Smart Triage Assistant...", 'white');
            $triageAssistant = new \App\Libraries\TriageAssistant();
            
            // Scenario 1: Severe anaphylaxis chest pain with allergy
            $allergies = [['allergen' => 'Penicillin', 'severity' => 'severe']];
            $vitals = ['temperature' => 39.0, 'heart_rate' => 125, 'systolic_bp' => 140];
            $triage = $triageAssistant->analyze("Severe chest pain, breathing difficulty, took penicillin yesterday", $vitals, $allergies);
            
            CLI::write("  - Predicted Priority: " . $triage['predicted_priority'] . " (Expected: urgent)");
            CLI::write("  - Confidence Score: " . $triage['confidence_score']);
            CLI::write("  - Vitals Triggered: " . ($triage['features_used']['vitals_triggered'] ? 'YES' : 'NO'));
            CLI::write("  - Allergy Triggered: " . ($triage['features_used']['allergy_triggered'] ? 'YES' : 'NO'));

            if ($triage['predicted_priority'] !== 'urgent') {
                throw new Exception("Smart Triage predicted incorrect priority for urgent case.");
            }
            CLI::write("  => PASS", 'green');

            // ----------------------------------------------------
            // 2. AI-B: INVENTORY FORECASTING TEST
            // ----------------------------------------------------
            CLI::write("\n[2/6] Testing AI-B: Predictive Inventory Forecasts...", 'white');
            $forecaster = new \App\Libraries\InventoryForecaster();
            
            // Insert mock dispensing transaction
            $db->table('inventory_transactions')->insert([
                'medicine_id'      => $medId,
                'user_id'          => $userId,
                'transaction_type' => 'dispensed',
                'quantity'         => 30, // 30 units dispensed
                'transaction_date' => date('Y-m-d H:i:s'),
                'notes'            => 'Mock clinic dispensing'
            ]);

            $forecast = $forecaster->calculateForecast($medId, 100, 20);
            CLI::write("  - Predicted Daily Usage: " . $forecast['predicted_daily_usage'] . " units/day");
            CLI::write("  - Predicted Stockout Date: " . $forecast['predicted_stockout_date']);
            CLI::write("  - Predicted Reorder Date: " . $forecast['predicted_reorder_date']);
            CLI::write("  - Seasonality Factor: " . $forecast['seasonality_factor']);

            if ($forecast['predicted_daily_usage'] <= 0) {
                throw new Exception("Inventory forecaster returned zero consumption rate.");
            }
            CLI::write("  => PASS", 'green');

            // ----------------------------------------------------
            // 3. AI-C: MENTAL HEALTH RISK SCORING TEST
            // ----------------------------------------------------
            CLI::write("\n[3/6] Testing AI-C: Mental Health Risk Scoring & Trend...", 'white');
            $riskScorer = new \App\Libraries\RiskScorer();

            // Insert longitudinal PHQ-9 screening responses (increasing scores = worsening distress)
            $db->table('assessment_responses')->insert([
                'template_id' => 1, // PHQ-9
                'student_id'  => $studentId,
                'responses'   => json_encode(['q_1' => 1]),
                'total_score' => 4,
                'submitted_at'=> date('Y-m-d H:i:s', strtotime('-15 days')),
            ]);
            $db->table('assessment_responses')->insert([
                'template_id' => 1,
                'student_id'  => $studentId,
                'responses'   => json_encode(['q_1' => 2]),
                'total_score' => 10,
                'submitted_at'=> date('Y-m-d H:i:s', strtotime('-7 days')),
            ]);
            $db->table('assessment_responses')->insert([
                'template_id' => 1,
                'student_id'  => $studentId,
                'responses'   => json_encode(['q_1' => 3]),
                'total_score' => 18, // Significant distress jump
                'submitted_at'=> date('Y-m-d H:i:s'),
            ]);

            $risk = $riskScorer->analyzeHistory($studentId, 'phq9_trend');
            CLI::write("  - Calculated Slope: " . $risk['trend_slope'] . " (Positive = worsening)");
            CLI::write("  - Risk Level: " . $risk['risk_level'] . " (Expected: high/critical)");
            CLI::write("  - Anomaly Detected: " . ($risk['anomaly_detected'] ? 'YES (distress spike)' : 'NO'));
            CLI::write("  - Projected 30-Day Score: " . $risk['projected_score']);

            if (!$risk['anomaly_detected'] || $risk['trend_slope'] <= 0) {
                throw new Exception("Risk scorer failed to detect distress jump anomaly or positive worsening slope.");
            }
            CLI::write("  => PASS", 'green');

            // ----------------------------------------------------
            // 4. AI-D: SMART SCHEDULING OPTIMIZER TEST
            // ----------------------------------------------------
            CLI::write("\n[4/6] Testing AI-D: Smart Scheduling Optimizer...", 'white');
            $scheduler = new \App\Libraries\SchedulingOptimizer();

            // Find a counsellor
            $counsellor = $db->table('user_roles')
                ->join('roles', 'roles.id = user_roles.role_id')
                ->where('roles.name', 'counsellor')
                ->select('user_roles.user_id')
                ->get()->getRow();
            $counsellorId = $counsellor ? (int) $counsellor->user_id : 1;

            // Insert 3 appointments (2 completed, 1 no-show)
            $db->table('counselling_appointments')->insert([
                'student_id' => $studentId,
                'counsellor_id' => $counsellorId,
                'appointment_date' => date('Y-m-d', strtotime('-5 days')),
                'start_time' => '14:00:00',
                'end_time' => '15:00:00',
                'status' => 'completed'
            ]);
            $db->table('counselling_appointments')->insert([
                'student_id' => $studentId,
                'counsellor_id' => $counsellorId,
                'appointment_date' => date('Y-m-d', strtotime('-4 days')),
                'start_time' => '14:00:00',
                'end_time' => '15:00:00',
                'status' => 'completed'
            ]);
            $db->table('counselling_appointments')->insert([
                'student_id' => $studentId,
                'counsellor_id' => $counsellorId,
                'appointment_date' => date('Y-m-d', strtotime('-3 days')),
                'start_time' => '14:00:00',
                'end_time' => '15:00:00',
                'status' => 'no_show'
            ]);

            $scheduler->recalculateSlotAnalytics($counsellorId);
            $prob = $scheduler->predictNoShowProbability($studentId, $counsellorId, date('Y-m-d'), '14:00:00');

            CLI::write("  - Predicted Student No-Show Probability: " . ($prob * 100) . "%");
            
            // Check analytics table row
            $analyticRow = $db->table('scheduling_analytics')
                ->where('counsellor_id', $counsellorId)
                ->where('time_slot', '14:00:00')
                ->get()->getRowArray();
            CLI::write("  - Slot No-Show Rate: " . ($analyticRow['no_show_rate'] * 100) . "%");
            CLI::write("  - Recommended Overbooking: " . $analyticRow['recommended_overbooking']);

            if ($prob <= 0.0 || !$analyticRow) {
                throw new Exception("Scheduling optimizer returned invalid probabilities or failed to populate analytics.");
            }
            CLI::write("  => PASS", 'green');

            // ----------------------------------------------------
            // 5. AI-E: NLP REPORT SUMMARIZER TEST
            // ----------------------------------------------------
            CLI::write("\n[5/6] Testing AI-E: NLP Report Summarizer (NLG)...", 'white');
            $summarizer = new \App\Libraries\ReportSummarizer();

            $clinicMockData = [
                'total_consultations' => 124,
                'triage_high'         => 12,
                'triage_urgent'       => 5,
                'referrals_count'     => 4,
                'top_complaint'       => 'fever and headache',
            ];
            $nlgClinic = $summarizer->generateSummary('clinic', date('Y-m-d', strtotime('-30 days')), date('Y-m-d'), $clinicMockData, null, $userId);
            CLI::write("  - Generated Clinic Narrative Summary:\n    \"" . $nlgClinic['summary_text'] . "\"");

            $counsellMockData = [
                'total_appointments'      => 45,
                'total_no_shows'          => 9,
                'crisis_alerts_count'     => 2,
                'severe_screenings_count' => 3,
            ];
            $nlgCounsell = $summarizer->generateSummary('counselling', date('Y-m-d', strtotime('-30 days')), date('Y-m-d'), $counsellMockData, null, $userId);
            CLI::write("  - Generated Counselling Narrative Summary:\n    \"" . $nlgCounsell['summary_text'] . "\"");

            if (empty($nlgClinic['summary_text']) || empty($nlgCounsell['summary_text'])) {
                throw new Exception("NLG Report Summarizer returned empty narrative summaries.");
            }
            CLI::write("  => PASS", 'green');

            // ----------------------------------------------------
            // 6. AI-F: INTELLIGENT CONFLICT DETECTION TEST
            // ----------------------------------------------------
            CLI::write("\n[6/6] Testing AI-F: Intelligent Conflict Detection...", 'white');
            $detector = new \App\Libraries\ConflictDetector();

            // Create outreach program & activity today
            $db->table('outreach_programs')->insert([
                'name' => 'AI Test Outreach',
                'description' => 'Workload and conflict AI testing',
                'coordinator_id' => $userId, // coordinator link
                'status' => 'active'
            ]);
            $progId = $db->insertID();

            $activityDate = date('Y-m-d', strtotime('+5 days'));
            $db->table('outreach_activities')->insert([
                'program_id' => $progId,
                'title' => 'Immunization Mission',
                'description' => 'Test',
                'location' => 'School Clinic',
                'activity_date' => $activityDate,
                'start_time' => '09:00:00',
                'end_time' => '12:00:00',
                'status' => 'upcoming'
            ]);
            $actId = $db->insertID();

            // Scenario 1: No overlaps -> expect no conflicts
            $conf1 = $detector->detectConflicts($userId, $activityDate, '09:00:00', '12:00:00');
            CLI::write("  - Conflict check (No overlaps): " . ($conf1['has_conflict'] ? 'CONFLICT' : 'OK'));
            if ($conf1['has_conflict']) {
                throw new Exception("False conflict detected on empty calendar.");
            }

            // Scenario 2: Overlay counselling booking on same slot -> expect conflict
            // counselling_appointments uses student_id (profile id)
            $db->table('counselling_appointments')->insert([
                'student_id' => $studentId,
                'counsellor_id' => $counsellorId,
                'appointment_date' => $activityDate,
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'status' => 'scheduled'
            ]);
            $conf2 = $detector->detectConflicts($userId, $activityDate, '09:00:00', '12:00:00');
            CLI::write("  - Conflict check (Overlap with counselling): " . ($conf2['has_conflict'] ? 'CONFLICT (OK)' : 'NO CONFLICT (FAIL)'));
            CLI::write("    Reason: \"" . $conf2['conflict_reason'] . "\"");

            if (!$conf2['has_conflict']) {
                throw new Exception("Conflict detector failed to identify counselling appointment overlap.");
            }

            // Suggest alternatives
            $alternatives = $detector->suggestAlternatives($actId, 3);
            CLI::write("  - Suggested Alternative Volunteers: " . count($alternatives));
            foreach ($alternatives as $alt) {
                CLI::write("    * Name: " . $alt['name'] . " (Workload Score: " . $alt['workload_score'] . ")");
            }

            if (empty($alternatives)) {
                throw new Exception("Failed to suggest alternative volunteers.");
            }
            CLI::write("  => PASS", 'green');

            CLI::write("\nALL 6 AI INTEGRATIONS VERIFIED SUCCESSFULLY!", 'green');

        } catch (Exception $e) {
            CLI::error("TEST FAILED: " . $e->getMessage());
            CLI::error("Line: " . $e->getLine());
        } finally {
            $db->transRollback(); // Roll back all mock inserts/modifications
            CLI::write("\nTransaction Rolled Back. System DB preserved.", 'yellow');
        }
        CLI::write("====================================================", 'cyan');
    }
}
