<?php

namespace App\Models;

use CodeIgniter\Model;

class MedicineModel extends Model
{
    protected $table            = 'medicines';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'generic_name', 'brand_name', 'category', 'dosage_form',
        'dosage_strength', 'unit', 'reorder_threshold',
        'description', 'is_active',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'generic_name' => 'required|max_length[200]',
        'unit'         => 'required|max_length[50]',
    ];

    /**
     * Get all active medicines.
     */
    public function getActive(): array
    {
        return $this->where('is_active', true)
            ->orderBy('generic_name', 'ASC')
            ->findAll();
    }

    /**
     * Search medicines by name.
     */
    public function search(string $query): array
    {
        // Escape LIKE wildcards in user input. See escape_like_helper.
        $q = escape_like($query);

        return $this->where('is_active', true)
            ->groupStart()
                ->like('generic_name', $q)
                ->orLike('brand_name', $q)
            ->groupEnd()
            ->orderBy('generic_name', 'ASC')
            ->findAll();
    }

    /**
     * Paged search — returns rows + total count.
     */
    public function searchPaged(string $query, int $perPage, int $offset): array
    {
        $q = escape_like($query);

        $count = $this->where('is_active', true)
            ->groupStart()
                ->like('generic_name', $q)
                ->orLike('brand_name', $q)
            ->groupEnd()
            ->countAllResults();

        $rows = $this->where('is_active', true)
            ->groupStart()
                ->like('generic_name', $q)
                ->orLike('brand_name', $q)
            ->groupEnd()
            ->orderBy('generic_name', 'ASC')
            ->findAll($perPage, $offset);

        return ['rows' => $rows, 'total' => $count];
    }

    /**
     * Paged "with stock" listing — returns rows + total count.
     */
    public function getWithStockPaged(int $perPage, int $offset): array
    {
        $db = \Config\Database::connect();

        $total = (int) $db->table('medicines m')
            ->where('m.is_active', true)
            ->countAllResults();

        $rows = $db->table('medicines m')
            ->select('m.*, COALESCE(SUM(mb.quantity_remaining), 0) as total_stock')
            ->join('medicine_batches mb', "mb.medicine_id = m.id AND mb.status = 'active'", 'left')
            ->where('m.is_active', true)
            ->groupBy('m.id')
            ->orderBy('m.generic_name', 'ASC')
            ->get($perPage, $offset)
            ->getResultArray();

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * Get medicines with their total available stock.
     */
    public function getWithStock(): array
    {
        $db = \Config\Database::connect();

        return $db->table('medicines m')
            ->select('m.*, COALESCE(SUM(mb.quantity_remaining), 0) as total_stock')
            ->join('medicine_batches mb', "mb.medicine_id = m.id AND mb.status = 'active'", 'left')
            ->where('m.is_active', true)
            ->groupBy('m.id')
            ->orderBy('m.generic_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get medicines with low stock (below reorder threshold).
     */
    public function getLowStock(): array
    {
        $db = \Config\Database::connect();

        return $db->table('medicines m')
            ->select('m.*, COALESCE(SUM(mb.quantity_remaining), 0) as total_stock')
            ->join('medicine_batches mb', "mb.medicine_id = m.id AND mb.status = 'active'", 'left')
            ->where('m.is_active', true)
            ->groupBy('m.id')
            ->having('total_stock <= m.reorder_threshold')
            ->orderBy('total_stock', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get a single medicine with its active batches and total stock.
     */
    public function getWithBatches(int $id): ?array
    {
        $medicine = $this->find($id);

        if ($medicine === null) {
            return null;
        }

        $batchModel = new MedicineBatchModel();
        $medicine['batches']     = $batchModel->getActiveBatches($id);
        $medicine['total_stock'] = array_sum(array_column($medicine['batches'], 'quantity_remaining'));

        return $medicine;
    }
}
