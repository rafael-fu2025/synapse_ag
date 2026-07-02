<?php

namespace App\Controllers\Inventory;

use App\Controllers\BaseController;
use App\Models\MedicineModel;
use App\Models\MedicineBatchModel;
use App\Models\InventoryTransactionModel;
use App\Models\AuditLogModel;

class MedicineController extends BaseController
{
    protected MedicineModel $medicineModel;

    public function __construct()
    {
        $this->medicineModel = new MedicineModel();
    }

    /**
     * Medicine catalog.
     */
    public function index()
    {
        $search = $this->request->getGet('q');
        $page   = max(1, (int) ($this->request->getGet('page') ?? 1));
        /* Per-page selector: 10 / 25 / 50 / 100, default 20.
           Clamped to a sane range so a malicious query string can't
           request a million-row page. */
        $perPageRaw = (int) ($this->request->getGet('per_page') ?? 20);
        $perPage    = max(10, min(200, $perPageRaw ?: 20));

        if ($search) {
            $result = $this->medicineModel->searchPaged($search, $perPage, ($page - 1) * $perPage);
        } else {
            $result = $this->medicineModel->getWithStockPaged($perPage, ($page - 1) * $perPage);
        }

        $medicines = $result['rows'];
        $total     = $result['total'];
        $totalPages = max(1, (int) ceil($total / $perPage));
        /* Clamp page to actual range so an over-large ?page= doesn't
           return 0 rows when the user has an old bookmark. */
        $page = max(1, min($page, $totalPages));

        // Compute AI inventory forecasts on-the-fly
        $forecaster = new \App\Libraries\InventoryForecaster();
        $forecastModel = new \App\Models\AiInventoryForecastModel();
        $forecasts = [];

        foreach ($medicines as &$med) {
            $medId = (int) $med['id'];
            $currentStock = (int) ($med['current_stock'] ?? 0);
            $reorderThreshold = (int) ($med['reorder_threshold'] ?? 10);

            $calc = $forecaster->calculateForecast($medId, $currentStock, $reorderThreshold);

            $existing = $forecastModel->where('medicine_id', $medId)->where('forecast_date', date('Y-m-d'))->first();
            if ($existing) {
                $forecastModel->update($existing['id'], $calc);
                $calc['id'] = $existing['id'];
            } else {
                $insertedId = $forecastModel->insert($calc);
                $calc['id'] = $insertedId;
            }

            $forecasts[$medId] = $calc;
        }

        return view('inventory/medicines/index', [
            'title'      => 'Medicine Inventory — SYNAPSE',
            'heading'    => 'Medicine Inventory',
            'medicines'  => $medicines,
            'search'     => $search,
            'forecasts'  => $forecasts,
            'page'       => $page,
            'perPage'    => $perPage,
            'total'      => $total,
            'totalPages' => $totalPages,
        ]);
    }

    /**
     * Medicine detail + batches.
     */
    public function show(int $id)
    {
        $medicine = $this->medicineModel->getWithBatches($id);

        if ($medicine === null) {
            return redirect()->to('/inventory')->with('error', 'Medicine not found.');
        }

        // Get single AI forecast for detail view
        $forecaster = new \App\Libraries\InventoryForecaster();
        $forecastModel = new \App\Models\AiInventoryForecastModel();
        
        $medWithStock = $this->medicineModel->getWithStock();
        $stockMap = array_column($medWithStock, 'current_stock', 'id');
        $currentStock = $stockMap[$id] ?? 0;

        $calc = $forecaster->calculateForecast($id, $currentStock, (int) $medicine['reorder_threshold']);
        
        $existing = $forecastModel->where('medicine_id', $id)->where('forecast_date', date('Y-m-d'))->first();
        if ($existing) {
            $forecastModel->update($existing['id'], $calc);
            $calc['id'] = $existing['id'];
        } else {
            $insertedId = $forecastModel->insert($calc);
            $calc['id'] = $insertedId;
        }

        return view('inventory/medicines/show', [
            'title'    => "{$medicine['generic_name']} — SYNAPSE",
            'heading'  => $medicine['generic_name'],
            'medicine' => $medicine,
            'forecast' => $calc,
        ]);
    }

    /**
     * Create medicine form.
     */
    public function create()
    {
        return view('inventory/medicines/form', [
            'title'    => 'Add Medicine — SYNAPSE',
            'heading'  => 'Add New Medicine',
            'medicine' => null,
            'mode'     => 'create',
        ]);
    }

    /**
     * Store new medicine.
     */
    public function store()
    {
        $rules = [
            'generic_name' => 'required|max_length[200]',
            'unit'         => 'required|max_length[50]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $id = $this->medicineModel->insert([
            'generic_name'     => $this->request->getPost('generic_name'),
            'brand_name'       => $this->request->getPost('brand_name'),
            'category'         => $this->request->getPost('category'),
            'dosage_form'      => $this->request->getPost('dosage_form'),
            'dosage_strength'  => $this->request->getPost('dosage_strength'),
            'unit'             => $this->request->getPost('unit'),
            'reorder_threshold'=> $this->request->getPost('reorder_threshold') ?: 10,
            'description'      => $this->request->getPost('description'),
        ]);

        $auditModel = new AuditLogModel();
        $auditModel->logAction(session()->get('user_id'), 'create', 'inventory', 'medicines', $id);

        return redirect()->to("/inventory/medicines/{$id}")->with('success', 'Medicine added to catalog.');
    }

    /**
     * Edit medicine form.
     */
    public function edit(int $id)
    {
        $medicine = $this->medicineModel->find($id);

        if ($medicine === null) {
            return redirect()->to('/inventory')->with('error', 'Medicine not found.');
        }

        return view('inventory/medicines/form', [
            'title'    => "Edit {$medicine['generic_name']} — SYNAPSE",
            'heading'  => 'Edit Medicine',
            'medicine' => $medicine,
            'mode'     => 'edit',
        ]);
    }

    /**
     * Update medicine.
     */
    public function update(int $id)
    {
        $rules = [
            'generic_name' => 'required|max_length[200]',
            'unit'         => 'required|max_length[50]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->medicineModel->update($id, [
            'generic_name'     => $this->request->getPost('generic_name'),
            'brand_name'       => $this->request->getPost('brand_name'),
            'category'         => $this->request->getPost('category'),
            'dosage_form'      => $this->request->getPost('dosage_form'),
            'dosage_strength'  => $this->request->getPost('dosage_strength'),
            'unit'             => $this->request->getPost('unit'),
            'reorder_threshold'=> $this->request->getPost('reorder_threshold') ?: 10,
            'description'      => $this->request->getPost('description'),
        ]);

        return redirect()->to("/inventory/medicines/{$id}")->with('success', 'Medicine updated.');
    }

    /**
     * Receive new batch form.
     */
    public function addBatch(int $id)
    {
        $medicine = $this->medicineModel->find($id);

        if ($medicine === null) {
            return redirect()->to('/inventory')->with('error', 'Medicine not found.');
        }

        return view('inventory/batches/form', [
            'title'    => "Receive Batch — SYNAPSE",
            'heading'  => "Receive Batch: {$medicine['generic_name']}",
            'medicine' => $medicine,
        ]);
    }

    /**
     * Store new batch.
     */
    public function storeBatch(int $id)
    {
        $rules = [
            'batch_number'      => 'required|max_length[100]',
            'quantity_received' => 'required|is_natural_no_zero',
            'expiration_date'   => 'required|valid_date',
            'received_date'     => 'required|valid_date',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $quantity = (int) $this->request->getPost('quantity_received');

        $batchModel = new MedicineBatchModel();
        $batchId = $batchModel->insert([
            'medicine_id'        => $id,
            'batch_number'       => $this->request->getPost('batch_number'),
            'quantity_received'  => $quantity,
            'quantity_remaining' => $quantity,
            'expiration_date'    => $this->request->getPost('expiration_date'),
            'received_date'      => $this->request->getPost('received_date'),
            'supplier'           => $this->request->getPost('supplier'),
            'notes'              => $this->request->getPost('notes'),
            'status'             => 'active',
        ]);

        // Log inventory transaction
        $invModel = new InventoryTransactionModel();
        $invModel->logReceipt($batchId, $quantity, session()->get('user_id'));

        $auditModel = new AuditLogModel();
        $auditModel->logAction(session()->get('user_id'), 'create', 'inventory', 'medicine_batches', $batchId);

        return redirect()->to("/inventory/medicines/{$id}")
            ->with('success', "Batch received: {$quantity} units.");
    }

    /**
     * Low stock report.
     */
    public function lowStock()
    {
        $medicines = $this->medicineModel->getLowStock();

        return view('inventory/reports/low_stock', [
            'title'     => 'Low Stock Report — SYNAPSE',
            'heading'   => 'Low Stock Medicines',
            'medicines' => $medicines,
        ]);
    }

    /**
     * Expiring batches report.
     */
    public function expiring()
    {
        $days    = (int) ($this->request->getGet('days') ?? 30);
        $batchModel = new MedicineBatchModel();
        $batches = $batchModel->getExpiringBatches($days);

        return view('inventory/reports/low_stock', [
            'title'     => 'Expiring Batches — SYNAPSE',
            'heading'   => "Batches Expiring Within {$days} Days",
            'medicines' => $batches,
        ]);
    }
}
