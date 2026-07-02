<?php

namespace App\Controllers\Clinic;

use App\Controllers\BaseController;
use App\Models\ConsultationModel;
use App\Models\ConsultationVitalsModel;
use App\Models\StudentModel;
use App\Models\AuditLogModel;

class ConsultationController extends BaseController
{
    protected ConsultationModel $consultModel;

    public function __construct()
    {
        $this->consultModel = new ConsultationModel();
    }

    /**
     * Today's consultation queue.
     */
    public function index()
    {
        $queue = $this->consultModel->getTodayQueue();
        $stats = $this->consultModel->getTodayStats();

        return view('clinic/consultations/index', [
            'title'   => 'Consultations — SYNAPSE',
            'heading' => 'Today\'s Consultations',
            'queue'   => $queue,
            'stats'   => $stats,
        ]);
    }

    /**
     * New consultation form.
     */
    public function create(int $studentId)
    {
        $studentModel = new StudentModel();
        $student = $studentModel->getWithProfile($studentId);

        if ($student === null) {
            return redirect()->to('/clinic/consultations')->with('error', 'Student not found.');
        }

        return view('clinic/consultations/create', [
            'title'   => 'New Consultation — SYNAPSE',
            'heading' => 'New Consultation',
            'student' => $student,
        ]);
    }

    /**
     * Store new consultation.
     */
    public function store()
    {
        $rules = [
            'student_id'      => 'required|is_natural_no_zero',
            'chief_complaint' => 'required|min_length[3]',
            'check_in_method' => 'required|in_list[qr,rfid,manual]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $studentId = (int) $this->request->getPost('student_id');
        $chiefComplaint = $this->request->getPost('chief_complaint');

        // Fetch allergies to check for severe triggers
        $allergyModel = new \App\Models\AllergyModel();
        $allergies = $allergyModel->where('student_id', $studentId)->findAll();

        // Run Triage AI Analysis
        $triageAssistant = new \App\Libraries\TriageAssistant();
        $triageResult = $triageAssistant->analyze($chiefComplaint, null, $allergies);
        
        $predictedPriority = $triageResult['predicted_priority'];
        $confidence = $triageResult['confidence_score'];
        $features = $triageResult['features_used'];

        // If staff provided a priority override, we use it, otherwise use AI prediction
        $staffPriority = $this->request->getPost('triage_priority');
        $finalPriority = $staffPriority ?: $predictedPriority;

        $consultId = $this->consultModel->insert([
            'student_id'        => $studentId,
            'attending_user_id' => session()->get('user_id'),
            'chief_complaint'   => $chiefComplaint,
            'check_in_method'   => $this->request->getPost('check_in_method'),
            'triage_priority'   => $finalPriority,
            'consultation_date' => date('Y-m-d H:i:s'),
            'status'            => 'in_progress',
        ]);

        // Save AI Prediction details
        $predictionModel = new \App\Models\AiTriagePredictionModel();
        $predictionModel->insert([
            'consultation_id'    => $consultId,
            'student_id'         => $studentId,
            'input_text'         => $chiefComplaint,
            'predicted_priority' => $predictedPriority,
            'confidence_score'   => $confidence,
            'model_version'      => $triageResult['model_version'],
            'features_used'      => json_encode($features),
            'staff_decision'     => $staffPriority ? ($staffPriority === $predictedPriority ? 'accepted' : 'overridden') : 'accepted',
            'staff_priority'     => $staffPriority ?: $predictedPriority,
            'decided_by'         => session()->get('user_id'),
            'decided_at'         => date('Y-m-d H:i:s'),
        ]);

        $auditModel = new AuditLogModel();
        $auditModel->logAction(session()->get('user_id'), 'create', 'clinic', 'consultations', $consultId);

        return redirect()->to("/clinic/consultations/{$consultId}")
            ->with('success', 'Consultation started with AI Triage priority: ' . strtoupper($finalPriority) . '. Record vitals next.');
    }

    /**
     * AJAX endpoint to run triage analysis.
     */
    public function ajaxTriage()
    {
        $studentId = $this->request->getPost('student_id');
        $chiefComplaint = $this->request->getPost('chief_complaint');

        $vitals = null;
        if ($this->request->getPost('temperature') !== null || $this->request->getPost('heart_rate') !== null) {
            $vitals = [
                'temperature'  => $this->request->getPost('temperature') ?: null,
                'heart_rate'   => $this->request->getPost('heart_rate') ?: null,
                'systolic_bp'  => $this->request->getPost('blood_pressure_sys') ?: null,
                'diastolic_bp' => $this->request->getPost('blood_pressure_dia') ?: null,
            ];
        }

        $allergies = [];
        if ($studentId) {
            $allergyModel = new \App\Models\AllergyModel();
            $allergies = $allergyModel->where('student_id', $studentId)->findAll();
        }

        $triageAssistant = new \App\Libraries\TriageAssistant();
        $triageResult = $triageAssistant->analyze($chiefComplaint ?: '', $vitals, $allergies);

        return $this->response->setJSON($triageResult);
    }

    /**
     * View consultation details.
     */
    public function show(int $id)
    {
        $consult = $this->consultModel->getFullConsultation($id);

        if ($consult === null) {
            return redirect()->to('/clinic/consultations')->with('error', 'Consultation not found.');
        }

        $predictionModel = new \App\Models\AiTriagePredictionModel();
        $aiPrediction = $predictionModel->where('consultation_id', $id)->first();

        return view('clinic/consultations/show', [
            'title'        => "Consultation #{$id} — SYNAPSE",
            'heading'      => "Consultation #{$id}",
            'consult'      => $consult,
            'aiPrediction' => $aiPrediction,
        ]);
    }

    /**
     * Record vitals form.
     */
    public function recordVitals(int $id)
    {
        $consult = $this->consultModel->find($id);
        if ($consult === null) {
            return redirect()->to('/clinic/consultations')->with('error', 'Consultation not found.');
        }

        return view('clinic/consultations/vitals_form', [
            'title'   => "Record Vitals — SYNAPSE",
            'heading' => 'Record Vital Signs',
            'consult' => $consult,
        ]);
    }

    /**
     * Store vitals.
     */
    public function storeVitals(int $id)
    {
        $vitalsModel = new ConsultationVitalsModel();

        // Check if vitals already exist
        $existing = $vitalsModel->getByConsultation($id);
        if ($existing) {
            return redirect()->to("/clinic/consultations/{$id}")
                ->with('warning', 'Vitals already recorded for this consultation.');
        }

        $vitalsModel->insert([
            'consultation_id'   => $id,
            'temperature'       => $this->request->getPost('temperature') ?: null,
            'blood_pressure_sys'=> $this->request->getPost('blood_pressure_sys') ?: null,
            'blood_pressure_dia'=> $this->request->getPost('blood_pressure_dia') ?: null,
            'heart_rate'        => $this->request->getPost('heart_rate') ?: null,
            'respiratory_rate'  => $this->request->getPost('respiratory_rate') ?: null,
            'weight_kg'         => $this->request->getPost('weight_kg') ?: null,
            'height_cm'         => $this->request->getPost('height_cm') ?: null,
        ]);

        // Recalculate Triage with Vitals
        $consult = $this->consultModel->find($id);
        if ($consult) {
            $studentId = (int) $consult['student_id'];
            $allergyModel = new \App\Models\AllergyModel();
            $allergies = $allergyModel->where('student_id', $studentId)->findAll();

            $vitalsData = [
                'temperature'  => $this->request->getPost('temperature'),
                'heart_rate'   => $this->request->getPost('heart_rate'),
                'systolic_bp'  => $this->request->getPost('blood_pressure_sys'),
                'diastolic_bp' => $this->request->getPost('blood_pressure_dia'),
            ];

            $triageAssistant = new \App\Libraries\TriageAssistant();
            $triageResult = $triageAssistant->analyze($consult['chief_complaint'], $vitalsData, $allergies);

            // Fetch existing prediction
            $predictionModel = new \App\Models\AiTriagePredictionModel();
            $existingPrediction = $predictionModel->where('consultation_id', $id)->first();

            if ($existingPrediction) {
                $predictionModel->update($existingPrediction['id'], [
                    'predicted_priority' => $triageResult['predicted_priority'],
                    'confidence_score'   => $triageResult['confidence_score'],
                    'features_used'      => json_encode($triageResult['features_used']),
                ]);

                // Update consultation if staff has not overridden
                if (($existingPrediction['staff_decision'] ?? 'accepted') !== 'overridden') {
                    $this->consultModel->update($id, [
                        'triage_priority' => $triageResult['predicted_priority']
                    ]);
                }
            }
        }

        return redirect()->to("/clinic/consultations/{$id}")
            ->with('success', 'Vital signs recorded and AI Triage re-evaluated.');
    }

    /**
     * Diagnosis form.
     */
    public function addDiagnosis(int $id)
    {
        $consult = $this->consultModel->find($id);
        if ($consult === null) {
            return redirect()->to('/clinic/consultations')->with('error', 'Consultation not found.');
        }

        return view('clinic/consultations/diagnosis_form', [
            'title'   => "Add Diagnosis — SYNAPSE",
            'heading' => 'Diagnosis & Notes',
            'consult' => $consult,
        ]);
    }

    /**
     * Store diagnosis.
     */
    public function storeDiagnosis(int $id)
    {
        $this->consultModel->update($id, [
            'diagnosis' => $this->request->getPost('diagnosis'),
            'notes'     => $this->request->getPost('notes'),
        ]);

        return redirect()->to("/clinic/consultations/{$id}")
            ->with('success', 'Diagnosis and notes saved.');
    }

    /**
     * Complete consultation.
     */
    public function complete(int $id)
    {
        $status = $this->request->getPost('status') ?? 'completed';

        /* Use the model's wrapper so completed_at is stamped and the
           remaining queue is re-packed. */
        $this->consultModel->finishConsultation($id, $status);

        $auditModel = new AuditLogModel();
        $auditModel->logAction(
            session()->get('user_id'),
            'complete',
            'clinic',
            'consultations',
            $id,
            null,
            ['status' => $status]
        );

        $message = $status === 'follow_up'
            ? 'Consultation marked for follow-up.'
            : 'Consultation completed.';

        return redirect()->to('/clinic/consultations')->with('success', $message);
    }

    /**
     * Queue management dashboard — same data as /clinic/consultations
     * but the layout makes room for the "Call Next" controls.
     */
    public function queue()
    {
        $queue = $this->consultModel->getTodayActiveQueue();
        $stats = $this->consultModel->getTodayStats();
        $nowServing = $this->consultModel->findCurrentlyServing();

        return view('clinic/consultations/queue', [
            'title'      => 'Queue — SYNAPSE',
            'heading'    => 'Clinic Queue',
            'queue'      => $queue,
            'stats'      => $stats,
            'nowServing' => $nowServing,
        ]);
    }

    /**
     * Staff pressed "Call Next" — promote the highest-priority waiting
     * patient to `called`. Returns JSON for the AJAX-driven queue UI.
     */
    public function callNext()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(405);
        }

        $userId = (int) session()->get('user_id');
        $row    = $this->consultModel->callNext($userId);

        if (! $row) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No patients waiting.',
            ]);
        }

        $auditModel = new AuditLogModel();
        $auditModel->logAction($userId, 'call', 'clinic', 'consultations', $row['id']);

        return $this->response->setJSON([
            'success' => true,
            'patient' => [
                'id'         => (int) $row['id'],
                'name'       => trim(($row['student_first'] ?? '') . ' ' . ($row['student_last'] ?? '')),
                'number'     => $row['student_number'],
                'priority'   => $row['triage_priority'] ?? 'medium',
                'queue_no'   => (int) $row['queue_position'],
                'called_at'  => $row['called_at'],
                'chief'      => $row['chief_complaint'],
            ],
        ]);
    }

    /**
     * Staff pressed "Start" on a called patient — they're in the room.
     */
    public function start(int $id)
    {
        $userId = (int) session()->get('user_id');
        $row    = $this->consultModel->startSession($id);
        if (! $row) {
            return redirect()->back()->with('error', 'Consultation not found.');
        }

        $auditModel = new AuditLogModel();
        $auditModel->logAction($userId, 'start', 'clinic', 'consultations', $id);

        return redirect()->to('/clinic/consultations/queue')->with('success', 'Consultation started.');
    }

    /**
     * Staff pressed "Skip" — patient didn't show. Closes the row and
     * re-packs the queue so the remaining positions stay 1..N.
     */
    public function skip(int $id)
    {
        $userId = (int) session()->get('user_id');
        $row    = $this->consultModel->skip($id, $userId);
        if (! $row) {
            return redirect()->back()->with('error', 'Consultation not found.');
        }

        $auditModel = new AuditLogModel();
        $auditModel->logAction($userId, 'skip', 'clinic', 'consultations', $id);

        return redirect()->to('/clinic/consultations/queue')->with('success', 'Patient skipped. Queue repacked.');
    }

    /**
     * Patient-facing "Now Serving" screen — mounted on a TV in the
     * waiting area. Auto-refreshes every 5s via meta-refresh so the
     * clinic doesn't have to maintain a websocket.
     */
    public function display()
    {
        $queue      = $this->consultModel->getTodayQueueForDisplay();
        $nowServing = $this->consultModel->findCurrentlyServing();

        return view('clinic/consultations/display', [
            'title'      => 'Now Serving — SYNAPSE',
            'heading'    => 'Now Serving',
            'queue'      => $queue,
            'nowServing' => $nowServing,
        ]);
    }

    /**
     * JSON state endpoint — used by the patient display (auto-refresh)
     * and by the clinic queue UI to live-refresh without a page reload.
     * Cached for 1s to keep concurrent refreshes cheap.
     */
    public function state()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(405);
        }
        $queue      = $this->consultModel->getTodayQueueForDisplay();
        $nowServing = $this->consultModel->findCurrentlyServing();
        $stats      = $this->consultModel->getTodayStats();

        return $this->response->setJSON([
            'now_serving' => $nowServing ? [
                'id'         => (int) $nowServing['id'],
                'name'       => trim(($nowServing['student_first'] ?? '') . ' ' . ($nowServing['student_last'] ?? '')),
                'number'     => $nowServing['student_number'],
                'priority'   => $nowServing['triage_priority'] ?? 'medium',
                'queue_no'   => (int) ($nowServing['queue_position'] ?? 0),
                'started_at' => $nowServing['started_at'] ?? null,
            ] : null,
            'called'      => array_values(array_filter($queue, fn($r) => $r['status'] === 'called')),
            'queue'       => array_values(array_filter($queue, fn($r) => $r['status'] === 'in_progress')),
            'stats'       => $stats,
            'updated_at'  => date('c'),
        ]);
    }

    /**
     * Student consultation history.
     */
    public function history(int $studentId)
    {
        $studentModel = new StudentModel();
        $student = $studentModel->getWithProfile($studentId);

        if ($student === null) {
            return redirect()->to('/clinic/students')->with('error', 'Student not found.');
        }

        $consults = $this->consultModel->getByStudent($studentId, 50);

        return view('clinic/consultations/index', [
            'title'   => "History: {$student['full_name']} — SYNAPSE",
            'heading' => "Consultation History: {$student['full_name']}",
            'queue'   => $consults,
            'stats'   => null,
        ]);
    }
}
