<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MedicineSeeder extends Seeder
{
    public function run()
    {
        $medicines = [
            ['generic_name' => 'Paracetamol',       'brand_name' => 'Biogesic',       'category' => 'Analgesic',      'dosage_form' => 'Tablet',     'dosage_strength' => '500mg',  'unit' => 'tablets',  'reorder_threshold' => 50],
            ['generic_name' => 'Mefenamic Acid',    'brand_name' => 'Ponstan',        'category' => 'Analgesic',      'dosage_form' => 'Capsule',    'dosage_strength' => '500mg',  'unit' => 'capsules', 'reorder_threshold' => 30],
            ['generic_name' => 'Ibuprofen',          'brand_name' => 'Advil',          'category' => 'Analgesic',      'dosage_form' => 'Tablet',     'dosage_strength' => '200mg',  'unit' => 'tablets',  'reorder_threshold' => 30],
            ['generic_name' => 'Amoxicillin',        'brand_name' => 'Amoxil',         'category' => 'Antibiotic',     'dosage_form' => 'Capsule',    'dosage_strength' => '500mg',  'unit' => 'capsules', 'reorder_threshold' => 20],
            ['generic_name' => 'Cetirizine',         'brand_name' => 'Zyrtec',         'category' => 'Antihistamine',  'dosage_form' => 'Tablet',     'dosage_strength' => '10mg',   'unit' => 'tablets',  'reorder_threshold' => 30],
            ['generic_name' => 'Loperamide',         'brand_name' => 'Imodium',        'category' => 'Antidiarrheal',  'dosage_form' => 'Capsule',    'dosage_strength' => '2mg',    'unit' => 'capsules', 'reorder_threshold' => 20],
            ['generic_name' => 'Oral Rehydration Salts', 'brand_name' => 'Hydrite',    'category' => 'Supplement',     'dosage_form' => 'Other',      'dosage_strength' => '1 sachet', 'unit' => 'sachets', 'reorder_threshold' => 30],
            ['generic_name' => 'Povidone-Iodine',    'brand_name' => 'Betadine',       'category' => 'Antiseptic',     'dosage_form' => 'Solution',   'dosage_strength' => '10%',    'unit' => 'bottles',  'reorder_threshold' => 5],
            ['generic_name' => 'Adhesive Bandage',   'brand_name' => 'Band-Aid',       'category' => 'First Aid',      'dosage_form' => 'Other',      'dosage_strength' => 'Assorted', 'unit' => 'pcs',   'reorder_threshold' => 50],
            ['generic_name' => 'Gauze Pad',          'brand_name' => null,              'category' => 'First Aid',      'dosage_form' => 'Other',      'dosage_strength' => '4x4 in', 'unit' => 'pcs',    'reorder_threshold' => 30],
            ['generic_name' => 'Elastic Bandage',    'brand_name' => null,              'category' => 'First Aid',      'dosage_form' => 'Other',      'dosage_strength' => '3 inch', 'unit' => 'rolls',   'reorder_threshold' => 10],
            ['generic_name' => 'Diphenhydramine',    'brand_name' => 'Benadryl',       'category' => 'Antihistamine',  'dosage_form' => 'Capsule',    'dosage_strength' => '25mg',   'unit' => 'capsules', 'reorder_threshold' => 20],
            ['generic_name' => 'Hyoscine',           'brand_name' => 'Buscopan',       'category' => 'Analgesic',      'dosage_form' => 'Tablet',     'dosage_strength' => '10mg',   'unit' => 'tablets',  'reorder_threshold' => 20],
            ['generic_name' => 'Ascorbic Acid',      'brand_name' => 'Celin',          'category' => 'Supplement',     'dosage_form' => 'Tablet',     'dosage_strength' => '500mg',  'unit' => 'tablets',  'reorder_threshold' => 30],
            ['generic_name' => 'Camphor + Menthol',  'brand_name' => 'Efficascent Oil','category' => 'Other',          'dosage_form' => 'Solution',   'dosage_strength' => '50ml',   'unit' => 'bottles',  'reorder_threshold' => 5],
        ];

        $batchModel = \Config\Database::connect();

        foreach ($medicines as $med) {
            $existing = $this->db->table('medicines')->where('generic_name', $med['generic_name'])->get()->getRow();
            if ($existing !== null) continue;

            $this->db->table('medicines')->insert($med);
            $medId = $this->db->insertID();

            // Create 2 sample batches per medicine
            $batches = [
                [
                    'medicine_id'        => $medId,
                    'batch_number'       => 'BN-' . date('Y') . '-' . str_pad($medId, 3, '0', STR_PAD_LEFT) . 'A',
                    'quantity_received'  => rand(50, 200),
                    'expiration_date'    => date('Y-m-d', strtotime('+' . rand(3, 12) . ' months')),
                    'received_date'      => date('Y-m-d', strtotime('-' . rand(1, 30) . ' days')),
                    'supplier'           => 'Metro Drug Corp.',
                    'status'             => 'active',
                ],
                [
                    'medicine_id'        => $medId,
                    'batch_number'       => 'BN-' . date('Y') . '-' . str_pad($medId, 3, '0', STR_PAD_LEFT) . 'B',
                    'quantity_received'  => rand(20, 100),
                    'expiration_date'    => date('Y-m-d', strtotime('+' . rand(6, 18) . ' months')),
                    'received_date'      => date('Y-m-d', strtotime('-' . rand(1, 15) . ' days')),
                    'supplier'           => 'Zuellig Pharma Corp.',
                    'status'             => 'active',
                ],
            ];

            foreach ($batches as $batch) {
                $batch['quantity_remaining'] = $batch['quantity_received'];
                $this->db->table('medicine_batches')->insert($batch);
            }
        }

        echo "  Seeded " . count($medicines) . " medicines with 2 batches each.\n";
    }
}
