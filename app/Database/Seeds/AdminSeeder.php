<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run()
    {
        $email = 'admin@synapse.edu.ph';

        // Check if admin already exists
        $existing = $this->db->table('users')->where('email', $email)->get()->getRow();

        if ($existing !== null) {
            echo "  Admin user already exists: {$email}\n";
            return;
        }

        // Create admin user
        $this->db->table('users')->insert([
            'email'         => $email,
            'password_hash' => password_hash('Synapse@2027', PASSWORD_BCRYPT, ['cost' => 12]),
            'first_name'    => 'System',
            'last_name'     => 'Administrator',
            'is_active'     => true,
            'email_verified_at' => date('Y-m-d H:i:s'),
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        $userId = $this->db->insertID();

        // Assign admin role
        $adminRole = $this->db->table('roles')->where('name', 'admin')->get()->getRow();

        if ($adminRole !== null) {
            $this->db->table('user_roles')->insert([
                'user_id'     => $userId,
                'role_id'     => $adminRole->id,
                'assigned_at' => date('Y-m-d H:i:s'),
            ]);
            echo "  Created admin user: {$email} (password: Synapse@2027)\n";
            echo "  Assigned role: admin\n";
        } else {
            echo "  WARNING: Admin role not found. Run RoleSeeder first.\n";
        }
    }
}
