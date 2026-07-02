<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class OutreachSeeder extends Seeder
{
    public function run()
    {
        // 1. Seed PASIMEO Coordinator
        $coordinatorEmail = 'pasimeo@synapse.app';
        $coordinator = $this->db->table('users')->where('email', $coordinatorEmail)->get()->getRow();

        if ($coordinator === null) {
            $this->db->table('users')->insert([
                'email'             => $coordinatorEmail,
                'password_hash'     => password_hash('Pasimeo@2027', PASSWORD_BCRYPT, ['cost' => 12]),
                'first_name'        => 'Juan',
                'last_name'         => 'Pasimeo',
                'is_active'         => true,
                'email_verified_at' => date('Y-m-d H:i:s'),
                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);
            $coordinatorId = $this->db->insertID();

            // Assign pasimeo_coordinator role
            $role = $this->db->table('roles')->where('name', 'pasimeo_coordinator')->get()->getRow();
            if ($role) {
                $this->db->table('user_roles')->insert([
                    'user_id'     => $coordinatorId,
                    'role_id'     => $role->id,
                    'assigned_at' => date('Y-m-d H:i:s'),
                ]);
            }
            echo "  Coordinator: Juan Pasimeo (pasimeo@synapse.app) seeded.\n";
        } else {
            $coordinatorId = $coordinator->id;
        }

        // 2. Seed outreach programs
        $programs = [
            [
                'name'           => 'Health Mission 2026',
                'description'    => 'Annual health assessment and consultation mission in partnership with local barangays.',
                'coordinator_id' => $coordinatorId,
                'start_date'     => date('Y-m-d', strtotime('-1 month')),
                'end_date'       => date('Y-m-d', strtotime('+3 months')),
                'status'         => 'active',
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ],
            [
                'name'           => 'Barangay Wellness Caravan',
                'description'    => 'Mobile dental and basic pediatric wellness checks in rural communities.',
                'coordinator_id' => $coordinatorId,
                'start_date'     => date('Y-m-d', strtotime('+1 month')),
                'end_date'       => date('Y-m-d', strtotime('+2 months')),
                'status'         => 'planning',
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ],
            [
                'name'           => 'Disaster Response & Basic First Aid',
                'description'    => 'First aid training and disaster response simulation with community volunteers.',
                'coordinator_id' => $coordinatorId,
                'start_date'     => date('Y-m-d', strtotime('-2 months')),
                'end_date'       => date('Y-m-d', strtotime('-1 month')),
                'status'         => 'completed',
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ],
        ];

        foreach ($programs as $prog) {
            $existing = $this->db->table('outreach_programs')->where('name', $prog['name'])->get()->getRow();
            if ($existing === null) {
                $this->db->table('outreach_programs')->insert($prog);
                $progId = $this->db->insertID();

                // Seed some activities for this program
                $activities = [];
                if ($prog['name'] === 'Health Mission 2026') {
                    // Active Program: 1 upcoming, 1 ongoing, 1 completed
                    $activities = [
                        [
                            'program_id'     => $progId,
                            'title'          => 'Brgy. 599 Medical Mission Check-up',
                            'description'    => 'Blood pressure testing, blood sugar screenings, and general consultation.',
                            'location'       => 'Barangay 599 Covered Court',
                            'activity_date'  => date('Y-m-d', strtotime('+3 days')),
                            'start_time'     => '08:00:00',
                            'end_time'       => '12:00:00',
                            'max_volunteers' => 5,
                            'status'         => 'upcoming',
                            'created_at'     => date('Y-m-d H:i:s'),
                            'updated_at'     => date('Y-m-d H:i:s'),
                        ],
                        [
                            'program_id'     => $progId,
                            'title'          => 'Basic Vital Signs Training Session',
                            'description'    => 'On-site training of community health workers on taking vitals.',
                            'location'       => 'Brgy. 599 Health Center',
                            'activity_date'  => date('Y-m-d'),
                            'start_time'     => '09:00:00',
                            'end_time'       => '15:00:00',
                            'max_volunteers' => 3,
                            'status'         => 'ongoing',
                            'created_at'     => date('Y-m-d H:i:s'),
                            'updated_at'     => date('Y-m-d H:i:s'),
                        ],
                        [
                            'program_id'     => $progId,
                            'title'          => 'Dental Hygiene Workshop for Kids',
                            'description'    => 'Teaching children proper brushing techniques and handing out dental kits.',
                            'location'       => 'Brgy. 599 Day Care Center',
                            'activity_date'  => date('Y-m-d', strtotime('-5 days')),
                            'start_time'     => '10:00:00',
                            'end_time'       => '12:00:00',
                            'max_volunteers' => 4,
                            'status'         => 'completed',
                            'created_at'     => date('Y-m-d H:i:s'),
                            'updated_at'     => date('Y-m-d H:i:s'),
                        ]
                    ];
                } else if ($prog['name'] === 'Barangay Wellness Caravan') {
                    // Planning Program: 2 upcoming/planning
                    $activities = [
                        [
                            'program_id'     => $progId,
                            'title'          => 'Mobile Dental Mission Planning Meet',
                            'description'    => 'Briefing and preparation of dental equipment and volunteer orientation.',
                            'location'       => 'University Amphitheater',
                            'activity_date'  => date('Y-m-d', strtotime('+15 days')),
                            'start_time'     => '14:00:00',
                            'end_time'       => '16:00:00',
                            'max_volunteers' => 10,
                            'status'         => 'upcoming',
                            'created_at'     => date('Y-m-d H:i:s'),
                            'updated_at'     => date('Y-m-d H:i:s'),
                        ]
                    ];
                } else {
                    // Completed Program: 1 completed activity
                    $activities = [
                        [
                            'program_id'     => $progId,
                            'title'          => 'First Aid & CPR Certification Seminar',
                            'description'    => 'Red Cross certified CPR training course for students and community members.',
                            'location'       => 'FEU Health Sciences Building Room 402',
                            'activity_date'  => date('Y-m-d', strtotime('-15 days')),
                            'start_time'     => '08:00:00',
                            'end_time'       => '17:00:00',
                            'max_volunteers' => 6,
                            'status'         => 'completed',
                            'created_at'     => date('Y-m-d H:i:s'),
                            'updated_at'     => date('Y-m-d H:i:s'),
                        ]
                    ];
                }

                foreach ($activities as $act) {
                    $this->db->table('outreach_activities')->insert($act);
                    $actId = $this->db->insertID();

                    // Seed volunteer assignments for these activities
                    $students = $this->db->table('users')
                        ->join('user_roles', 'user_roles.user_id = users.id')
                        ->join('roles', 'roles.id = user_roles.role_id')
                        ->where('roles.name', 'student')
                        ->select('users.id, users.first_name, users.last_name')
                        ->get()->getResultArray();

                    if (!empty($students)) {
                        // Assign the first student to all activities
                        $student1 = $students[0];
                        $this->db->table('volunteer_assignments')->insert([
                            'activity_id'     => $actId,
                            'user_id'         => $student1['id'],
                            'assigned_role'   => 'First Aider / Assistant',
                            'status'          => 'confirmed',
                            'assigned_by'     => $coordinatorId,
                        ]);

                        if ($act['status'] === 'completed') {
                            $checkIn = date('Y-m-d H:i:s', strtotime($act['activity_date'] . ' ' . $act['start_time']));
                            $checkOut = date('Y-m-d H:i:s', strtotime($act['activity_date'] . ' ' . $act['end_time']));
                            $hours = round((strtotime($checkOut) - strtotime($checkIn)) / 3600, 2);

                            $this->db->table('outreach_attendance')->insert([
                                'activity_id'     => $actId,
                                'user_id'         => $student1['id'],
                                'check_in_time'   => $checkIn,
                                'check_out_time'  => $checkOut,
                                'check_in_method' => 'manual',
                                'hours_credited'  => $hours,
                                'verified_by'     => $coordinatorId,
                            ]);
                        }

                        // If there is a second student
                        if (count($students) > 1) {
                            $student2 = $students[1];
                            $this->db->table('volunteer_assignments')->insert([
                                'activity_id'     => $actId,
                                'user_id'         => $student2['id'],
                                'assigned_role'   => 'Logistics / Support',
                                'status'          => ($act['status'] === 'completed') ? 'confirmed' : 'assigned',
                                'assigned_by'     => $coordinatorId,
                            ]);

                            if ($act['status'] === 'completed') {
                                $checkIn = date('Y-m-d H:i:s', strtotime($act['activity_date'] . ' ' . $act['start_time']));
                                $checkOut = date('Y-m-d H:i:s', strtotime($act['activity_date'] . ' ' . $act['end_time']));
                                $hours = round((strtotime($checkOut) - strtotime($checkIn)) / 3600, 2);

                                $this->db->table('outreach_attendance')->insert([
                                    'activity_id'     => $actId,
                                    'user_id'         => $student2['id'],
                                    'check_in_time'   => $checkIn,
                                    'check_out_time'  => $checkOut,
                                    'check_in_method' => 'manual',
                                    'hours_credited'  => $hours,
                                    'verified_by'     => null, // Unverified
                                ]);
                            }
                        }
                    }
                }
                echo "  Seeded program '{$prog['name']}' with activities, assignments, and attendance.\n";
            } else {
                echo "  Outreach program '{$prog['name']}' already exists.\n";
            }
        }
    }
}
