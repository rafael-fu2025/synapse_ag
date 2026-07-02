<?php

namespace App\Controllers\Clinic;

use App\Controllers\BaseController;
use App\Models\TreatmentModel;
use App\Models\MedicineModel;
use App\Models\MedicineBatchModel;
use App\Models\ConsultationModel;

class TreatmentController extends BaseController
{
    protected TreatmentModel $treatmentModel;

    public function __construct()
    {
        $this->treatmentModel = new TreatmentModel();
    }

    /**
     * Add treatment form.
     */
    public function create(int $consultationId)
    {
        $consultModel = new ConsultationModel();
        $consult = $consultModel->find($consultationId);

        if ($consult === null) {
            return redirect()->to('/clinic/consultations')->with('error', 'Consultation not found.');
        }

        $medicineModel = new MedicineModel();
        $medicines = $medicineModel->getWithStock();

        return view('clinic/treatments/create', [
            'title'      => 'Add Treatment — SYNAPSE',
            'heading'    => 'Add Treatment',
            'consult'    => $consult,
            'medicines'  => $medicines,
        ]);
    }

    /**
     * Store treatment.
     */
    public function store()
    {
        $rules = [
            'consultation_id' => 'required|is_natural_no_zero',
            'treatment_type'  => 'required|in_list[medication,first_aid,procedure,referral,other]',
            'description'     => 'required|min_length[3]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $consultId    = (int) $this->request->getPost('consultation_id');
        $type         = $this->request->getPost('treatment_type');
        $description  = $this->request->getPost('description');

        if ($type === 'medication') {
            // FEFO dispensing
            $medicineId = (int) $this->request->getPost('medicine_id');
            $quantity   = (int) $this->request->getPost('quantity');

            if ($quantity < 1) {
                return redirect()->back()->withInput()->with('error', 'Quantity must be at least 1.');
            }

            // Get FEFO batch
            $batchModel = new MedicineBatchModel();
            $batch = $batchModel->getFEFOBatch($medicineId);

            if ($batch === null) {
                return redirect()->back()->withInput()
                    ->with('error', 'No available stock for this medicine.');
            }

            if ($batch['quantity_remaining'] < $quantity) {
                return redirect()->back()->withInput()
                    ->with('error', "Insufficient stock. Available: {$batch['quantity_remaining']}.");
            }

            $result = $this->treatmentModel->dispense([
                'consultation_id' => $consultId,
                'description'     => $description,
                'administered_by' => session()->get('user_id'),
            ], (int) $batch['id'], $quantity);

            if ($result === false) {
                return redirect()->back()->withInput()
                    ->with('error', 'Failed to dispense medication. Please try again.');
            }
        } else {
            // Non-medication treatment
            $this->treatmentModel->insert([
                'consultation_id' => $consultId,
                'treatment_type'  => $type,
                'description'     => $description,
                'administered_by' => session()->get('user_id'),
            ]);
        }

        return redirect()->to("/clinic/consultations/{$consultId}")
            ->with('success', 'Treatment added successfully.');
    }

    /**
     * AJAX: Get batches for a medicine (for dynamic form).
     */
    public function getBatches(int $medicineId)
    {
        $batchModel = new MedicineBatchModel();
        $batches = $batchModel->getActiveBatches($medicineId);

        return $this->response->setJSON(['batches' => $batches]);
    }
}
