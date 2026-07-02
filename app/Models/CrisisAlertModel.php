<?php

namespace App\Models;

use CodeIgniter\Model;

class CrisisAlertModel extends Model
{
    protected $table            = 'crisis_alerts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'student_id', 'assessment_response_id', 'trigger_source',
        'severity', 'status', 'assigned_counsellor_id',
        'acknowledged_at', 'acknowledged_by',
        'resolution_notes', 'resolved_at',
        'escalated_to', 'escalated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get active (unresolved) crisis alerts.
     */
    public function getActive(): array
    {
        return $this->select('crisis_alerts.*, students.student_number, u_student.first_name as student_first, u_student.last_name as student_last, u_counsellor.first_name as counsellor_first, u_counsellor.last_name as counsellor_last')
            ->join('students', 'students.id = crisis_alerts.student_id')
            ->join('users as u_student', 'u_student.id = students.user_id')
            ->join('users as u_counsellor', 'u_counsellor.id = crisis_alerts.assigned_counsellor_id', 'left')
            ->whereNotIn('crisis_alerts.status', ['resolved'])
            ->orderBy('crisis_alerts.created_at', 'DESC')
            ->findAll();
    }

    /**
     * Get unacknowledged alerts (for dashboard urgency).
     */
    public function getUnacknowledged(): array
    {
        return $this->select('crisis_alerts.*, students.student_number, u_student.first_name as student_first, u_student.last_name as student_last')
            ->join('students', 'students.id = crisis_alerts.student_id')
            ->join('users as u_student', 'u_student.id = students.user_id')
            ->where('crisis_alerts.status', 'triggered')
            ->orderBy('crisis_alerts.created_at', 'ASC')
            ->findAll();
    }

    /**
     * Acknowledge an alert.
     */
    public function acknowledge(int $id, int $userId): bool
    {
        return $this->update($id, [
            'status'          => 'acknowledged',
            'acknowledged_at' => date('Y-m-d H:i:s'),
            'acknowledged_by' => $userId,
        ]);
    }

    /**
     * Resolve an alert with notes.
     */
    public function resolve(int $id, string $notes): bool
    {
        return $this->update($id, [
            'status'           => 'resolved',
            'resolution_notes' => $notes,
            'resolved_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Escalate an alert to head counsellor.
     */
    public function escalate(int $id, int $headCounsellorId): bool
    {
        return $this->update($id, [
            'status'       => 'escalated',
            'escalated_to' => $headCounsellorId,
            'escalated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Create a crisis alert from a screening response.
     */
    public function createFromScreening(int $studentId, int $responseId, string $source, string $severity = 'high', ?int $counsellorId = null): int|false
    {
        $this->insert([
            'student_id'              => $studentId,
            'assessment_response_id'  => $responseId,
            'trigger_source'          => $source,
            'severity'                => $severity,
            'status'                  => 'triggered',
            'assigned_counsellor_id'  => $counsellorId,
        ]);

        $alertId = $this->getInsertID();

        // Notify counsellors
        $notifModel = new NotificationModel();
        $notifModel->createNotification(
            $counsellorId,
            'crisis_alert',
            '🚨 Crisis Alert Triggered',
            "A crisis alert has been triggered (source: {$source}). Immediate attention required. Must acknowledge within 30 minutes.",
            'counselling',
            'crisis_alerts',
            $alertId
        );

        return $alertId ?: false;
    }

    /**
     * Get dashboard stats.
     */
    public function getStats(): array
    {
        $triggered    = $this->where('status', 'triggered')->countAllResults(false);
        $acknowledged = $this->where('status', 'acknowledged')->countAllResults(false);
        $inProgress   = $this->where('status', 'in_progress')->countAllResults(false);
        $escalated    = $this->where('status', 'escalated')->countAllResults(false);

        return compact('triggered', 'acknowledged', 'inProgress', 'escalated');
    }
}
