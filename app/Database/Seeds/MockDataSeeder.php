<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class MockDataSeeder extends Seeder
{
    public function run()
    {
        // Clear existing mock users
        $this->db->query("DELETE FROM users WHERE email LIKE 'student%@example.com'");
        
        // Disable foreign key checks for truncation
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        $tables = [
            'medicines', 'medicine_batches',
            'consultations', 'consultation_vitals', 'counselling_appointments',
            'assessment_responses', 'ai_risk_scores', 'notifications', 'audit_logs'
        ];
        foreach ($tables as $table) {
            $this->db->query("TRUNCATE TABLE `$table`");
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
        
        // 1. Generate Students
        $studentIds = [];
        for ($i = 1; $i <= 15; $i++) {
            try {
                // Insert into users
                $userData = [
                    'first_name'    => 'Student' . $i,
                    'last_name'     => 'Test' . $i,
                    'email'         => "student{$i}@example.com",
                    'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
                    'created_at'    => Time::now()->subDays(rand(1, 30))->toDateTimeString()
                ];
                $this->db->table('users')->insert($userData);
                $userId = $this->db->insertID();

                // Assign student role (role_id = 4)
                $this->db->table('user_roles')->insert([
                    'user_id' => $userId,
                    'role_id' => 4
                ]);

                // Insert into students
                $studentData = [
                    'user_id'        => $userId,
                    'student_number' => '2023' . str_pad($i, 4, '0', STR_PAD_LEFT),
                    'course'         => ['BSIT', 'BSCS', 'BSN', 'BSBA'][array_rand(['BSIT', 'BSCS', 'BSN', 'BSBA'])],
                    'year_level'     => rand(1, 4),
                    'date_of_birth'  => Time::now()->subYears(rand(18, 24))->toDateString(),
                    'gender'         => ['Male', 'Female'][array_rand(['Male', 'Female'])],
                    'blood_type'     => ['O+', 'A+', 'B+', 'AB+'][array_rand(['O+', 'A+', 'B+', 'AB+'])],
                    'rfid_tag'       => 'RFID' . uniqid(),
                    'qr_code'        => uniqid(),
                    'created_at'     => $userData['created_at']
                ];
                $this->db->table('students')->insert($studentData);
                $studentIds[] = $this->db->insertID();
            } catch (\Exception $e) {
                echo "Error inserting student {$i}: " . $e->getMessage() . "\n";
            }
        }
        
        // Fetch existing if empty
        if (empty($studentIds)) {
            $existing = $this->db->table('students')->select('id')->get()->getResultArray();
            $studentIds = array_column($existing, 'id');
        }

        // 2. Generate Medicines & Batches
        $medicines = [
            ['name' => 'Paracetamol (Biogesic)', 'category' => 'Painkiller', 'unit' => 'tablet', 'reorder_level' => 100],
            ['name' => 'Ibuprofen (Advil)', 'category' => 'Painkiller', 'unit' => 'tablet', 'reorder_level' => 50],
            ['name' => 'Amoxicillin', 'category' => 'Antibiotic', 'unit' => 'capsule', 'reorder_level' => 30],
            ['name' => 'Cetirizine', 'category' => 'Antihistamine', 'unit' => 'tablet', 'reorder_level' => 20],
            ['name' => 'Loperamide', 'category' => 'Antidiarrheal', 'unit' => 'capsule', 'reorder_level' => 40],
        ];
        
        foreach ($medicines as $med) {
            try {
                $this->db->table('medicines')->insert($med);
                $medId = $this->db->insertID();
                
                // Generate 2 batches per medicine
                $batches = [
                    [
                        'medicine_id' => $medId,
                        'batch_number'=> 'BATCH-A-' . rand(100, 999),
                        'quantity'    => rand(50, 200),
                        'expiry_date' => Time::now()->addMonths(rand(1, 6))->toDateString(),
                        'created_at'  => Time::now()->toDateTimeString()
                    ],
                    [
                        'medicine_id' => $medId,
                        'batch_number'=> 'BATCH-B-' . rand(100, 999),
                        'quantity'    => rand(10, 50),
                        'expiry_date' => Time::now()->addMonths(rand(12, 24))->toDateString(),
                        'created_at'  => Time::now()->toDateTimeString()
                    ]
                ];
                foreach ($batches as $batch) {
                    $this->db->table('medicine_batches')->insert($batch);
                }
            } catch (\Exception $e) {}
        }

        if (empty($studentIds)) {
            echo "No students found to generate relations for.\n";
            return;
        }

        // 3. Generate Consultations & Vitals
        $consultations = [];
        $today = Time::now();
        
        // Generate past and present consultations
        for ($i = 0; $i < 20; $i++) {
            try {
                $studentId = $studentIds[array_rand($studentIds)];
                $isToday = rand(0, 1);
                $date = $isToday ? $today->toDateTimeString() : $today->subDays(rand(1, 14))->toDateTimeString();
                
                $status = $isToday ? ['in_progress', 'completed'][array_rand(['in_progress', 'completed'])] : 'completed';
                
                $this->db->table('consultations')->insert([
                    'student_id'        => $studentId,
                    'attending_user_id' => 1, // assuming user 1 is admin/staff
                    'chief_complaint'   => ['Headache', 'Fever', 'Stomach ache', 'Dizziness', 'Cough'][array_rand(['Headache', 'Fever', 'Stomach ache', 'Dizziness', 'Cough'])],
                    'check_in_method'   => ['walk_in', 'qr', 'rfid'][array_rand(['walk_in', 'qr', 'rfid'])],
                    'triage_priority'   => ['low', 'medium', 'high'][array_rand(['low', 'medium', 'high'])],
                    'consultation_date' => $date,
                    'status'            => $status,
                    'ai_triage_notes'   => 'AI Note: Patient presents with common symptoms. Recommend rest and hydration.',
                    'created_at'        => $date
                ]);
                
                $consultId = $this->db->insertID();
                
                $this->db->table('consultation_vitals')->insert([
                    'consultation_id' => $consultId,
                    'blood_pressure'  => rand(110, 130) . '/' . rand(70, 85),
                    'temperature'     => rand(365, 385) / 10,
                    'heart_rate'      => rand(60, 100),
                    'respiratory_rate'=> rand(12, 20),
                    'created_at'      => $date
                ]);
            } catch (\Exception $e) {}
        }

        // 4. Generate Counselling Appointments & Screenings
        for ($i = 0; $i < 15; $i++) {
            try {
                $studentId = $studentIds[array_rand($studentIds)];
                $isPast = rand(0, 1);
                $date = $isPast ? $today->subDays(rand(1, 14)) : $today->addDays(rand(1, 14));
                
                $this->db->table('counselling_appointments')->insert([
                    'student_id'       => $studentId,
                    'counsellor_id'    => 1, // assuming user 1 is admin/counsellor
                    'appointment_date' => $date->toDateString(),
                    'start_time'       => '10:00:00',
                    'end_time'         => '11:00:00',
                    'type'             => ['routine', 'follow_up', 'urgent'][array_rand(['routine', 'follow_up', 'urgent'])],
                    'status'           => $isPast ? 'completed' : ['scheduled', 'confirmed'][array_rand(['scheduled', 'confirmed'])],
                    'reason'           => 'Academic Stress',
                    'created_at'       => Time::now()->toDateTimeString()
                ]);

                // Add some PHQ-9 screenings
                if (rand(0, 1)) {
                    $score = rand(0, 27);
                    $severity = 'Minimal';
                    if ($score >= 5) $severity = 'Mild';
                    if ($score >= 10) $severity = 'Moderate';
                    if ($score >= 15) $severity = 'Moderately Severe';
                    if ($score >= 20) $severity = 'Severe';

                    $this->db->table('assessment_responses')->insert([
                        'template_id'     => 1, // PHQ-9 template
                        'student_id'      => $studentId,
                        'responses'       => json_encode(['q1' => rand(0,3), 'q2' => rand(0,3)]),
                        'total_score'     => $score,
                    ]);
                    $responseId = $this->db->insertID();

                    $this->db->table('ai_risk_scores')->insert([
                        'student_id'      => $studentId,
                        'assessment_response_id' => $responseId,
                        'risk_level'      => ['low', 'medium', 'high', 'critical'][array_rand(['low', 'medium', 'high', 'critical'])],
                        'risk_factors'    => json_encode(['High stress', 'Sleep issues']),
                        'confidence_score' => rand(70, 95),
                        'created_at'      => $date->toDateTimeString()
                    ]);
                }
            } catch (\Exception $e) {}
        }

        // 5. Generate Notifications
        $this->db->table('notifications')->insertBatch([
            [
                'user_id' => 1,
                'type'    => 'inventory',
                'title'   => 'Low Stock Alert',
                'message' => 'Paracetamol stock is running low (15 tablets left).',
                'data'    => json_encode(['link' => '/inventory/low-stock']),
                'is_read' => 0,
                'created_at' => Time::now()->subMinutes(15)->toDateTimeString()
            ],
            [
                'user_id' => 1,
                'type'    => 'crisis',
                'title'   => 'High Risk Alert',
                'message' => 'High risk PHQ-9 score submitted by Student 4.',
                'data'    => json_encode(['link' => '/counselling/screenings']),
                'is_read' => 0,
                'created_at' => Time::now()->subHours(2)->toDateTimeString()
            ]
        ]);

        echo "Mock Data Seeded Successfully!\n";
    }
}
