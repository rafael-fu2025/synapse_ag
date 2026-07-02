<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            [
                'name'         => 'admin',
                'display_name' => 'System Administrator',
                'description'  => 'Full system access. Manages users, roles, and system configuration.',
            ],
            [
                'name'         => 'clinic_staff',
                'display_name' => 'Clinic Staff',
                'description'  => 'Manages clinic consultations, patient check-in, treatments, and medicine inventory.',
            ],
            [
                'name'         => 'counsellor',
                'display_name' => 'Counsellor',
                'description'  => 'Manages counselling appointments, screenings, risk assessments, and referrals.',
            ],
            [
                'name'         => 'pasimeo_coordinator',
                'display_name' => 'PASIMEO Coordinator',
                'description'  => 'Manages outreach programs, activities, volunteer assignments, and attendance tracking.',
            ],
            [
                'name'         => 'student',
                'display_name' => 'Student',
                'description'  => 'Can book appointments, view personal health records, complete screening forms, and track volunteer hours.',
            ],
        ];

        foreach ($roles as $role) {
            // Check if role already exists
            $existing = $this->db->table('roles')->where('name', $role['name'])->get()->getRow();

            if ($existing === null) {
                $this->db->table('roles')->insert($role);
                echo "  Created role: {$role['name']}\n";
            } else {
                echo "  Role already exists: {$role['name']}\n";
            }
        }
    }
}
