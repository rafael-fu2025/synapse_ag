<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run()
    {
        $students = [
            ['first_name' => 'Maria',    'last_name' => 'Santos',    'email' => 'maria.santos@feu.edu.ph',    'student_number' => '2024-00001', 'course' => 'BS Computer Science',    'year_level' => 3, 'gender' => 'female', 'blood_type' => 'O+',  'allergen' => 'Penicillin',   'allergy_severity' => 'severe'],
            ['first_name' => 'Juan',     'last_name' => 'Dela Cruz', 'email' => 'juan.delacruz@feu.edu.ph',   'student_number' => '2024-00002', 'course' => 'BS Information Technology','year_level' => 2, 'gender' => 'male',   'blood_type' => 'A+',  'allergen' => null,           'allergy_severity' => null],
            ['first_name' => 'Ana',      'last_name' => 'Reyes',     'email' => 'ana.reyes@feu.edu.ph',       'student_number' => '2024-00003', 'course' => 'BS Psychology',          'year_level' => 4, 'gender' => 'female', 'blood_type' => 'B+',  'allergen' => 'Sulfa drugs',  'allergy_severity' => 'moderate'],
            ['first_name' => 'Carlos',   'last_name' => 'Garcia',    'email' => 'carlos.garcia@feu.edu.ph',   'student_number' => '2024-00004', 'course' => 'BS Nursing',             'year_level' => 1, 'gender' => 'male',   'blood_type' => 'AB+', 'allergen' => null,           'allergy_severity' => null],
            ['first_name' => 'Patricia', 'last_name' => 'Lim',       'email' => 'patricia.lim@feu.edu.ph',    'student_number' => '2024-00005', 'course' => 'BS Accountancy',         'year_level' => 3, 'gender' => 'female', 'blood_type' => 'O-',  'allergen' => 'Aspirin',      'allergy_severity' => 'mild'],
            ['first_name' => 'Rafael',   'last_name' => 'Torres',    'email' => 'rafael.torres@feu.edu.ph',   'student_number' => '2024-00006', 'course' => 'BS Computer Science',    'year_level' => 2, 'gender' => 'male',   'blood_type' => 'A-',  'allergen' => 'Latex',        'allergy_severity' => 'severe'],
            ['first_name' => 'Jasmine',  'last_name' => 'Cruz',      'email' => 'jasmine.cruz@feu.edu.ph',    'student_number' => '2024-00007', 'course' => 'BS Psychology',          'year_level' => 1, 'gender' => 'female', 'blood_type' => 'B-',  'allergen' => null,           'allergy_severity' => null],
            ['first_name' => 'Miguel',   'last_name' => 'Ramos',     'email' => 'miguel.ramos@feu.edu.ph',    'student_number' => '2024-00008', 'course' => 'BS Information Technology','year_level' => 4, 'gender' => 'male',   'blood_type' => 'O+',  'allergen' => 'Ibuprofen',    'allergy_severity' => 'moderate'],
        ];

        $roleModel = $this->db->table('roles')->where('name', 'student')->get()->getRow();

        foreach ($students as $s) {
            // Check if already exists
            $existing = $this->db->table('users')->where('email', $s['email'])->get()->getRow();
            if ($existing !== null) continue;

            // 1. Create user
            $this->db->table('users')->insert([
                'email'         => $s['email'],
                'password_hash' => password_hash('Student@' . date('Y'), PASSWORD_BCRYPT, ['cost' => 12]),
                'first_name'    => $s['first_name'],
                'last_name'     => $s['last_name'],
                'is_active'     => true,
                'email_verified_at' => date('Y-m-d H:i:s'),
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
            $userId = $this->db->insertID();

            // 2. Assign student role
            if ($roleModel) {
                $this->db->table('user_roles')->insert([
                    'user_id'     => $userId,
                    'role_id'     => $roleModel->id,
                    'assigned_at' => date('Y-m-d H:i:s'),
                ]);
            }

            // 3. Create student profile
            $this->db->table('students')->insert([
                'user_id'        => $userId,
                'student_number' => $s['student_number'],
                'qr_code'        => 'QR-' . $s['student_number'],
                'rfid_tag'       => 'RFID-' . $s['student_number'],
                'course'         => $s['course'],
                'year_level'     => $s['year_level'],
                'date_of_birth'  => date('Y-m-d', strtotime('-' . rand(18, 24) . ' years')),
                'gender'         => $s['gender'],
                'blood_type'     => $s['blood_type'],
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);
            $studentId = $this->db->insertID();

            // 4. Emergency contact
            $this->db->table('emergency_contacts')->insert([
                'student_id'   => $studentId,
                'contact_name' => 'Parent of ' . $s['first_name'],
                'relationship' => 'Parent',
                'phone'        => '09' . rand(100000000, 999999999),
                'is_primary'   => true,
            ]);

            // 5. Allergy if any
            if ($s['allergen']) {
                $this->db->table('allergies')->insert([
                    'student_id' => $studentId,
                    'allergen'   => $s['allergen'],
                    'severity'   => $s['allergy_severity'],
                    'reaction'   => 'Known reaction — documented during intake.',
                ]);
            }
        }

        echo "  Seeded " . count($students) . " students with profiles, contacts, and allergies.\n";
    }
}
