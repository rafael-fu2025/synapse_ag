<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Drops the PASIMEO outreach / volunteer tables.
 *
 * PASIMEO was removed from the capstone scope in July 2026. This migration
 * is safe to run on installs that already have the tables (it cascades
 * safely) and is a no-op on installs that don't.
 *
 * Tables dropped (in order so foreign keys are respected):
 *   1. outreach_attendance      (FK -> outreach_activities)
 *   2. volunteer_assignments    (FK -> outreach_activities, users)
 *   3. outreach_activities      (FK -> outreach_programs)
 *   4. outreach_programs        (FK -> users)
 *   5. volunteer_workload_scores (no FK out, but related AI table)
 */
class DropPasimeoTables extends Migration
{
    public function up(): void
    {
        // Drop in reverse-dependency order so foreign keys don't block us.
        $tables = [
            'outreach_attendance',
            'volunteer_assignments',
            'outreach_activities',
            'outreach_programs',
            'volunteer_workload_scores',
        ];

        foreach ($tables as $table) {
            // $this->forge->dropTable(..., true) ignores "table doesn't exist"
            // errors so the migration is idempotent across fresh installs.
            $this->forge->dropTable($table, true);
        }
    }

    public function down(): void
    {
        // Recreate the dropped tables for rollback. Schemas mirror
        // Database/synapse_ag.sql prior to the July 2026 PASIMEO removal.
        // If you only want to roll back partially, restore from your
        // pre-migration backup instead of relying on this path.

        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 255],
            'description'    => ['type' => 'TEXT', 'null' => true],
            'coordinator_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'start_date'     => ['type' => 'DATE', 'null' => true],
            'end_date'       => ['type' => 'DATE', 'null' => true],
            'status'         => ['type' => 'ENUM', 'constraint' => ['planning','active','completed','cancelled'], 'default' => 'planning'],
            'created_at'     => ['type' => 'TIMESTAMP', 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
            'updated_at'     => ['type' => 'TIMESTAMP', 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->createTable('outreach_programs');
    }
}