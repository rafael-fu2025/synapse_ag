<?php

namespace App\Models;

use CodeIgniter\Model;

class PermissionModel extends Model
{
    protected $table            = 'permissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'name',
        'module',
        'description',
    ];

    protected $useTimestamps = false;

    /**
     * Find a permission by its name.
     */
    public function findByName(string $name): ?array
    {
        return $this->where('name', $name)->first();
    }

    /**
     * Get all permissions grouped by module.
     */
    public function getGroupedByModule(): array
    {
        $permissions = $this->orderBy('module')->orderBy('name')->findAll();
        $grouped     = [];

        foreach ($permissions as $perm) {
            $grouped[$perm['module']][] = $perm;
        }

        return $grouped;
    }
}
