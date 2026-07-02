<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds PHQ-9, GAD-7, and Intake Form assessment templates.
 */
class AssessmentSeeder extends Seeder
{
    public function run()
    {
        // Get admin user for created_by
        $admin = $this->db->table('users')->where('email', 'admin@synapse.app')->get()->getRow();
        $createdBy = $admin ? $admin->id : 1;

        // ============================================================
        // PHQ-9 (Patient Health Questionnaire — Depression)
        // ============================================================
        $existing = $this->db->table('assessment_templates')->where('title', 'PHQ-9 Depression Screening')->get()->getRow();
        if ($existing === null) {
            $this->db->table('assessment_templates')->insert([
                'title'       => 'PHQ-9 Depression Screening',
                'description' => 'Over the last 2 weeks, how often have you been bothered by any of the following problems? Score each item 0 (Not at all) to 3 (Nearly every day). Total range: 0-27.',
                'type'        => 'screening',
                'is_active'   => true,
                'created_by'  => $createdBy,
            ]);
            $phq9Id = $this->db->insertID();

            $phq9Questions = [
                'Little interest or pleasure in doing things',
                'Feeling down, depressed, or hopeless',
                'Trouble falling or staying asleep, or sleeping too much',
                'Feeling tired or having little energy',
                'Poor appetite or overeating',
                'Feeling bad about yourself — or that you are a failure or have let yourself or your family down',
                'Trouble concentrating on things, such as reading the newspaper or watching television',
                'Moving or speaking so slowly that other people could have noticed? Or the opposite — being so fidgety or restless that you have been moving around a lot more than usual',
                'Thoughts that you would be better off dead, or of hurting yourself in some way',
            ];

            $likertOptions = json_encode([
                ['value' => 0, 'label' => 'Not at all'],
                ['value' => 1, 'label' => 'Several days'],
                ['value' => 2, 'label' => 'More than half the days'],
                ['value' => 3, 'label' => 'Nearly every day'],
            ]);

            foreach ($phq9Questions as $i => $text) {
                $this->db->table('assessment_questions')->insert([
                    'template_id'   => $phq9Id,
                    'question_text' => $text,
                    'question_type' => 'likert',
                    'options'       => $likertOptions,
                    'order_index'   => $i,
                    'is_required'   => true,
                ]);
            }
            echo "  PHQ-9: 1 template + 9 questions seeded.\n";
        }

        // ============================================================
        // GAD-7 (Generalized Anxiety Disorder)
        // ============================================================
        $existing = $this->db->table('assessment_templates')->where('title', 'GAD-7 Anxiety Screening')->get()->getRow();
        if ($existing === null) {
            $this->db->table('assessment_templates')->insert([
                'title'       => 'GAD-7 Anxiety Screening',
                'description' => 'Over the last 2 weeks, how often have you been bothered by the following problems? Score each item 0 (Not at all) to 3 (Nearly every day). Total range: 0-21.',
                'type'        => 'screening',
                'is_active'   => true,
                'created_by'  => $createdBy,
            ]);
            $gad7Id = $this->db->insertID();

            $gad7Questions = [
                'Feeling nervous, anxious, or on edge',
                'Not being able to stop or control worrying',
                'Worrying too much about different things',
                'Trouble relaxing',
                'Being so restless that it\'s hard to sit still',
                'Becoming easily annoyed or irritable',
                'Feeling afraid as if something awful might happen',
            ];

            foreach ($gad7Questions as $i => $text) {
                $this->db->table('assessment_questions')->insert([
                    'template_id'   => $gad7Id,
                    'question_text' => $text,
                    'question_type' => 'likert',
                    'options'       => $likertOptions,
                    'order_index'   => $i,
                    'is_required'   => true,
                ]);
            }
            echo "  GAD-7: 1 template + 7 questions seeded.\n";
        }

        // ============================================================
        // Intake Form
        // ============================================================
        $existing = $this->db->table('assessment_templates')->where('title', 'Counselling Intake Form')->get()->getRow();
        if ($existing === null) {
            $this->db->table('assessment_templates')->insert([
                'title'       => 'Counselling Intake Form',
                'description' => 'Initial intake assessment to understand the student\'s presenting concern and urgency.',
                'type'        => 'intake',
                'is_active'   => true,
                'created_by'  => $createdBy,
            ]);
            $intakeId = $this->db->insertID();

            $intakeQuestions = [
                ['text' => 'What is your primary reason for seeking counselling today?', 'type' => 'text'],
                ['text' => 'How long have you been experiencing this concern?', 'type' => 'multiple_choice', 'options' => json_encode([
                    ['value' => 'less_1_week',  'label' => 'Less than 1 week'],
                    ['value' => '1_4_weeks',    'label' => '1-4 weeks'],
                    ['value' => '1_3_months',   'label' => '1-3 months'],
                    ['value' => '3_plus_months', 'label' => 'More than 3 months'],
                ])],
                ['text' => 'On a scale of 1-5, how urgently do you feel you need support?', 'type' => 'scale'],
                ['text' => 'Have you previously received counselling or mental health support?', 'type' => 'yes_no'],
                ['text' => 'Is there anything else you\'d like your counsellor to know before your session?', 'type' => 'text'],
            ];

            foreach ($intakeQuestions as $i => $q) {
                $this->db->table('assessment_questions')->insert([
                    'template_id'   => $intakeId,
                    'question_text' => $q['text'],
                    'question_type' => $q['type'],
                    'options'       => $q['options'] ?? null,
                    'order_index'   => $i,
                    'is_required'   => $q['type'] !== 'text',
                ]);
            }
            echo "  Intake Form: 1 template + 5 questions seeded.\n";
        }

        // ============================================================
        // Seed counsellor user + availability
        // ============================================================
        $counsellorEmail = 'counsellor@synapse.app';
        $existingCounsellor = $this->db->table('users')->where('email', $counsellorEmail)->get()->getRow();

        if ($existingCounsellor === null) {
            $this->db->table('users')->insert([
                'email'             => $counsellorEmail,
                'password_hash'     => password_hash('Counsellor@2027', PASSWORD_BCRYPT, ['cost' => 12]),
                'first_name'        => 'Dr. Elena',
                'last_name'         => 'Reyes',
                'is_active'         => true,
                'email_verified_at' => date('Y-m-d H:i:s'),
                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);
            $counsellorUserId = $this->db->insertID();

            // Assign counsellor role
            $roleModel = $this->db->table('roles')->where('name', 'counsellor')->get()->getRow();
            if ($roleModel) {
                $this->db->table('user_roles')->insert([
                    'user_id'     => $counsellorUserId,
                    'role_id'     => $roleModel->id,
                    'assigned_at' => date('Y-m-d H:i:s'),
                ]);
            }

            // Set availability: Mon-Fri, 9 AM - 12 PM and 1 PM - 4 PM
            for ($d = 1; $d <= 5; $d++) {
                $this->db->table('counsellor_availability')->insert([
                    'counsellor_id' => $counsellorUserId,
                    'day_of_week'   => $d,
                    'start_time'    => '09:00:00',
                    'end_time'      => '10:00:00',
                    'max_slots'     => 1,
                    'is_active'     => true,
                ]);
                $this->db->table('counsellor_availability')->insert([
                    'counsellor_id' => $counsellorUserId,
                    'day_of_week'   => $d,
                    'start_time'    => '10:00:00',
                    'end_time'      => '11:00:00',
                    'max_slots'     => 1,
                    'is_active'     => true,
                ]);
                $this->db->table('counsellor_availability')->insert([
                    'counsellor_id' => $counsellorUserId,
                    'day_of_week'   => $d,
                    'start_time'    => '13:00:00',
                    'end_time'      => '14:00:00',
                    'max_slots'     => 1,
                    'is_active'     => true,
                ]);
                $this->db->table('counsellor_availability')->insert([
                    'counsellor_id' => $counsellorUserId,
                    'day_of_week'   => $d,
                    'start_time'    => '14:00:00',
                    'end_time'      => '15:00:00',
                    'max_slots'     => 1,
                    'is_active'     => true,
                ]);
            }
            echo "  Counsellor: Dr. Elena Reyes + Mon-Fri availability (4 slots/day) seeded.\n";
        }
    }
}
