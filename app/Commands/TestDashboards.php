<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TestDashboards extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'test:dashboards';
    protected $description = 'Verifies that all four dashboards render successfully with dynamic metrics.';
    protected $usage       = 'test:dashboards';

    public function run(array $params)
    {
        CLI::write("====================================================", 'cyan');
        CLI::write("SYNAPSE Dashboard Metrics Render Verification Tool", 'yellow');
        CLI::write("====================================================", 'cyan');

        // Mock session
        $session = session();
        $session->set([
            'user_id' => 1,
            'primary_role' => 'admin',
            'first_name' => 'CLI',
            'last_name' => 'Tester',
        ]);

        $controller = new \App\Controllers\DashboardController();

        // 1. Test Admin Dashboard
        CLI::write("\n1. Testing Admin Dashboard...", 'white');
        try {
            $html = $controller->admin();
            CLI::write("   - Rendered successfully. Length: " . strlen($html) . " bytes");
            if (strpos($html, '<h3>—</h3>') !== false) {
                CLI::write("   [FAIL] Found '<h3>—</h3>' placeholder in rendered admin output!", 'red');
            } else {
                CLI::write("   [PASS] No placeholders found.", 'green');
            }
        } catch (\Throwable $e) {
            CLI::write("   [FAIL] Exception: " . $e->getMessage(), 'red');
            CLI::write($e->getTraceAsString(), 'red');
        }

        // 2. Test Clinic Dashboard
        CLI::write("\n2. Testing Clinic Dashboard...", 'white');
        try {
            $html = $controller->clinic();
            CLI::write("   - Rendered successfully. Length: " . strlen($html) . " bytes");
            if (strpos($html, '<h3>—</h3>') !== false) {
                CLI::write("   [FAIL] Found '<h3>—</h3>' placeholder in rendered clinic output!", 'red');
            } else {
                CLI::write("   [PASS] No placeholders found.", 'green');
            }
        } catch (\Throwable $e) {
            CLI::write("   [FAIL] Exception: " . $e->getMessage(), 'red');
            CLI::write($e->getTraceAsString(), 'red');
        }

        // 3. Test Counsellor Dashboard
        CLI::write("\n3. Testing Counsellor Dashboard...", 'white');
        try {
            $html = $controller->counsellor();
            CLI::write("   - Rendered successfully. Length: " . strlen($html) . " bytes");
            if (strpos($html, '<h3>—</h3>') !== false) {
                CLI::write("   [FAIL] Found '<h3>—</h3>' placeholder in rendered counsellor output!", 'red');
            } else {
                CLI::write("   [PASS] No placeholders found.", 'green');
            }
        } catch (\Throwable $e) {
            CLI::write("   [FAIL] Exception: " . $e->getMessage(), 'red');
            CLI::write($e->getTraceAsString(), 'red');
        }

        // 4. Test PASIMEO Dashboard
        CLI::write("\n4. Testing PASIMEO Dashboard...", 'white');
        try {
            $html = $controller->pasimeo();
            CLI::write("   - Rendered successfully. Length: " . strlen($html) . " bytes");
            if (strpos($html, '<h3>—</h3>') !== false) {
                CLI::write("   [FAIL] Found '<h3>—</h3>' placeholder in rendered pasimeo output!", 'red');
            } else {
                CLI::write("   [PASS] No placeholders found.", 'green');
            }
        } catch (\Throwable $e) {
            CLI::write("   [FAIL] Exception: " . $e->getMessage(), 'red');
            CLI::write($e->getTraceAsString(), 'red');
        }

        CLI::write("\n====================================================", 'cyan');
        CLI::write("Verification finished.", 'yellow');
        CLI::write("====================================================", 'cyan');
    }
}
