<?php

namespace App\Controllers\Counselling;

use App\Controllers\BaseController;
use App\Models\CrisisAlertModel;
use App\Models\AuditLogModel;

class CrisisController extends BaseController
{
    protected CrisisAlertModel $crisisModel;

    public function __construct()
    {
        $this->crisisModel = new CrisisAlertModel();
    }

    /**
     * Crisis alerts dashboard.
     */
    public function index()
    {
        $alerts = $this->crisisModel->getActive();
        $stats  = $this->crisisModel->getStats();

        return view('counselling/crisis/index', [
            'title'  => 'Crisis Alerts — SYNAPSE',
            'heading'=> 'Crisis Alerts',
            'alerts' => $alerts,
            'stats'  => $stats,
        ]);
    }

    /**
     * Acknowledge a crisis alert.
     */
    public function acknowledge(int $id)
    {
        $this->crisisModel->acknowledge($id, session()->get('user_id'));

        $auditModel = new AuditLogModel();
        $auditModel->logAction(session()->get('user_id'), 'acknowledge', 'counselling', 'crisis_alerts', $id);

        return redirect()->to('/counselling/crisis')->with('success', 'Crisis alert acknowledged.');
    }

    /**
     * Resolve a crisis alert.
     */
    public function resolve(int $id)
    {
        $notes = $this->request->getPost('resolution_notes');

        if (empty($notes)) {
            return redirect()->back()->with('error', 'Resolution notes are required.');
        }

        $this->crisisModel->resolve($id, $notes);

        $auditModel = new AuditLogModel();
        $auditModel->logAction(session()->get('user_id'), 'resolve', 'counselling', 'crisis_alerts', $id);

        return redirect()->to('/counselling/crisis')->with('success', 'Crisis alert resolved.');
    }

    /**
     * Escalate a crisis alert.
     */
    public function escalate(int $id)
    {
        // For now, escalate to the current user (head counsellor)
        $this->crisisModel->escalate($id, session()->get('user_id'));

        return redirect()->to('/counselling/crisis')->with('warning', 'Crisis alert escalated.');
    }
}
