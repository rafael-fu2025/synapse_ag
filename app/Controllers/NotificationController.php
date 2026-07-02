<?php

namespace App\Controllers;

class NotificationController extends BaseController
{
    public function unread()
    {
        $userId = session()->get('user_id');
        if (!$userId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $db = \Config\Database::connect();
        $notifications = $db->table('notifications')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->get()->getResultArray();

        $count = $db->table('notifications')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->countAllResults();

        return $this->response->setJSON([
            'status' => 'success',
            'count' => $count,
            'notifications' => $notifications
        ]);
    }

    public function markRead($id)
    {
        $userId = session()->get('user_id');
        if (!$userId) {
            return $this->response->setStatusCode(401)
                ->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        // Validate $id: must be the literal 'all' or a positive integer.
        // The route uses (:any) which is permissive — without this check a
        // crafted value like "' OR 1=1 --" could reach the query builder.
        if ($id !== 'all') {
            $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) {
                return $this->response->setStatusCode(400)
                    ->setJSON(['status' => 'error', 'message' => 'Invalid notification id']);
            }
        }

        $db = \Config\Database::connect();

        if ($id === 'all') {
            $db->table('notifications')
                ->where('user_id', $userId)
                ->where('is_read', 0)
                ->update(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
        } else {
            $db->table('notifications')
                ->where('id', $id)
                ->where('user_id', $userId)
                ->update(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
        }

        // Return the rotated CSRF hash so the client can refresh its meta
        // tag. CI4 rotates the token on every successful POST, so without
        // this echo the very next AJAX POST would 403.
        return $this->response->setJSON([
            'status'     => 'success',
            'csrf_hash'  => csrf_hash(),
        ]);
    }
}
