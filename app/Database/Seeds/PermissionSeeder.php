<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            // ── Auth & RBAC ──
            ['name' => 'users.view',             'module' => 'auth',        'description' => 'View user accounts'],
            ['name' => 'users.create',           'module' => 'auth',        'description' => 'Create new user accounts'],
            ['name' => 'users.edit',             'module' => 'auth',        'description' => 'Edit user accounts'],
            ['name' => 'users.deactivate',       'module' => 'auth',        'description' => 'Deactivate user accounts'],
            ['name' => 'roles.manage',           'module' => 'auth',        'description' => 'Manage roles and permissions'],
            ['name' => 'audit.view',             'module' => 'auth',        'description' => 'View audit logs'],

            // ── Clinic ──
            ['name' => 'consultations.view',     'module' => 'clinic',      'description' => 'View consultations'],
            ['name' => 'consultations.create',   'module' => 'clinic',      'description' => 'Create new consultations'],
            ['name' => 'consultations.edit',     'module' => 'clinic',      'description' => 'Edit consultations'],
            ['name' => 'vitals.record',          'module' => 'clinic',      'description' => 'Record patient vital signs'],
            ['name' => 'treatments.create',      'module' => 'clinic',      'description' => 'Create treatments and dispense medicine'],
            ['name' => 'allergies.manage',       'module' => 'clinic',      'description' => 'Manage patient allergies'],
            ['name' => 'referrals.create',       'module' => 'clinic',      'description' => 'Create referrals to counselling'],
            ['name' => 'referrals.view',         'module' => 'clinic',      'description' => 'View referrals'],
            ['name' => 'referrals.respond',      'module' => 'clinic',      'description' => 'Accept or decline referrals'],
            ['name' => 'checkin.perform',        'module' => 'clinic',      'description' => 'Perform patient QR/RFID check-in'],

            // ── Inventory ──
            ['name' => 'medicines.view',         'module' => 'inventory',   'description' => 'View medicine catalog'],
            ['name' => 'medicines.manage',       'module' => 'inventory',   'description' => 'Create/edit medicines and batches'],
            ['name' => 'inventory.dispense',     'module' => 'inventory',   'description' => 'Dispense medicine from inventory'],
            ['name' => 'inventory.adjust',       'module' => 'inventory',   'description' => 'Make inventory adjustments'],
            ['name' => 'inventory.reports',      'module' => 'inventory',   'description' => 'View inventory reports and forecasts'],

            // ── Counselling ──
            ['name' => 'appointments.view',      'module' => 'counselling', 'description' => 'View counselling appointments'],
            ['name' => 'appointments.create',    'module' => 'counselling', 'description' => 'Book counselling appointments'],
            ['name' => 'appointments.manage',    'module' => 'counselling', 'description' => 'Manage appointment schedule and availability'],
            ['name' => 'sessions.notes',         'module' => 'counselling', 'description' => 'Write and view session notes (counsellor only)'],
            ['name' => 'screenings.submit',      'module' => 'counselling', 'description' => 'Submit screening questionnaires (PHQ-9/GAD-7)'],
            ['name' => 'screenings.view',        'module' => 'counselling', 'description' => 'View screening results and risk scores'],
            ['name' => 'crisis.manage',          'module' => 'counselling', 'description' => 'View and manage crisis alerts'],
            ['name' => 'risk.view',              'module' => 'counselling', 'description' => 'View AI risk scores and trends'],

            // ── Reports ──
            ['name' => 'reports.generate',       'module' => 'reports',     'description' => 'Generate system reports'],
            ['name' => 'reports.view',           'module' => 'reports',     'description' => 'View generated reports'],
            ['name' => 'reports.export',         'module' => 'reports',     'description' => 'Export reports (PDF/CSV/XLSX)'],

            // ── AI Features ──
            ['name' => 'ai.triage.view',         'module' => 'ai',         'description' => 'View AI triage predictions'],
            ['name' => 'ai.triage.override',     'module' => 'ai',         'description' => 'Override AI triage decisions'],
            ['name' => 'ai.forecast.view',       'module' => 'ai',         'description' => 'View inventory forecasts'],
            ['name' => 'ai.risk.view',           'module' => 'ai',         'description' => 'View AI mental health risk scores'],
            ['name' => 'ai.scheduling.view',     'module' => 'ai',         'description' => 'View smart scheduling analytics'],
            ['name' => 'ai.reports.generate',    'module' => 'ai',         'description' => 'Generate AI-powered report summaries'],
            ['name' => 'ai.conflict.view',       'module' => 'ai',         'description' => 'View AI conflict detection results'],

            // ── Students ──
            ['name' => 'students.view_own',      'module' => 'students',   'description' => 'View own health records'],
            ['name' => 'students.view_all',      'module' => 'students',   'description' => 'View all student profiles'],
            ['name' => 'students.manage',        'module' => 'students',   'description' => 'Create/edit student profiles'],
        ];

        foreach ($permissions as $perm) {
            $existing = $this->db->table('permissions')->where('name', $perm['name'])->get()->getRow();

            if ($existing === null) {
                $this->db->table('permissions')->insert($perm);
            }
        }

        echo "  Seeded " . count($permissions) . " permissions across " .
             count(array_unique(array_column($permissions, 'module'))) . " modules.\n";
    }
}
