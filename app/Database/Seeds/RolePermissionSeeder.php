<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // ── RBAC Matrix ──
        // Maps role names → list of permission names
        $matrix = [
            'admin' => [
                // Admin gets EVERYTHING
                'users.view', 'users.create', 'users.edit', 'users.deactivate', 'roles.manage', 'audit.view',
                'consultations.view', 'consultations.create', 'consultations.edit',
                'vitals.record', 'treatments.create', 'allergies.manage',
                'referrals.create', 'referrals.view', 'referrals.respond', 'checkin.perform',
                'medicines.view', 'medicines.manage', 'inventory.dispense', 'inventory.adjust', 'inventory.reports',
                'appointments.view', 'appointments.create', 'appointments.manage', 'sessions.notes',
                'screenings.submit', 'screenings.view', 'crisis.manage', 'risk.view',
                'programs.view', 'programs.manage', 'activities.manage', 'volunteers.assign',
                'attendance.manage', 'attendance.view_own',
                'reports.generate', 'reports.view', 'reports.export',
                'ai.triage.view', 'ai.triage.override', 'ai.forecast.view', 'ai.risk.view',
                'ai.scheduling.view', 'ai.reports.generate', 'ai.conflict.view',
                'students.view_own', 'students.view_all', 'students.manage',
            ],

            'clinic_staff' => [
                'consultations.view', 'consultations.create', 'consultations.edit',
                'vitals.record', 'treatments.create', 'allergies.manage',
                'referrals.create', 'referrals.view', 'referrals.respond', 'checkin.perform',
                'medicines.view', 'medicines.manage', 'inventory.dispense', 'inventory.adjust', 'inventory.reports',
                'ai.triage.view', 'ai.triage.override', 'ai.forecast.view',
                'students.view_all',
                'reports.view',
            ],

            'counsellor' => [
                'appointments.view', 'appointments.create', 'appointments.manage', 'sessions.notes',
                'screenings.view', 'crisis.manage', 'risk.view',
                'referrals.create', 'referrals.view', 'referrals.respond',
                'ai.risk.view', 'ai.scheduling.view',
                'reports.view',
            ],

            'pasimeo_coordinator' => [
                'programs.view', 'programs.manage',
                'activities.manage', 'volunteers.assign',
                'attendance.manage', 'attendance.view_own',
                'ai.conflict.view',
                'reports.view',
            ],

            'student' => [
                'appointments.view', 'appointments.create',
                'screenings.submit',
                'students.view_own',
                'attendance.view_own',
            ],
        ];

        $assignedCount = 0;

        foreach ($matrix as $roleName => $permNames) {
            // Get role ID
            $role = $this->db->table('roles')->where('name', $roleName)->get()->getRow();

            if ($role === null) {
                echo "  WARNING: Role '{$roleName}' not found — skipping.\n";
                continue;
            }

            foreach ($permNames as $permName) {
                // Get permission ID
                $perm = $this->db->table('permissions')->where('name', $permName)->get()->getRow();

                if ($perm === null) {
                    echo "  WARNING: Permission '{$permName}' not found — skipping.\n";
                    continue;
                }

                // Check if already assigned
                $existing = $this->db->table('role_permissions')
                    ->where('role_id', $role->id)
                    ->where('permission_id', $perm->id)
                    ->get()->getRow();

                if ($existing === null) {
                    $this->db->table('role_permissions')->insert([
                        'role_id'       => $role->id,
                        'permission_id' => $perm->id,
                    ]);
                    $assignedCount++;
                }
            }

            echo "  Assigned " . count($permNames) . " permissions to '{$roleName}'\n";
        }

        echo "  Total new assignments: {$assignedCount}\n";
    }
}
