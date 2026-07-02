<?php

namespace App\Controllers\Pasimeo;

use App\Controllers\BaseController;
use App\Models\OutreachActivityModel;
use App\Models\OutreachProgramModel;
use App\Models\AuditLogModel;

class ActivityController extends BaseController
{
    protected OutreachActivityModel $activityModel;

    public function __construct()
    {
        $this->activityModel = new OutreachActivityModel();
    }

    /**
     * Activity detail with volunteers + attendance.
     */
    public function show(int $id)
    {
        $activity = $this->activityModel->getWithDetails($id);
        if ($activity === null) {
            return redirect()->to('/pasimeo')->with('error', 'Activity not found.');
        }

        return view('pasimeo/activities/show', [
            'title'    => "{$activity['title']} — SYNAPSE",
            'heading'  => $activity['title'],
            'activity' => $activity,
        ]);
    }

    /**
     * Create activity form.
     */
    public function create(int $programId)
    {
        $programModel = new OutreachProgramModel();
        $program = $programModel->find($programId);
        if ($program === null) {
            return redirect()->to('/pasimeo')->with('error', 'Program not found.');
        }

        return view('pasimeo/activities/form', [
            'title'    => 'New Activity — SYNAPSE',
            'heading'  => 'Create Activity',
            'program'  => $program,
            'activity' => null,
        ]);
    }

    /**
     * Store activity.
     */
    public function store()
    {
        $rules = [
            'program_id'    => 'required|is_natural_no_zero',
            'title'         => 'required|min_length[3]',
            'activity_date' => 'required|valid_date',
            'start_time'    => 'required',
            'end_time'      => 'required',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->activityModel->insert([
            'program_id'     => $this->request->getPost('program_id'),
            'title'          => $this->request->getPost('title'),
            'description'    => $this->request->getPost('description'),
            'location'       => $this->request->getPost('location'),
            'activity_date'  => $this->request->getPost('activity_date'),
            'start_time'     => $this->request->getPost('start_time'),
            'end_time'       => $this->request->getPost('end_time'),
            'max_volunteers' => $this->request->getPost('max_volunteers') ?: null,
            'status'         => 'upcoming',
        ]);

        $id = $this->activityModel->getInsertID();

        $auditModel = new AuditLogModel();
        $auditModel->logAction(session()->get('user_id'), 'create', 'pasimeo', 'outreach_activities', $id);

        return redirect()->to("/pasimeo/activities/{$id}")->with('success', 'Activity created.');
    }

    /**
     * Update activity status.
     */
    public function updateStatus(int $id)
    {
        $status = $this->request->getPost('status');

        $this->activityModel->update($id, ['status' => $status]);

        return redirect()->to("/pasimeo/activities/{$id}")
            ->with('success', "Activity marked as {$status}.");
    }
}
