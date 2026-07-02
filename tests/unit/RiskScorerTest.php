<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\RiskScorer;

class RiskScorerTest extends CIUnitTestCase
{
    protected $db;
    private RiskScorer $riskScorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = \Config\Database::connect();
        $this->db->transStart();
        $this->riskScorer = new RiskScorer();
    }

    protected function tearDown(): void
    {
        $this->db->transRollback();
        parent::tearDown();
    }

    public function testAnalyzeHistoryEmpty()
    {
        // When there is no screening history, it should return baseline low risk
        $result = $this->riskScorer->analyzeHistory(99990, 'phq9_trend');
        $this->assertSame('low', $result['risk_level']);
        $this->assertSame(0, $result['current_score']);
        $this->assertSame(0.0, $result['trend_slope']);
        $this->assertFalse($result['anomaly_detected']);
    }

    public function testAnalyzeHistoryWithSpikeAnomaly()
    {
        // Ensure template 1 exists
        $exists = $this->db->table('assessment_templates')->where('id', 1)->countAllResults();
        if ($exists === 0) {
            $this->db->table('assessment_templates')->insert([
                'id' => 1,
                'name' => 'PHQ-9',
                'description' => 'PHQ-9 screening',
                'is_active' => true
            ]);
        }

        // Create mock student
        $this->db->table('users')->insert([
            'id' => 99995,
            'email' => 'riskstudent@synapse.edu.ph',
            'password_hash' => 'dummy',
            'first_name' => 'Risk',
            'last_name' => 'Student',
            'is_active' => true
        ]);
        
        $this->db->table('students')->insert([
            'id' => 99995,
            'user_id' => 99995,
            'student_number' => 'TEST-002',
            'course' => 'CS',
            'year_level' => 1
        ]);

        // Insert assessments with a sudden 7-point score spike
        // Response 1: score = 5
        $this->db->table('assessment_responses')->insert([
            'id' => 99991,
            'student_id' => 99995,
            'template_id' => 1, // PHQ-9
            'total_score' => 5,
            'responses' => '{}',
            'submitted_at' => date('Y-m-d H:i:s', strtotime('-10 days'))
        ]);

        // Response 2: score = 12 (distress spike of +7)
        $this->db->table('assessment_responses')->insert([
            'id' => 99992,
            'student_id' => 99995,
            'template_id' => 1, // PHQ-9
            'total_score' => 12,
            'responses' => '{}',
            'submitted_at' => date('Y-m-d H:i:s')
        ]);

        $result = $this->riskScorer->analyzeHistory(99995, 'phq9_trend');
        
        $this->assertSame('high', $result['risk_level']);
        $this->assertSame(12, $result['current_score']);
        $this->assertTrue($result['anomaly_detected']);
        $this->assertGreaterThan(0, $result['trend_slope']);
    }
}
