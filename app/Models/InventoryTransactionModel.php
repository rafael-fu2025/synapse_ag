<?php

namespace App\Models;

use CodeIgniter\Model;

class InventoryTransactionModel extends Model
{
    protected $table            = 'inventory_transactions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'medicine_batch_id', 'transaction_type', 'quantity',
        'reference_type', 'reference_id', 'performed_by', 'notes',
    ];

    protected $useTimestamps = false;

    /**
     * Get transactions for a specific batch.
     */
    public function getByBatch(int $batchId): array
    {
        return $this->select('inventory_transactions.*, users.first_name, users.last_name')
            ->join('users', 'users.id = inventory_transactions.performed_by')
            ->where('medicine_batch_id', $batchId)
            ->orderBy('transaction_date', 'DESC')
            ->findAll();
    }

    /**
     * Log a batch receipt transaction.
     */
    public function logReceipt(int $batchId, int $quantity, int $performedBy, ?string $notes = null): bool
    {
        return (bool) $this->insert([
            'medicine_batch_id' => $batchId,
            'transaction_type'  => 'received',
            'quantity'          => $quantity,
            'performed_by'      => $performedBy,
            'notes'             => $notes ?? 'Batch received',
        ]);
    }
}
