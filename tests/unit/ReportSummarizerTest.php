<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\ReportSummarizer;

class ReportSummarizerTest extends CIUnitTestCase
{
    protected $db;
    private ReportSummarizer $summarizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = \Config\Database::connect();
        $this->db->transStart();
        $this->summarizer = new ReportSummarizer();
    }

    protected function tearDown(): void
    {
        $this->db->transRollback();
        parent::tearDown();
    }

    public function testGenerateClinicSummary()
    {
        $data = [
            'total_consultations' => 100,
            'triage_high' => 15,
            'triage_urgent' => 5, // Total critical = 20 (20%)
            'referrals_count' => 3,
            'top_complaint' => 'Severe Headache'
        ];

        $result = $this->summarizer->generateSummary('clinic', '2026-06-01', '2026-06-30', $data, null, 1);
        
        $text = $result['summary_text'];
        
        $this->assertStringContainsString('total of **100 consultations**', $text);
        $this->assertStringContainsString('20% were classified as high-priority', $text);
        $this->assertStringContainsString('**Severe Headache**', $text);
        $this->assertStringContainsString('**3 cases** were referred', $text);

        // Verify it was saved to DB
        $dbRecord = $this->db->table('ai_generated_summaries')->where('module', 'clinic')->get()->getRowArray();
        $this->assertNotNull($dbRecord);
        $this->assertEquals('template_nlg_v2.0', $dbRecord['model_used']);
    }

    public function testGenerateCounsellingSummary()
    {
        $data = [
            'total_appointments' => 50,
            'total_no_shows' => 10, // 20% no-show
            'crisis_alerts_count' => 2,
            'severe_screenings_count' => 4
        ];

        $result = $this->summarizer->generateSummary('counselling', '2026-06-01', '2026-06-30', $data, null, 1);
        
        $text = $result['summary_text'];
        
        $this->assertStringContainsString('**50 appointments**', $text);
        $this->assertStringContainsString('**20% no-show rate**', $text);
        $this->assertStringContainsString('**4 instances** of severe anxiety', $text);
        $this->assertStringContainsString('**2 critical crisis alerts**', $text);
        $this->assertStringContainsString('30-minute counsellor response protocol', $text);

        // Verify DB
        $dbRecord = $this->db->table('ai_generated_summaries')->where('module', 'counselling')->get()->getRowArray();
        $this->assertNotNull($dbRecord);
    }
}
