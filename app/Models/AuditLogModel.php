<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table            = 'audit_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'user_id',
        'action',
        'module',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'hash',
        'previous_hash',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    // Callbacks
    protected $beforeInsert = ['generateHashChain'];

    /**
     * Generate SHA-256 hash and link to previous hash (hash-chain integrity).
     */
    protected function generateHashChain(array $data): array
    {
        // Get the last audit log's hash
        $lastLog = $this->orderBy('id', 'DESC')->first();
        $previousHash = $lastLog ? $lastLog['hash'] : 'GENESIS';

        // Set created_at explicitly so it is saved and can be hashed/recalculated
        if (empty($data['data']['created_at'])) {
            $data['data']['created_at'] = date('Y-m-d H:i:s');
        }

        // Build content string for hashing
        $content = json_encode([
            'user_id'     => isset($data['data']['user_id']) ? (int)$data['data']['user_id'] : null,
            'action'      => $data['data']['action'] ?? '',
            'module'      => $data['data']['module'] ?? '',
            'entity_type' => $data['data']['entity_type'] ?? '',
            'entity_id'   => isset($data['data']['entity_id']) ? (int)$data['data']['entity_id'] : null,
            'old_values'  => $data['data']['old_values'] ?? null,
            'new_values'  => $data['data']['new_values'] ?? null,
            'previous'    => $previousHash,
            'created_at'  => $data['data']['created_at'],
        ]);

        $data['data']['hash']          = hash('sha256', $content);
        $data['data']['previous_hash'] = $previousHash;

        return $data;
    }

    /**
     * Log an action to the audit trail.
     * This is the primary method other code should call.
     */
    public function logAction(
        ?int $userId,
        string $action,
        string $module,
        string $entityType = '',
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): bool {
        $request = service('request');
        $userAgent = method_exists($request, 'getUserAgent') ? $request->getUserAgent()->getAgentString() : 'CLI Command';

        return (bool) $this->insert([
            'user_id'     => $userId,
            'action'      => $action,
            'module'      => $module,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'old_values'  => $oldValues ? json_encode($oldValues) : null,
            'new_values'  => $newValues ? json_encode($newValues) : null,
            'ip_address'  => $request->getIPAddress(),
            'user_agent'  => $userAgent,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Verify the integrity of the hash chain.
     * Returns true if the chain is intact, false if tampered.
     */
    public function verifyChainIntegrity(int $limit = 100): array
    {
        $logs = $this->orderBy('id', 'ASC')->findAll($limit);

        $errors         = [];
        $expectedPrevHash = 'GENESIS';

        foreach ($logs as $log) {
            // 1. Check if previous hash matches what we expect
            if ($log['previous_hash'] !== $expectedPrevHash) {
                $errors[] = [
                    'log_id'   => $log['id'],
                    'expected' => $expectedPrevHash,
                    'actual'   => $log['previous_hash'],
                    'message'  => "Hash chain broken at log #{$log['id']}: previous_hash link invalid.",
                ];
            }

            // 2. Recalculate hash of the content to check if cell values were modified
            $content = json_encode([
                'user_id'     => isset($log['user_id']) ? (int)$log['user_id'] : null,
                'action'      => $log['action'] ?? '',
                'module'      => $log['module'] ?? '',
                'entity_type' => $log['entity_type'] ?? '',
                'entity_id'   => isset($log['entity_id']) ? (int)$log['entity_id'] : null,
                'old_values'  => $log['old_values'] ?? null,
                'new_values'  => $log['new_values'] ?? null,
                'previous'    => $log['previous_hash'],
                'created_at'  => $log['created_at'],
            ]);

            $recalculatedHash = hash('sha256', $content);
            if ($log['hash'] !== $recalculatedHash) {
                $errors[] = [
                    'log_id'   => $log['id'],
                    'expected' => $log['hash'],
                    'actual'   => $recalculatedHash,
                    'message'  => "Content tampered at log #{$log['id']}: calculated hash does not match stored hash.",
                ];
            }

            $expectedPrevHash = $log['hash'];
        }

        return [
            'intact'      => empty($errors),
            'checked'     => count($logs),
            'errors'      => $errors,
            'error_count' => count($errors),
        ];
    }
}
