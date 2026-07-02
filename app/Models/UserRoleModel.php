<?php

namespace App\Models;

use CodeIgniter\Model;

class UserRoleModel extends Model
{
    protected $table            = 'user_roles';
    protected $primaryKey       = 'user_id'; // Part of composite PK
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'user_id',
        'role_id',
        'assigned_at',
    ];

    protected $useTimestamps = false;

    /**
     * Get all roles for a given user.
     *
     * @return list<array{id: int, name: string, display_name: string}>
     */
    public function getUserRoles(int $userId): array
    {
        return $this->select('roles.id, roles.name, roles.display_name')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->findAll();
    }

    /**
     * Check if a user has a specific role (by name).
     */
    public function hasRole(int $userId, string $roleName): bool
    {
        $result = $this->join('roles', 'roles.id = user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->where('roles.name', $roleName)
            ->countAllResults();

        return $result > 0;
    }

    /**
     * Check if a user has any of the given roles.
     *
     * @param list<string> $roleNames
     */
    public function hasAnyRole(int $userId, array $roleNames): bool
    {
        $result = $this->join('roles', 'roles.id = user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->whereIn('roles.name', $roleNames)
            ->countAllResults();

        return $result > 0;
    }

    /**
     * Assign a role to a user.
     */
    public function assignRole(int $userId, int $roleId): bool
    {
        // Check if already assigned
        $existing = $this->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->first();

        if ($existing !== null) {
            return true; // Already assigned
        }

        return $this->insert([
            'user_id'     => $userId,
            'role_id'     => $roleId,
            'assigned_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Revoke a role from a user.
     */
    public function revokeRole(int $userId, int $roleId): bool
    {
        return $this->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->delete();
    }

    /**
     * Get the primary role name for a user (first assigned role).
     */
    public function getPrimaryRole(int $userId): ?string
    {
        $role = $this->select('roles.name')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->orderBy('user_roles.assigned_at', 'ASC')
            ->first();

        return $role ? $role['name'] : null;
    }
}
