<?php

namespace App\Models;

use CodeIgniter\Model;

class RolePermissionModel extends Model
{
    protected $table            = 'role_permissions';
    protected $primaryKey       = 'role_id'; // Part of composite PK
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'role_id',
        'permission_id',
    ];

    protected $useTimestamps = false;

    /**
     * Get all permissions for a given role.
     */
    public function getRolePermissions(int $roleId): array
    {
        return $this->select('permissions.id, permissions.name, permissions.module, permissions.description')
            ->join('permissions', 'permissions.id = role_permissions.permission_id')
            ->where('role_permissions.role_id', $roleId)
            ->findAll();
    }

    /**
     * Check if a user has a specific permission (across all their roles).
     */
    public function userCan(int $userId, string $permissionName): bool
    {
        $db = \Config\Database::connect();

        $result = $db->table('role_permissions rp')
            ->join('user_roles ur', 'ur.role_id = rp.role_id')
            ->join('permissions p', 'p.id = rp.permission_id')
            ->where('ur.user_id', $userId)
            ->where('p.name', $permissionName)
            ->countAllResults();

        return $result > 0;
    }

    /**
     * Get all permission names for a user (across all their roles).
     *
     * @return list<string>
     */
    public function getUserPermissions(int $userId): array
    {
        $db = \Config\Database::connect();

        $results = $db->table('role_permissions rp')
            ->select('DISTINCT(p.name) as name')
            ->join('user_roles ur', 'ur.role_id = rp.role_id')
            ->join('permissions p', 'p.id = rp.permission_id')
            ->where('ur.user_id', $userId)
            ->get()
            ->getResultArray();

        return array_column($results, 'name');
    }

    /**
     * Assign a permission to a role.
     */
    public function assignPermission(int $roleId, int $permissionId): bool
    {
        $existing = $this->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->first();

        if ($existing !== null) {
            return true;
        }

        return $this->insert([
            'role_id'       => $roleId,
            'permission_id' => $permissionId,
        ]);
    }

    /**
     * Revoke a permission from a role.
     */
    public function revokePermission(int $roleId, int $permissionId): bool
    {
        return $this->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->delete();
    }

    /**
     * Get all permission IDs assigned to a role.
     *
     * @return list<int>
     */
    public function getRolePermissionIds(int $roleId): array
    {
        $rows = $this->where('role_id', $roleId)->findAll();
        return array_map(static fn(array $r) => (int) $r['permission_id'], $rows);
    }
}
