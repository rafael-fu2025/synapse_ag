<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Master seeder — runs all seeders in the correct order.
 *
 * Usage: php spark db:seed DatabaseSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        echo "=== SYNAPSE Database Seeder ===\n\n";

        echo "[1/7] Seeding Roles...\n";
        $this->call('RoleSeeder');

        echo "\n[2/7] Seeding Permissions...\n";
        $this->call('PermissionSeeder');

        echo "\n[3/7] Seeding Role-Permission Assignments...\n";
        $this->call('RolePermissionSeeder');

        echo "\n[4/7] Seeding Admin User...\n";
        $this->call('AdminSeeder');

        echo "\n[5/7] Seeding Medicines & Batches...\n";
        $this->call('MedicineSeeder');

        echo "\n[6/7] Seeding Students...\n";
        $this->call('StudentSeeder');

        echo "\n[7/7] Seeding Assessments & Counsellor...\n";
        $this->call('AssessmentSeeder');

        echo "\n=== Seeding Complete ===\n";
    }
}
