<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table            = 'notifications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'user_id', 'type', 'title', 'message',
        'data', 'is_read', 'read_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    /**
     * Create a notification.
     * If user_id is null, it's a broadcast (resolves target users based on roles/context).
     */
    public function createNotification(
        ?int $userId,
        string $type,
        string $title,
        string $message,
        string $module = '',
        string $entityType = '',
        ?int $entityId = null
    ): bool {
        $db = \Config\Database::connect();
        
        $extraData = [];
        if ($module !== '') $extraData['module'] = $module;
        if ($entityType !== '') $extraData['entity_type'] = $entityType;
        if ($entityId !== null) $extraData['entity_id'] = $entityId;
        
        $jsonAllowedData = !empty($extraData) ? json_encode($extraData) : null;

        if ($userId !== null) {
            return (bool) $this->insert([
                'user_id' => $userId,
                'type'    => $type,
                'title'   => $title,
                'message' => $message,
                'data'    => $jsonAllowedData,
            ]);
        }

        $recipients = [];

        if ($type === 'appointment_booked' && $entityType === 'counselling_appointments' && $entityId !== null) {
            $appt = $db->table('counselling_appointments')->where('id', $entityId)->get()->getRowArray();
            if ($appt) {
                $student = $db->table('students')->where('id', $appt['student_id'])->get()->getRowArray();
                if ($student) {
                    $recipients[] = (int) $student['user_id'];
                }
            }
        } elseif ($type === 'referral_accepted' && $entityType === 'referrals' && $entityId !== null) {
            $ref = $db->table('referrals')->where('id', $entityId)->get()->getRowArray();
            if ($ref) {
                $recipients[] = (int) $ref['referred_by'];
            }
        } elseif ($type === 'low_stock' || $module === 'inventory' || $module === 'clinic') {
            $roleUsers = $db->table('user_roles')
                ->join('roles', 'roles.id = user_roles.role_id')
                ->where('roles.name', 'clinic_staff')
                ->select('user_roles.user_id')
                ->get()->getResultArray();
            foreach ($roleUsers as $ru) {
                $recipients[] = (int) $ru['user_id'];
            }
        } elseif ($type === 'welfare_alert' || $type === 'screening_alert' || $type === 'referral' || $module === 'counselling') {
            $roleUsers = $db->table('user_roles')
                ->join('roles', 'roles.id = user_roles.role_id')
                ->where('roles.name', 'counsellor')
                ->select('user_roles.user_id')
                ->get()->getResultArray();
            foreach ($roleUsers as $ru) {
                $recipients[] = (int) $ru['user_id'];
            }
        }

        if (empty($recipients)) {
            $adminUsers = $db->table('user_roles')
                ->join('roles', 'roles.id = user_roles.role_id')
                ->where('roles.name', 'admin')
                ->select('user_roles.user_id')
                ->get()->getResultArray();
            foreach ($adminUsers as $au) {
                $recipients[] = (int) $au['user_id'];
            }
        }

        $recipients = array_unique($recipients);
        $success = true;
        foreach ($recipients as $rId) {
            $ok = (bool) $this->insert([
                'user_id' => $rId,
                'type'    => $type,
                'title'   => $title,
                'message' => $message,
                'data'    => $jsonAllowedData,
            ]);
            if (!$ok) $success = false;
        }

        return $success;
    }

    /**
     * Get unread notifications for a user.
     */
    public function getUnread(int $userId, int $limit = 20): array
    {
        return $this->where('user_id', $userId)
            ->where('is_read', false)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get unread count for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->where('user_id', $userId)
            ->where('is_read', false)
            ->countAllResults();
    }

    /**
     * Mark a notification as read.
     */
    public function markRead(int $id): bool
    {
        return $this->update($id, [
            'is_read' => true,
            'read_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllRead(int $userId): bool
    {
        return $this->where('user_id', $userId)
            ->where('is_read', false)
            ->set([
                'is_read' => true,
                'read_at' => date('Y-m-d H:i:s'),
            ])
            ->update();
    }
}
