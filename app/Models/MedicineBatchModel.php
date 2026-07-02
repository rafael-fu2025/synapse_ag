<?php

namespace App\Models;

use CodeIgniter\Model;

class MedicineBatchModel extends Model
{
    protected $table            = 'medicine_batches';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'medicine_id', 'batch_number', 'quantity_received',
        'quantity_remaining', 'expiration_date', 'received_date',
        'supplier', 'notes', 'status',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $validationRules = [
        'medicine_id'        => 'required|is_natural_no_zero',
        'batch_number'       => 'required|max_length[100]',
        'quantity_received'  => 'required|is_natural_no_zero',
        'quantity_remaining' => 'required|is_natural',
        'expiration_date'    => 'required|valid_date',
        'received_date'      => 'required|valid_date',
    ];

    /**
     * Get all active (non-depleted, non-expired) batches for a medicine.
     * Ordered by expiration date (FEFO — First Expiry, First Out).
     */
    public function getActiveBatches(int $medicineId): array
    {
        return $this->where('medicine_id', $medicineId)
            ->where('status', 'active')
            ->where('quantity_remaining >', 0)
            ->orderBy('expiration_date', 'ASC') // FEFO
            ->findAll();
    }

    /**
     * Get the FEFO batch (earliest expiring, with stock) for a medicine.
     */
    public function getFEFOBatch(int $medicineId): ?array
    {
        return $this->where('medicine_id', $medicineId)
            ->where('status', 'active')
            ->where('quantity_remaining >', 0)
            ->where('expiration_date >', date('Y-m-d'))
            ->orderBy('expiration_date', 'ASC')
            ->first();
    }

    /**
     * Get batches expiring within N days.
     */
    public function getExpiringBatches(int $days = 30): array
    {
        $futureDate = date('Y-m-d', strtotime("+{$days} days"));

        return $this->select('medicine_batches.*, medicines.generic_name, medicines.brand_name, medicines.unit')
            ->join('medicines', 'medicines.id = medicine_batches.medicine_id')
            ->where('medicine_batches.status', 'active')
            ->where('medicine_batches.quantity_remaining >', 0)
            ->where('medicine_batches.expiration_date <=', $futureDate)
            ->orderBy('medicine_batches.expiration_date', 'ASC')
            ->findAll();
    }

    /**
     * Decrement quantity from a batch (used during dispensing).
     * Returns false if insufficient stock.
     */
    public function decrementStock(int $batchId, int $quantity): bool
    {
        $batch = $this->find($batchId);

        if ($batch === null || $batch['quantity_remaining'] < $quantity) {
            return false;
        }

        $newQty = $batch['quantity_remaining'] - $quantity;

        $this->update($batchId, [
            'quantity_remaining' => $newQty,
            'status'            => $newQty === 0 ? 'depleted' : 'active',
        ]);

        return true;
    }
}
