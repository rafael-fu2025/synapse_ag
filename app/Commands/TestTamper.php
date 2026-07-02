<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\AuditLogModel;
use Exception;

class TestTamper extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'test:tamper';
    protected $description = 'Verifies the cryptographic integrity of the audit log hash chain and simulates a data tampering attack.';
    protected $usage       = 'test:tamper';

    public function run(array $params)
    {
        CLI::write("====================================================", 'cyan');
        CLI::write("Audit Log Cryptographic Hash-Chain Verification Tool", 'yellow');
        CLI::write("====================================================", 'cyan');

        $db = \Config\Database::connect();
        $db->transStart(); // Isolation boundary: rollback all alterations after test

        try {
            // Transactional delete of existing logs so we start with a clean genesis block in this test run.
            // DML delete is fully rollbackable, unlike TRUNCATE which causes an implicit commit in MySQL.
            $db->table('audit_logs')->where('id > 0')->delete();

            $auditModel = new AuditLogModel();

            // 1. Log a sequence of events to establish a valid hash chain
            CLI::write("\n[Step 1] Creating a clean chain of audit logs...", 'white');
            
            $auditModel->logAction(1, 'login', 'auth', 'users', 1, null, ['status' => 'logged_in']);
            $auditModel->logAction(1, 'view', 'clinic', 'patients', 2, null, null);
            $auditModel->logAction(1, 'create', 'clinic', 'consultations', 10, null, ['priority' => 'urgent']);
            $auditModel->logAction(1, 'dispense', 'inventory', 'medicines', 5, ['stock' => 100], ['stock' => 70]);

            // Retrieve the newly inserted logs within this transaction context
            $recentLogs = $db->table('audit_logs')
                ->orderBy('id', 'DESC')
                ->limit(4)
                ->get()
                ->getResultArray();

            $recentLogs = array_reverse($recentLogs); // order ASC

            CLI::write("  - Created 4 log entries successfully.");
            foreach ($recentLogs as $index => $log) {
                CLI::write("    * Entry #{$log['id']} [{$log['action']} in {$log['module']}]: Hash: " . substr($log['hash'], 0, 12) . "... Prev: " . substr($log['previous_hash'], 0, 12) . "...");
            }

            // 2. Validate chain integrity on the clean chain
            CLI::write("\n[Step 2] Validating clean chain integrity...", 'white');
            $verification = $auditModel->verifyChainIntegrity(100);

            CLI::write("  - Checked Logs: " . $verification['checked']);
            CLI::write("  - Chain Intact: " . ($verification['intact'] ? 'YES' : 'NO'));
            CLI::write("  - Errors Found: " . $verification['error_count']);

            if (!$verification['intact']) {
                foreach ($verification['errors'] as $err) {
                    CLI::write("    * ERROR: " . $err['message'], 'red');
                }
                throw new Exception("Clean chain validation failed! Expected intact = true.");
            }
            CLI::write("  => PASS (Chain is cryptographically secure)", 'green');

            // 3. Simulate content tampering
            $targetLog = $recentLogs[1]; // Entry index 1
            CLI::write("\n[Step 3] Simulating content tampering attack on Log ID #{$targetLog['id']}...", 'white');
            CLI::write("  - Modifying 'action' from '{$targetLog['action']}' to 'delete_all' directly in DB...");

            $db->table('audit_logs')
                ->where('id', $targetLog['id'])
                ->update(['action' => 'delete_all']);

            CLI::write("  - Re-running integrity audit...");
            $verificationTampered = $auditModel->verifyChainIntegrity(100);

            CLI::write("  - Chain Intact: " . ($verificationTampered['intact'] ? 'YES' : 'NO'));
            CLI::write("  - Errors Found: " . $verificationTampered['error_count']);

            foreach ($verificationTampered['errors'] as $err) {
                CLI::write("    * ERROR DETECTED: " . $err['message'], 'red');
            }

            if ($verificationTampered['intact'] || $verificationTampered['error_count'] === 0) {
                throw new Exception("Security Failure: Recalculation failed to detect tampered cell value!");
            }
            CLI::write("  => PASS (Tampering successfully blocked and flagged by cryptographic verify)", 'green');

            // 4. Restore value and simulate chain break (previous_hash link manipulation)
            CLI::write("\n[Step 4] Restoring modified value and simulating previous_hash pointer breakage...", 'white');
            
            // Restore cell value
            $db->table('audit_logs')
                ->where('id', $targetLog['id'])
                ->update(['action' => $targetLog['action']]);

            // Break previous_hash linkage on the subsequent row
            $nextLog = $recentLogs[2];
            $db->table('audit_logs')
                ->where('id', $nextLog['id'])
                ->update(['previous_hash' => 'TAMPERED_PREV_HASH']);

            CLI::write("  - Set previous_hash of Log ID #{$nextLog['id']} to 'TAMPERED_PREV_HASH'.");
            CLI::write("  - Re-running integrity audit...");
            
            $verificationBrokenLink = $auditModel->verifyChainIntegrity(100);
            CLI::write("  - Chain Intact: " . ($verificationBrokenLink['intact'] ? 'YES' : 'NO'));
            CLI::write("  - Errors Found: " . $verificationBrokenLink['error_count']);

            foreach ($verificationBrokenLink['errors'] as $err) {
                CLI::write("    * ERROR DETECTED: " . $err['message'], 'red');
            }

            if ($verificationBrokenLink['intact'] || $verificationBrokenLink['error_count'] === 0) {
                throw new Exception("Security Failure: Failed to detect broken chain link!");
            }
            CLI::write("  => PASS (Broken pointer link correctly detected and flagged)", 'green');

            CLI::write("\nALL AUDIT LOG SECURITY CHECKS PASSED!", 'green');

        } catch (Exception $e) {
            CLI::error("\nTEST FAILED: " . $e->getMessage());
            CLI::error("Line: " . $e->getLine());
        } finally {
            $db->transRollback(); // Roll back all mock inserts/modifications
            CLI::write("\nTransaction Rolled Back. Database preserved in pristine state.", 'yellow');
        }
        CLI::write("====================================================", 'cyan');
    }
}
