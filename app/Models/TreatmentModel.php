<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\Exceptions\DatabaseException;

class TreatmentModel extends Model
{
    protected $table            = 'treatments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'consultation_id', 'medicine_batch_id', 'treatment_type',
        'description', 'quantity_used', 'administered_by',
    ];

    protected $useTimestamps = false;

    protected $validationRules = [
        'consultation_id' => 'required|is_natural_no_zero',
        'treatment_type'  => 'required|in_list[medication,first_aid,procedure,referral,other]',
        'description'     => 'required|min_length[3]',
        'administered_by' => 'required|is_natural_no_zero',
    ];

    /**
     * Get all treatments for a consultation.
     */
    public function getByConsultation(int $consultationId): array
    {
        return $this->select('treatments.*, medicines.generic_name, medicines.brand_name, medicines.unit, medicine_batches.batch_number, users.first_name as admin_first, users.last_name as admin_last')
            ->join('medicine_batches', 'medicine_batches.id = treatments.medicine_batch_id', 'left')
            ->join('medicines', 'medicines.id = medicine_batches.medicine_id', 'left')
            ->join('users', 'users.id = treatments.administered_by')
            ->where('consultation_id', $consultationId)
            ->findAll();
    }

    /**
     * Dispense medicine — wraps treatment creation + batch decrement +
     * inventory transaction in a single DB transaction.
     *
     * Returns the treatment ID on success, or false on failure.
     */
    public function dispense(array $treatmentData, int $batchId, int $quantity): int|false
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // 1. Create treatment record
            $treatmentData['medicine_batch_id'] = $batchId;
            $treatmentData['quantity_used']     = $quantity;
            $treatmentData['treatment_type']    = 'medication';

            $this->insert($treatmentData);
            $treatmentId = $this->getInsertID();

            // 2. Decrement batch stock
            $batchModel = new MedicineBatchModel();
            if (! $batchModel->decrementStock($batchId, $quantity)) {
                $db->transRollback();
                return false;
            }

            // 3. Create inventory transaction
            $invModel = new InventoryTransactionModel();
            $invModel->insert([
                'medicine_batch_id' => $batchId,
                'transaction_type'  => 'dispensed',
                'quantity'          => $quantity,
                'reference_type'    => 'consultation',
                'reference_id'      => $treatmentData['consultation_id'],
                'performed_by'      => $treatmentData['administered_by'],
                'notes'             => 'Dispensed during consultation',
            ]);

            // 4. Check if low stock — send notification
            $batch    = $batchModel->find($batchId);
            $medicine = (new MedicineModel())->find($batch['medicine_id']);

            if ($medicine) {
                $medicineWithStock = (new MedicineModel())->getWithBatches((int) $medicine['id']);
                if ($medicineWithStock && $medicineWithStock['total_stock'] <= $medicine['reorder_threshold']) {
                    $notifModel = new NotificationModel();
                    $notifModel->createNotification(
                        null, // broadcast to clinic staff
                        'low_stock',
                        'Low Stock Alert',
                        "Medicine \"{$medicine['generic_name']}\" is below reorder threshold ({$medicineWithStock['total_stock']} {$medicine['unit']} remaining).",
                        'inventory',
                        'medicines',
                        (int) $medicine['id']
                    );
                }
            }

            $db->transComplete();

            return $db->transStatus() ? $treatmentId : false;
        } catch (DatabaseException $e) {
            $db->transRollback();
            log_message('error', 'Dispense failed: ' . $e->getMessage());
            return false;
        }
    }
}
