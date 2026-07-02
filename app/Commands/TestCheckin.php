<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\StudentModel;
use App\Models\CounsellingAppointmentModel;
use App\Models\ConsultationModel;
use App\Models\OfflineCheckinBufferModel;
use App\Controllers\Iot\CheckinController;
use Exception;
use ReflectionClass;

class TestCheckin extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'test:checkin';
    protected $description = 'Runs IoT check-in routing and offline synchronization tests.';

    public function run(array $params)
    {
        CLI::write("====================================================", 'cyan');
        CLI::write("SYNAPSE IoT Integration Subsystem Routing Tests", 'yellow');
        CLI::write("====================================================", 'cyan');

        $db = \Config\Database::connect();
        $db->transStart(); // Use a transaction so we can roll back all test changes

        try {
            // 1. Fetch a student (Maria Santos is student_number 2024-00001)
            $studentModel = new StudentModel();
            $student = $studentModel->where('student_number', '2024-00001')->first();
            if (!$student) {
                throw new Exception("Maria Santos student profile not found! Make sure DB is seeded.");
            }
            
            // Resolve user record
            $user = $db->table('users')->where('id', $student['user_id'])->get()->getRowArray();
            $student['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $student['avatar_url'] = null;
            $studentId = (int)$student['id'];
            $userId = (int)$student['user_id'];
            
            CLI::write("Testing with Student: {$student['full_name']} ({$student['student_number']})", 'green');
            
            // Instantiate Controller
            $request  = \Config\Services::request();
            $response = \Config\Services::response();
            $logger   = \Config\Services::logger();
            $controller = new CheckinController();
            $controller->initController($request, $response, $logger);
            // Use Reflection to test the private dispatchCheckin method
            $reflection = new ReflectionClass(get_class($controller));
            $method = $reflection->getMethod('dispatchCheckin');
            $method->setAccessible(true);
            
            // Clean up today's conflicting test records just in case
            $today = date('Y-m-d');
            
            // Test Case A: CLINIC ROUTING (No schedules today)
            // ----------------------------------------------------
            CLI::write("\n[Test Case A] Clinic Fallback Routing:", 'white');
            // Ensure no appointments or assignments today
            $db->table('counselling_appointments')->where('student_id', $studentId)->where('appointment_date', $today)->delete();
            
            $resultA = $method->invokeArgs($controller, [$student, 'qr', 'Kiosk-TestA', date('Y-m-d H:i:s')]);
            
            CLI::write("  - Routing Type: " . ($resultA['type'] ?? 'N/A'));
            CLI::write("  - Destination: " . ($resultA['destination'] ?? 'N/A'));
            CLI::write("  - Success: " . ($resultA['success'] ? 'YES' : 'NO'));
            
            if (($resultA['type'] ?? '') !== 'clinic') {
                throw new Exception("Expected clinic routing, got: " . ($resultA['type'] ?? 'none'));
            }
            CLI::write("  => PASS", 'green');
            
            // Test Case B: COUNSELLING ROUTING (Scheduled appointment today)
            // ----------------------------------------------------
            CLI::write("\n[Test Case B] Counselling Routing:", 'white');
            // Create a counselling appointment today
            $counsellor = $db->table('user_roles')
                ->join('roles', 'roles.id = user_roles.role_id')
                ->where('roles.name', 'counsellor')
                ->select('user_roles.user_id')
                ->get()->getRow();
            
            if (!$counsellor) {
                throw new Exception("No counsellor user found in DB! Seed assessments first.");
            }
            
            $db->table('counselling_appointments')->insert([
                'student_id' => $studentId,
                'counsellor_id' => $counsellor->user_id,
                'appointment_date' => $today,
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'status' => 'scheduled',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            $resultB = $method->invokeArgs($controller, [$student, 'qr', 'Kiosk-TestB', date('Y-m-d H:i:s')]);
            CLI::write("  - Routing Type: " . ($resultB['type'] ?? 'N/A'));
            CLI::write("  - Destination: " . ($resultB['destination'] ?? 'N/A'));
            CLI::write("  - Success: " . ($resultB['success'] ? 'YES' : 'NO'));
            
            if (($resultB['type'] ?? '') !== 'counselling') {
                throw new Exception("Expected counselling routing, got: " . ($resultB['type'] ?? 'none'));
            }
            CLI::write("  => PASS", 'green');
            
            // Test Case D: OFFLINE BUFFERING & SYNCHRONIZATION
            // ----------------------------------------------------
            CLI::write("\n[Test Case D] Offline Buffering & Synchronization:", 'white');
            $bufferModel = new OfflineCheckinBufferModel();
            
            // Clear buffer table
            $db->table('offline_checkin_buffer')->truncate();
            
            // Simulate scan buffering
            $student2 = $studentModel->where('student_number', '2024-00002')->first();
            if (!$student2) {
                throw new Exception("Juan Dela Cruz student profile not found! Make sure DB is seeded.");
            }

            $bufferModel->saveScan('QR-' . $student2['student_number'], 'qr', 'Kiosk-OfflineTest', date('Y-m-d H:i:s'));
            
            // Save duplicate scan (should be flagged during sync)
            $bufferModel->saveScan('QR-' . $student2['student_number'], 'qr', 'Kiosk-OfflineTest', date('Y-m-d H:i:s', strtotime('+2 minutes')));
            
            // Save scan for another student (unregistered / unknown)
            $bufferModel->saveScan('QR-9999-99999', 'qr', 'Kiosk-OfflineTest', date('Y-m-d H:i:s'));
            
            $pending = $bufferModel->getPending();
            CLI::write("  - Pending scans in buffer: " . count($pending) . " (Expected: 3)");
            if (count($pending) !== 3) {
                throw new Exception("Expected 3 pending scans in buffer, got: " . count($pending));
            }
            
            // Run Sync logic via CheckinController
            $syncResult = $controller->sync();
            
            // Decode response JSON from shared response service
            $resp = json_decode($response->getBody(), true);
            CLI::write("  - Sync Status: " . ($resp['success'] ? 'SUCCESS' : 'FAILED'));
            CLI::write("  - Synced Count: " . ($resp['synced'] ?? 0));
            CLI::write("  - Duplicate Count: " . ($resp['duplicates'] ?? 0));
            CLI::write("  - Failed Count: " . ($resp['failed'] ?? 0));
            
            if (($resp['synced'] ?? 0) !== 1 || ($resp['duplicates'] ?? 0) !== 1 || ($resp['failed'] ?? 0) !== 1) {
                throw new Exception("Sync stats incorrect! Expected 1 synced, 1 duplicate, 1 failed.");
            }
            CLI::write("  => PASS", 'green');
            
            CLI::write("\nALL TESTS PASSED SUCCESSFULLY!", 'green');
            
        } catch (Exception $e) {
            CLI::error("TEST EXCEPTION: " . $e->getMessage());
            CLI::error("Line: " . $e->getLine());
        } finally {
            // Roll back transaction so database is completely clean
            $db->transRollback();
            CLI::write("\nTransaction Rolled Back. Database state preserved.", 'yellow');
        }
        CLI::write("====================================================", 'cyan');
    }
}
