<?php

namespace App\Controllers\Pasimeo;

use App\Controllers\BaseController;
use App\Models\OutreachProgramModel;
use App\Models\OutreachActivityModel;
use App\Models\AuditLogModel;

class ProgramController extends BaseController
{
    protected OutreachProgramModel $programModel;

    public function __construct()
    {
        $this->programModel = new OutreachProgramModel();
    }

    /**
     * Dashboard — programs list with stats.
     */
    public function index()
    {
        $programs = $this->programModel->getAllWithStats();
        $stats = $this->programModel->getDashboardStats();

        $activityModel = new OutreachActivityModel();
        $upcoming = $activityModel->getUpcoming(5);

        return view('pasimeo/programs/index', [
            'title'    => 'PASIMEO — SYNAPSE',
            'heading'  => 'Outreach Programs',
            'programs' => $programs,
            'stats'    => $stats,
            'upcoming' => $upcoming,
        ]);
    }

    /**
     * Program detail with activities.
     */
    public function show(int $id)
    {
        $program = $this->programModel->getWithDetails($id);
        if ($program === null) {
            return redirect()->to('/pasimeo')->with('error', 'Program not found.');
        }

        return view('pasimeo/programs/show', [
            'title'   => "{$program['name']} — SYNAPSE",
            'heading' => $program['name'],
            'program' => $program,
        ]);
    }

    /**
     * Create form.
     */
    public function create()
    {
        return view('pasimeo/programs/form', [
            'title'   => 'New Program — SYNAPSE',
            'heading' => 'Create Outreach Program',
            'program' => null,
        ]);
    }

    /**
     * Store new program.
     */
    public function store()
    {
        $rules = [
            'name'       => 'required|min_length[3]',
            'start_date' => 'permit_empty|valid_date',
            'end_date'   => 'permit_empty|valid_date',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->programModel->insert([
            'name'           => $this->request->getPost('name'),
            'description'    => $this->request->getPost('description'),
            'coordinator_id' => session()->get('user_id'),
            'start_date'     => $this->request->getPost('start_date') ?: null,
            'end_date'       => $this->request->getPost('end_date') ?: null,
            'status'         => 'planning',
        ]);

        $id = $this->programModel->getInsertID();

        $auditModel = new AuditLogModel();
        $auditModel->logAction(session()->get('user_id'), 'create', 'pasimeo', 'outreach_programs', $id);

        return redirect()->to("/pasimeo/programs/{$id}")->with('success', 'Program created.');
    }

    /**
     * Edit form.
     */
    public function edit(int $id)
    {
        $program = $this->programModel->find($id);
        if ($program === null) {
            return redirect()->to('/pasimeo')->with('error', 'Program not found.');
        }

        return view('pasimeo/programs/form', [
            'title'   => "Edit {$program['name']} — SYNAPSE",
            'heading' => "Edit Program",
            'program' => $program,
        ]);
    }

    /**
     * Update program.
     */
    public function update(int $id)
    {
        $this->programModel->update($id, [
            'name'        => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'start_date'  => $this->request->getPost('start_date') ?: null,
            'end_date'    => $this->request->getPost('end_date') ?: null,
            'status'      => $this->request->getPost('status') ?: 'planning',
        ]);

        return redirect()->to("/pasimeo/programs/{$id}")->with('success', 'Program updated.');
    }
}
