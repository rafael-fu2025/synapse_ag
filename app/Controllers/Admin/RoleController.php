<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AuditLogModel;
use App\Models\PermissionModel;
use App\Models\RoleModel;
use App\Models\RolePermissionModel;
use App\Models\UserRoleModel;

/**
 * Admin Role Management
 *
 * Endpoints:
 *   GET  /admin/roles              → list roles + permission count + user count
 *   GET  /admin/roles/create       → create form
 *   POST /admin/roles/store        → create role
 *   GET  /admin/roles/(:num)       → role detail + permission matrix
 *   GET  /admin/roles/(:num)/edit  → edit form
 *   POST /admin/roles/update/(:num) → save edit
 *   POST /admin/roles/toggle-permission/(:num) → attach/detach permission
 */
class RoleController extends BaseController
{
    private RoleModel           $roles;
    private PermissionModel     $permissions;
    private RolePermissionModel $rolePerms;
    private UserRoleModel       $userRoles;
    private AuditLogModel       $audit;

    public function __construct()
    {
        $this->roles       = new RoleModel();
        $this->permissions = new PermissionModel();
        $this->rolePerms   = new RolePermissionModel();
        $this->userRoles   = new UserRoleModel();
        $this->audit       = new AuditLogModel();
    }

    public function index()
    {
        $db = \Config\Database::connect();

        $roles = $db->table('roles')->orderBy('name', 'ASC')->get()->getResultArray();

        // Permission count + user count per role
        foreach ($roles as &$r) {
            $r['permission_count'] = (int) $db->table('role_permissions')
                ->where('role_id', $r['id'])
                ->countAllResults();

            $r['user_count'] = (int) $db->table('user_roles')
                ->where('role_id', $r['id'])
                ->countAllResults();
        }
        unset($r);

        return view('admin/roles/index', [
            'title'   => 'Role Management — SYNAPSE',
            'heading' => 'Role Management',
            'roles'   => $roles,
        ]);
    }

    public function create()
    {
        return view('admin/roles/create', [
            'title'   => 'Create Role — SYNAPSE',
            'heading' => 'Create Role',
        ]);
    }

    public function store()
    {
        $rules = [
            'name'         => 'required|max_length[50]|is_unique[roles.name]|regex_match[/^[a-z_]+$/]',
            'display_name' => 'required|max_length[100]',
            'description'  => 'permit_empty|max_length[500]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'name'         => $this->request->getPost('name'),
            'display_name' => $this->request->getPost('display_name'),
            'description'  => $this->request->getPost('description') ?: null,
        ];

        $this->roles->insert($data);
        $roleId = (int) $this->roles->getInsertID();

        $this->audit->logAction(
            (int) session()->get('user_id'),
            'create',
            'admin',
            'roles',
            $roleId,
            null,
            ['name' => $data['name']]
        );

        return redirect()->to('/admin/roles/' . $roleId)
            ->with('success', 'Role created. Assign permissions below.');
    }

    public function show(int $id)
    {
        $role = $this->roles->find($id);
        if ($role === null) {
            return redirect()->to('/admin/roles')->with('error', 'Role not found.');
        }

        $assignedPermIds = $this->rolePerms->getRolePermissionIds($id);
        $allPerms        = $this->permissions->getGroupedByModule();

        // Users holding this role
        $db = \Config\Database::connect();
        $users = $db->table('user_roles ur')
            ->select('u.id, u.email, u.first_name, u.last_name, u.is_active')
            ->join('users u', 'u.id = ur.user_id')
            ->where('ur.role_id', $id)
            ->orderBy('u.email', 'ASC')
            ->get()->getResultArray();

        return view('admin/roles/show', [
            'title'           => 'Role Detail — SYNAPSE',
            'heading'         => 'Role Detail',
            'role'            => $role,
            'assignedPermIds' => $assignedPermIds,
            'allPerms'        => $allPerms,
            'users'           => $users,
        ]);
    }

    public function edit(int $id)
    {
        $role = $this->roles->find($id);
        if ($role === null) {
            return redirect()->to('/admin/roles')->with('error', 'Role not found.');
        }

        return view('admin/roles/edit', [
            'title'   => 'Edit Role — SYNAPSE',
            'heading' => 'Edit Role',
            'role'    => $role,
        ]);
    }

    public function update(int $id)
    {
        $role = $this->roles->find($id);
        if ($role === null) {
            return redirect()->to('/admin/roles')->with('error', 'Role not found.');
        }

        $rules = [
            'name'         => "required|max_length[50]|is_unique[roles.name,id,{$id}]|regex_match[/^[a-z_]+$/]",
            'display_name' => 'required|max_length[100]',
            'description'  => 'permit_empty|max_length[500]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $oldValues = [
            'name'         => $role['name'],
            'display_name' => $role['display_name'],
            'description'  => $role['description'],
        ];

        $data = [
            'name'         => $this->request->getPost('name'),
            'display_name' => $this->request->getPost('display_name'),
            'description'  => $this->request->getPost('description') ?: null,
        ];

        $this->roles->update($id, $data);

        $this->audit->logAction(
            (int) session()->get('user_id'),
            'update',
            'admin',
            'roles',
            $id,
            $oldValues,
            $data
        );

        return redirect()->to('/admin/roles/' . $id)
            ->with('success', 'Role updated.');
    }

    public function togglePermission(int $roleId)
    {
        $role = $this->roles->find($roleId);
        if ($role === null) {
            return redirect()->to('/admin/roles')->with('error', 'Role not found.');
        }

        $permId = (int) $this->request->getPost('permission_id');
        $perm   = $this->permissions->find($permId);
        if ($perm === null) {
            return redirect()->back()->with('error', 'Invalid permission.');
        }

        // Toggle: if assigned, revoke; otherwise assign
        $isAssigned = in_array($permId, $this->rolePerms->getRolePermissionIds($roleId), true);

        if ($isAssigned) {
            $this->rolePerms->revokePermission($roleId, $permId);
            $action = 'revoke_permission';
            $newVal = null;
        } else {
            $this->rolePerms->assignPermission($roleId, $permId);
            $action = 'assign_permission';
            $newVal = ['permission_id' => $permId, 'permission_name' => $perm['name']];
        }

        $this->audit->logAction(
            (int) session()->get('user_id'),
            $action,
            'admin',
            'role_permissions',
            $roleId,
            $isAssigned ? ['permission_id' => $permId, 'permission_name' => $perm['name']] : null,
            $newVal
        );

        return redirect()->to('/admin/roles/' . $roleId)
            ->with('success', 'Permission updated.');
    }
}