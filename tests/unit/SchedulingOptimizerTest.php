<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\SchedulingOptimizer;

class SchedulingOptimizerTest extends CIUnitTestCase
{
    protected $db;
    private SchedulingOptimizer $optimizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = \Config\Database::connect();
        $this->db->transStart();
        $this->optimizer = new SchedulingOptimizer();
    }

    protected function tearDown(): void
    {
        $this->db->transRollback();
        parent::tearDown();
    }

    public function testRecalculateSlotAnalytics()
    {
        // Insert a mock counsellor
        $this->db->table('users')->insert([
            'id' => 99993,
            'email' => 'optcounsellor@synapse.edu.ph',
            'password_hash' => 'dummy',
            'first_name' => 'Opt',
            'last_name' => 'Counsellor',
            'is_active' => true
        ]);

        // Insert historical appointments:
        // Let's use a known Monday date, e.g. 2026-06-22 is Monday.
        // Insert 10 appointments at 09:00:00 on Mondays. 4 are no_show.
        // No-show rate = 4/10 = 0.40 (> 0.30), which should trigger recommended_overbooking = 2.
        
        for ($i = 0; $i < 10; $i++) {
            $status = ($i < 4) ? 'no_show' : 'completed';
            $this->db->table('counselling_appointments')->insert([
                'counsellor_id' => 99993,
                'student_id' => 1, // Uses default seeded student for FK sake
                'appointment_date' => '2026-06-22', // Monday
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
                'status' => $status
            ]);
        }

        $result = $this->optimizer->recalculateSlotAnalytics(99993);
        $this->assertTrue($result);

        // Check the database for the generated analytics
        $analytics = $this->db->table('scheduling_analytics')
            ->where('counsellor_id', 99993)
            ->where('day_of_week', 1) // Monday is 1 in the logic
            ->where('time_slot', '09:00:00')
            ->get()->getRowArray();

        $this->assertNotNull($analytics);
        $this->assertEquals(10, $analytics['total_appointments']);
        $this->assertEquals(4, $analytics['total_no_shows']);
        $this->assertEquals(0.40, $analytics['no_show_rate']);
        $this->assertEquals(2, $analytics['recommended_overbooking']);
    }

    public function testPredictNoShowProbability()
    {
        // 1. Insert a mock student with consecutive no-shows penalty
        $this->db->table('users')->insert([
            'id' => 99994,
            'email' => 'optstudent@synapse.edu.ph',
            'password_hash' => 'dummy',
            'first_name' => 'Opt',
            'last_name' => 'Student',
            'is_active' => true
        ]);
        
        $this->db->table('students')->insert([
            'id' => 99994,
            'user_id' => 99994,
            'student_number' => 'OPT-001',
            'course' => 'CS',
            'year_level' => 1,
            'consecutive_no_shows' => 2 // This adds a heavy penalty (+50%)
        ]);

        // Insert mock counsellor 2 for appointments
        $this->db->table('users')->insert([
            'id' => 99995,
            'email' => 'counsellor2@synapse.edu.ph',
            'password_hash' => 'dummy',
            'first_name' => 'Mock',
            'last_name' => 'Counsellor',
            'is_active' => true
        ]);

        // Insert historical appointments for student (5 total, 1 no_show -> 20% rate)
        for ($i = 0; $i < 5; $i++) {
            $status = ($i < 1) ? 'no_show' : 'completed';
            $this->db->table('counselling_appointments')->insert([
                'counsellor_id' => 99995, // Fresh counsellor
                'student_id' => 99994,
                'appointment_date' => '2026-06-23', 
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'status' => $status
            ]);
        }

        // 2. Insert scheduling analytics for a specific slot with 30% slot rate
        $this->db->table('scheduling_analytics')->insert([
            'counsellor_id' => 99995,
            'day_of_week' => 2, // Tuesday
            'time_slot' => '10:00:00',
            'total_appointments' => 100,
            'total_no_shows' => 30,
            'no_show_rate' => 0.30,
            'avg_utilization' => 0.85,
            'recommended_overbooking' => 2
        ]);


        // 2026-06-23 is a Tuesday
        $prob = $this->optimizer->predictNoShowProbability(99994, 99995, '2026-06-23', '10:00:00');
        
        // Expected Math:
        // Student Rate: 1/5 = 0.20
        // Consecutive No Shows: 2
        // Penalty: 2 * 0.25 = 0.50
        // Slot Rate: 0.30
        // Formula: (0.40 * 0.20) + (0.30 * 0.30) + (0.30 * 0.80) + 0.50
        // = 0.08 + 0.09 + 0.24 + 0.50 = 0.91
        
        $this->assertEquals(0.91, $prob);
    }
}
