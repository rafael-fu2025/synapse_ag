<?php

namespace App\Models;

use CodeIgniter\Model;

class OfflineCheckinBufferModel extends Model
{
    protected $table            = 'offline_checkin_buffer';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'student_identifier', 'scan_method', 'station_id',
        'scanned_at', 'sync_status', 'synced_at', 'error_message',
    ];

    protected $useTimestamps = false;

    /**
     * Save offline scan.
     */
    public function saveScan(string $identifier, string $method, ?string $stationId, string $scannedAt): int|false
    {
        return $this->insert([
            'student_identifier' => $identifier,
            'scan_method'        => $method,
            'station_id'         => $stationId,
            'scanned_at'         => $scannedAt,
            'sync_status'        => 'pending',
        ]);
    }

    /**
     * Get pending scans.
     */
    public function getPending(): array
    {
        return $this->where('sync_status', 'pending')
            ->orderBy('scanned_at', 'ASC')
            ->findAll();
    }

    /**
     * Mark scan as synced.
     */
    public function markSynced(int $id): bool
    {
        return $this->update($id, [
            'sync_status' => 'synced',
            'synced_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Mark scan as failed.
     */
    public function markFailed(int $id, string $error): bool
    {
        return $this->update($id, [
            'sync_status'   => 'failed',
            'error_message' => $error,
        ]);
    }

    /**
     * Mark scan as duplicate.
     */
    public function markDuplicate(int $id): bool
    {
        return $this->update($id, [
            'sync_status' => 'duplicate',
        ]);
    }
}
