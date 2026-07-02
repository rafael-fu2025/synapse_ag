<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\InventoryForecaster;

class InventoryForecasterTest extends CIUnitTestCase
{
    protected $db;
    private InventoryForecaster $forecaster;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = \Config\Database::connect();
        $this->db->transStart();
        $this->forecaster = new InventoryForecaster();
    }

    protected function tearDown(): void
    {
        $this->db->transRollback();
        parent::tearDown();
    }

    public function testCalculateForecastWithSeasonality()
    {
        // Insert a mock medicine category "Cough & Cold"
        $this->db->table('medicines')->insert([
            'id' => 99990,
            'generic_name' => 'Mock Flu Med',
            'category' => 'cough & cold',
            'unit' => 'tablet',
            'reorder_threshold' => 100,
            'is_active' => true
        ]);
        // Insert a mock user for performed_by
        $this->db->table('users')->insert([
            'id' => 99999,
            'email' => 'staff@synapse.edu.ph',
            'password_hash' => 'dummy',
            'first_name' => 'Mock',
            'last_name' => 'Staff',
            'is_active' => true
        ]);

        $this->db->table('medicine_batches')->insert([
            'id' => 99991,
            'medicine_id' => 99990,
            'batch_number' => 'BATCH-TEST',
            'quantity_received' => 500,
            'quantity_remaining' => 200,
            'received_date' => date('Y-m-d'),
            'expiration_date' => date('Y-m-d', strtotime('+1 year'))
        ]);

        // Insert historical usage (150 tablets dispensed in last 30 days)
        // This is 5 tablets per day.
        $this->db->table('inventory_transactions')->insert([
            'medicine_batch_id' => 99991,
            'transaction_type' => 'dispensed',
            'quantity' => 150,
            'transaction_date' => date('Y-m-d H:i:s', strtotime('-15 days')),
            'performed_by' => 99999
        ]);

        $result = $this->forecaster->calculateForecast(99990, 200, 100);

        // Daily rate should be 150/30 = 5.0
        // If current month is between June (6) and Oct (10), seasonality = 1.25 -> 5.0 * 1.25 = 6.25
        $currentMonth = (int) date('m');
        if ($currentMonth >= 6 && $currentMonth <= 10) {
            $this->assertEquals(6.25, $result['predicted_daily_usage']);
            $this->assertEquals(1.25, $result['seasonality_factor']);
        } else {
            $this->assertEquals(5.0, $result['predicted_daily_usage']);
            $this->assertEquals(1.0, $result['seasonality_factor']);
        }

        $this->assertEquals(99990, $result['medicine_id']);
        
        // Ensure it was saved to DB
        $dbRecord = $this->db->table('ai_inventory_forecasts')->where('medicine_id', 99990)->get()->getRowArray();
        $this->assertNotNull($dbRecord);
    }

    public function testCalculateForecastNoHistory()
    {
        // Medicine with no history
        $this->db->table('medicines')->insert([
            'id' => 99992,
            'generic_name' => 'No History Med',
            'category' => 'vitamins',
            'unit' => 'tablet',
            'reorder_threshold' => 50,
            'is_active' => true
        ]);

        $result = $this->forecaster->calculateForecast(99992, 100, 50);

        // Should use fallback daily rate 0.25 (1 unit every 4 days)
        $this->assertEquals(0.25, $result['predicted_daily_usage']);
    }
}
