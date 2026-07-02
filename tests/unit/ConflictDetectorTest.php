<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\ConflictDetector;

class ConflictDetectorTest extends CIUnitTestCase
{
    protected $db;
    private ConflictDetector $conflictDetector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = \Config\Database::connect();
        $this->db->transStart(); // Start transaction for safety
        $this->conflictDetector = new ConflictDetector();
    }

    protected function tearDown(): void
    {
        $this->db->transRollback(); // Roll back all insertions/deletions
        parent::tearDown();
    }

    public function testNoConflictsWhenScheduleIsClear()
    {
        // Testing user id 99999 (non-existent or clean user)
        $result = $this->conflictDetector->detectConflicts(99999, '2026-06-27', '09:00:00', '10:00:00');
        $this->assertFalse($result['has_conflict']);
        $this->assertNull($result['conflict_reason']);
    }

    public function testCounsellingAppointmentOverlapConflict()
    {
        // Insert a temporary student and counsellor for testing
        $this->db->table('users')->insert([
            'id' => 99990,
            'email' => 'teststudent@synapse.edu.ph',
            'password_hash' => 'dummy',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'is_active' => true
        ]);
        
        $this->db->table('students')->insert([
            'id' => 99990,
            'user_id' => 99990,
            'student_number' => 'TEST-001',
            'course' => 'CS',
            'year_level' => 1
        ]);

        $this->db->table('users')->insert([
            'id' => 99991,
            'email' => 'testcounsellor@synapse.app',
            'password_hash' => 'dummy',
            'first_name' => 'Test',
            'last_name' => 'Counsellor',
            'is_active' => true
        ]);

        // Insert appointment
        $this->db->table('counselling_appointments')->insert([
            'student_id' => 99990,
            'counsellor_id' => 99991,
            'appointment_date' => '2026-06-27',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'scheduled'
        ]);

        // Check conflict for the student (should fail because of overlap with the appointment)
        $result = $this->conflictDetector->detectConflicts(99990, '2026-06-27', '10:30:00', '11:30:00');
        $this->assertTrue($result['has_conflict']);
        $this->assertStringContainsString('Counselling Session', $result['conflict_reason']);
    }

    public function testClinicShiftOverlapConflict()
    {
        $this->db->table('users')->insert([
            'id' => 99992,
            'email' => 'testclinic@synapse.edu.ph',
            'password_hash' => 'dummy',
            'first_name' => 'Test',
            'last_name' => 'ClinicStaff',
            'is_active' => true
        ]);

        // Insert a clinic staff schedule shift for Sunday (day_of_week = 0)
        // Date '2026-06-28' is a Sunday
        $this->db->table('clinic_staff_schedules')->insert([
            'user_id' => 99992,
            'day_of_week' => 0, // Sunday
            'shift_start' => '08:00:00',
            'shift_end' => '12:00:00'
        ]);

        $result = $this->conflictDetector->detectConflicts(99992, '2026-06-28', '09:00:00', '10:00:00');
        $this->assertTrue($result['has_conflict']);
        $this->assertStringContainsString('Clinic Duty Shift', $result['conflict_reason']);
    }
}
