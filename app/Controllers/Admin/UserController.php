<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AuditLogModel;
use App\Models\RoleModel;
use App\Models\UserModel;
use App\Models\UserRoleModel;

/**
 * Admin User Management
 *
 * Endpoints:
 *   GET  /admin/users              → list with search + filter
 *   GET  /admin/users/create       → form
 *   POST /admin/users/store        → create user + assign roles
 *   GET  /admin/users/(:num)       → detail page (profile + roles)
 *   GET  /admin/users/(:num)/edit  → edit form
 *   POST /admin/users/update/(:num) → save edit
 *   POST /admin/users/toggle/(:num) → flip is_active
 *   POST /admin/users/reset-password/(:num) → admin-initiated reset
 *   POST /admin/users/assign-role/(:num) → attach a role
 *   POST /admin/users/revoke-role/(:num) → detach a role
 *
 * All actions write to audit_logs.
 */
class UserController extends BaseController
{
    private UserModel       $users;
    private UserRoleModel   $userRoles;
    private RoleModel       $roles;
    private AuditLogModel   $audit;

    public function __construct()
    {
        $this->users     = new UserModel();
        $this->userRoles = new UserRoleModel();
        $this->roles     = new RoleModel();
        $this->audit     = new AuditLogModel();
    }

    /**
     * Whitelist of columns the user-list page is allowed to sort by.
     * Maps the URL `sort` parameter to the real SQL column + direction.
     * Keeping the allowlist in code (not just trusting the query string)
     * prevents SQL injection via the ORDER BY clause.
     */
    private const SORTABLE = [
        'name'        => ['col' => 'u.last_name',    'default_dir' => 'ASC'],
        'email'       => ['col' => 'u.email',        'default_dir' => 'ASC'],
        'last_login'  => ['col' => 'u.last_login_at','default_dir' => 'DESC'],
        'created'     => ['col' => 'u.created_at',   'default_dir' => 'DESC'],
    ];

    public function index()
    {
        $db = \Config\Database::connect();

        $q        = trim((string) ($this->request->getGet('q') ?? ''));
        $roleName = trim((string) ($this->request->getGet('role') ?? ''));
        $status   = (string) ($this->request->getGet('status') ?? 'all');

        // Sort: validate against allowlist, accept 'asc'/'desc' only.
        $sortKey  = (string) ($this->request->getGet('sort') ?? 'created');
        $sortKey  = isset(self::SORTABLE[$sortKey]) ? $sortKey : 'created';
        $sortDir  = strtolower((string) ($this->request->getGet('dir') ?? self::SORTABLE[$sortKey]['default_dir']));
        $sortDir  = ($sortDir === 'asc') ? 'ASC' : 'DESC';
        $sortCol  = self::SORTABLE[$sortKey]['col'];

        $builder = $db->table('users u')->select('u.*');

        if ($q !== '') {
            // Escape LIKE wildcards (% and _) so a user typing `_` doesn't
            // match every record. See app/Helpers/like_escape_helper.php.
            $qEscaped = escape_like($q);
            $builder->groupStart()
                ->like('u.email',      $qEscaped, 'both', null, true)
                ->orLike('u.first_name',$qEscaped, 'both', null, true)
                ->orLike('u.last_name', $qEscaped, 'both', null, true)
                ->groupEnd();
        }

        if ($status === 'active') {
            $builder->where('u.is_active', true);
        } elseif ($status === 'inactive') {
            $builder->where('u.is_active', false);
        }

        if ($roleName !== '') {
            $builder->join('user_roles ur', 'ur.user_id = u.id')
                ->join('roles r', 'r.id = ur.role_id')
                ->where('r.name', $roleName);
        }

        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));
        /* Per-page selector: 10 / 25 / 50 / 100, default 20.
           Clamped to a sane range so a malicious query string can't
           request a million-row page. */
        $perPageRaw = (int) ($this->request->getGet('per_page') ?? 20);
        $perPage    = max(10, min(200, $perPageRaw ?: 20));
        $total   = $builder->countAllResults(false);

        /* Clamp page to the actual range so an over-large page param
           (e.g. from an old bookmark or a typo) doesn't return 0 rows
           with an empty table. We re-clamp below once we know $total. */
        $page = max(1, min($page, (int) max(1, ceil($total / $perPage))));

        // Secondary sort by id keeps the order stable when the primary
        // column has ties (e.g. many users with NULL last_login_at).
        $users = $builder
            ->orderBy($sortCol, $sortDir)
            ->orderBy('u.id', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()->getResultArray();

        // Resolve role names per user in one extra query to avoid N+1
        $userIds = array_column($users, 'id');
        $rolesByUser = [];
        if (! empty($userIds)) {
            $rows = $db->table('user_roles ur')
                ->select('ur.user_id, r.name, r.display_name')
                ->join('roles r', 'r.id = ur.role_id')
                ->whereIn('ur.user_id', $userIds)
                ->get()->getResultArray();
            foreach ($rows as $row) {
                $rolesByUser[$row['user_id']][] = $row;
            }
        }

        foreach ($users as &$u) {
            $u['roles'] = $rolesByUser[$u['id']] ?? [];
        }
        unset($u);

        $allRoles = $this->roles->orderBy('display_name', 'ASC')->findAll();

        // Register a synthetic pager so the pagination_links() helper can render.
        // Pager::store() signature is store($group, $page, $perPage, $total) —
        // do NOT pass ceil($total/$perPage) here, that would be treated as the
        // total record count and break "Showing X–Y of Z" + next/prev controls.
        $pager = service('pager');
        $pager->store('default', $page, $perPage, $total);

        return view('admin/users/index', [
            'title'      => 'User Management — SYNAPSE',
            'heading'    => 'User Management',
            'users'      => $users,
            'total'      => $total,
            'page'       => $page,
            'totalPages' => (int) ceil($total / $perPage),
            'perPage'    => $perPage,
            'q'          => $q,
            'roleName'   => $roleName,
            'status'     => $status,
            'sortKey'    => $sortKey,
            'sortDir'    => $sortDir,
            'allRoles'   => $allRoles,
            'pager'      => $pager,
        ]);
    }

    /**
     * Bulk activate / deactivate users from the list page.
     *
     * Accepts: ids[]=int, ids[]=int, action=activate|deactivate
     * Returns JSON for fetch() callers and redirects for no-JS callers.
     */
    public function bulkToggle()
    {
        $ids = (array) $this->request->getPost('ids');
        $ids = array_values(array_filter(array_map('intval', $ids), fn($id) => $id > 0));

        $action = (string) $this->request->getPost('action');
        if (! in_array($action, ['activate', 'deactivate'], true)) {
            return $this->bulkResponse(false, 'Invalid action.', 400);
        }
        if (empty($ids)) {
            return $this->bulkResponse(false, 'No users selected.', 400);
        }

        // Prevent admins from deactivating themselves via the bulk action.
        $currentUserId = (int) session()->get('user_id');
        if ($action === 'deactivate' && in_array($currentUserId, $ids, true)) {
            return $this->bulkResponse(
                false,
                'You cannot deactivate your own account. Remove yourself from the selection.',
                400
            );
        }

        $newStatus = ($action === 'activate');
        $db = \Config\Database::connect();
        $db->table('users')
            ->whereIn('id', $ids)
            ->update(['is_active' => $newStatus]);

        $affected = $db->affectedRows();

        // One audit-log entry per affected user so each row's history is
        // discoverable individually.
        foreach ($ids as $id) {
            $this->audit->logAction(
                $currentUserId,
                $action,
                'admin',
                'users',
                $id,
                null,
                ['bulk' => true, 'batch_size' => count($ids), 'new_status' => $newStatus]
            );
        }

        return $this->bulkResponse(
            true,
            sprintf(
                '%d user%s %s.',
                $affected,
                $affected === 1 ? '' : 's',
                $action === 'activate' ? 'activated' : 'deactivated'
            )
        );
    }

    /**
     * Bulk delete / anonymize users. Same 3-tier policy as the single-user
     * delete (soft, anonymize, hard) with one extra rule for hard mode:
     * users that have rows in RESTRICT-protected tables are reported in
     * `skipped` and not deleted. The remaining selected users are still
     * processed — a partial success is a valid outcome.
     *
     * Self-protection: the current admin can never appear in the list.
     * If somehow the client sends the admin's id, that id is silently
     * moved into the `skipped` map.
     */
    public function bulkDelete()
    {
        $ids  = (array) $this->request->getPost('ids');
        $ids  = array_values(array_filter(array_map('intval', $ids), fn($id) => $id > 0));
        $mode = (string) $this->request->getPost('mode');

        if (! in_array($mode, ['soft', 'anonymize', 'hard'], true)) {
            return $this->bulkResponse(false, 'Invalid delete mode.', 400);
        }
        if (empty($ids)) {
            return $this->bulkResponse(false, 'No users selected.', 400);
        }

        // For hard mode, require the admin to type DELETE as a 2nd factor.
        // Cheaper than per-email confirmation and reasonable for a bulk
        // operation that's already gated by an in-page confirm dialog.
        if ($mode === 'hard') {
            $confirm = trim((string) $this->request->getPost('confirm_phrase'));
            if ($confirm !== 'DELETE') {
                return $this->bulkResponse(
                    false,
                    "To bulk hard-delete, type DELETE (in capital letters, exactly) to confirm.",
                    400
                );
            }
        }

        // Refuse to touch the current admin's own row.
        $currentUserId = (int) session()->get('user_id');
        $safeIds = array_values(array_filter($ids, fn($id) => $id !== $currentUserId));
        if (count($safeIds) === 0) {
            return $this->bulkResponse(
                false,
                'You cannot delete your own account. Remove yourself from the selection.',
                400
            );
        }

        $result = $this->users->bulkDeleteByMode($safeIds, $mode);

        // Write one audit-log entry per affected user with the bulk flag
        // and the per-id reason so a forensic search can recover exactly
        // which user was processed in which way.
        $actionVerb = match ($mode) {
            'soft'      => 'deactivate',
            'anonymize' => 'anonymize',
            'hard'      => 'hard_delete',
        };

        foreach ($result['deleted'] as $id) {
            $oldSnapshot = isset($result['snapshots'][$id])
                ? [
                    'email'      => $result['snapshots'][$id]['email'],
                    'first_name' => $result['snapshots'][$id]['first_name'],
                    'last_name'  => $result['snapshots'][$id]['last_name'],
                ]
                : null;
            $this->audit->logAction(
                $currentUserId,
                $actionVerb,
                'admin',
                'users',
                $id,
                $oldSnapshot,
                ['bulk' => true, 'batch_size' => count($safeIds), 'via' => 'bulk_' . $mode]
            );
        }

        foreach ($result['skipped'] as $id => $reason) {
            // Skipped records are still notable; emit a 'skip' audit row
            // so admins can see what was rejected and why, but tag it
            // clearly so it isn't confused with an actual destructive
            // action.
            $this->audit->logAction(
                $currentUserId,
                'skip',
                'admin',
                'users',
                (int) $id,
                null,
                ['bulk' => true, 'mode' => $mode, 'reason' => $reason]
            );
        }

        $deletedCount = count($result['deleted']);
        $skippedCount = count($result['skipped']);
        $selfSkipped  = count($ids) - count($safeIds);

        if ($deletedCount === 0 && ($skippedCount + $selfSkipped) > 0) {
            $message = sprintf(
                'No users were %s. %d skipped.',
                $mode === 'soft' ? 'deactivated' : ($mode === 'anonymize' ? 'anonymized' : 'deleted'),
                $skippedCount + $selfSkipped
            );
            return $this->bulkResponse(false, $message, 409, $result);
        }

        $parts = [];
        $parts[] = sprintf(
            '%d user%s %s.',
            $deletedCount,
            $deletedCount === 1 ? '' : 's',
            $mode === 'soft' ? 'deactivated' : ($mode === 'anonymize' ? 'anonymized' : 'deleted')
        );
        if ($skippedCount > 0) {
            $parts[] = sprintf('%d skipped (see audit log).', $skippedCount);
        }
        $message = implode(' ', $parts);

        return $this->bulkResponse(true, $message, 200, $result);
    }

    /**
     * Send a JSON response to fetch() callers and a flash-redirect fallback
     * for the no-JS path (links/forms posting directly). `$extra` is merged
     * into the JSON body so callers can pass per-id skip reasons etc.
     */
    private function bulkResponse(bool $ok, string $message, int $status = 200, array $extra = [])
    {
        if ($this->request->isAJAX() || str_contains((string) $this->request->getHeaderLine('Accept'), 'json')) {
            $payload = array_merge([
                'ok'      => $ok,
                'message' => $message,
                'csrf'    => [
                    'name'  => csrf_token(),
                    'value' => csrf_hash(),
                ],
            ], $extra);
            return $this->response
                ->setStatusCode($status)
                ->setContentType('application/json')
                ->setJSON($payload);
        }

        return redirect()->to('/admin/users')->with($ok ? 'success' : 'error', $message);
    }

    public function create()
    {
        $allRoles = $this->roles->orderBy('display_name', 'ASC')->findAll();

        return view('admin/users/create', [
            'title'    => 'Create User — SYNAPSE',
            'heading'  => 'Create User',
            'allRoles' => $allRoles,
            'old'      => session()->getFlashdata('old') ?? [],
        ]);
    }

    public function store()
    {
        $rules = [
            'email'      => 'required|valid_email|is_unique[users.email]',
            'first_name' => 'required|max_length[100]',
            'last_name'  => 'required|max_length[100]',
            'middle_name'=> 'permit_empty|max_length[100]',
            'phone'      => 'permit_empty|max_length[20]',
            'password'   => 'required|min_length[10]',
        ];

        if (! $this->validate($rules)) {
            // If the request came from the dialog (data-synapse-form-link
            // bind in synapse-ui.js), the Referer is the list page where the
            // dialog was rendered, so redirect()->back() would dump the user
            // back onto the list without the form they were filling out.
            // Detect that case via the _dialog_submit marker and route the
            // redirect to the standalone create page where the form
            // re-renders with errors inline and old input repopulated.
            if ($this->request->getPost('_dialog_submit') === '1') {
                return redirect()->to('/admin/users/create')
                    ->withInput()
                    ->with('errors', $this->validator->getErrors());
            }
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'email'         => $this->request->getPost('email'),
            'first_name'    => $this->request->getPost('first_name'),
            'last_name'     => $this->request->getPost('last_name'),
            'middle_name'   => $this->request->getPost('middle_name') ?: null,
            'phone'         => $this->request->getPost('phone') ?: null,
            'password'      => $this->request->getPost('password'),
            'is_active'     => (bool) $this->request->getPost('is_active'),
            'email_verified_at' => date('Y-m-d H:i:s'),
        ];

        $this->users->insert($data);
        $userId = (int) $this->users->getInsertID();

        // Assign roles
        $roleIds = (array) ($this->request->getPost('role_ids') ?? []);
        foreach ($roleIds as $roleId) {
            $roleId = (int) $roleId;
            if ($roleId > 0) {
                $this->userRoles->assignRole($userId, $roleId);
            }
        }

        $this->audit->logAction(
            (int) session()->get('user_id'),
            'create',
            'admin',
            'users',
            $userId,
            null,
            ['email' => $data['email'], 'roles' => $roleIds]
        );

        return redirect()->to('/admin/users/' . $userId)
            ->with('success', 'User created successfully.');
    }

    public function show(int $id)
    {
        $user = $this->users->find($id);
        if ($user === null) {
            return redirect()->to('/admin/users')->with('error', 'User not found.');
        }

        $userRoles = $this->userRoles->getUserRoles($id);
        $allRoles  = $this->roles->orderBy('display_name', 'ASC')->findAll();

        // Recent activity for this user (last 20 audit entries)
        $db = \Config\Database::connect();
        $activity = $db->table('audit_logs')
            ->where('user_id', $id)
            ->orderBy('created_at', 'DESC')
            ->limit(20)
            ->get()->getResultArray();

        return view('admin/users/show', [
            'title'     => 'User Detail — SYNAPSE',
            'heading'   => 'User Detail',
            'user'      => $user,
            'userRoles' => $userRoles,
            'allRoles'  => $allRoles,
            'activity'  => $activity,
        ]);
    }

    public function edit(int $id)
    {
        $user = $this->users->find($id);
        if ($user === null) {
            return redirect()->to('/admin/users')->with('error', 'User not found.');
        }

        $userRoles = $this->userRoles->getUserRoles($id);
        $allRoles  = $this->roles->orderBy('display_name', 'ASC')->findAll();

        return view('admin/users/edit', [
            'title'     => 'Edit User — SYNAPSE',
            'heading'   => 'Edit User',
            'user'      => $user,
            'userRoles' => $userRoles,
            'allRoles'  => $allRoles,
        ]);
    }

    public function update(int $id)
    {
        $user = $this->users->find($id);
        if ($user === null) {
            return redirect()->to('/admin/users')->with('error', 'User not found.');
        }

        $rules = [
            'email'      => "required|valid_email|is_unique[users.email,id,{$id}]",
            'first_name' => 'required|max_length[100]',
            'last_name'  => 'required|max_length[100]',
            'middle_name'=> 'permit_empty|max_length[100]',
            'phone'      => 'permit_empty|max_length[20]',
        ];

        if (! $this->validate($rules)) {
            // If the form was submitted via the dialog, redirect back to
            // the edit page so the user sees the form re-rendered with
            // errors + repopulated values instead of landing on the list.
            if ($this->request->getPost('_dialog_submit') === '1') {
                return redirect()->to('/admin/users/' . $id . '/edit')
                    ->withInput()
                    ->with('errors', $this->validator->getErrors());
            }
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $oldValues = [
            'email'      => $user['email'],
            'first_name' => $user['first_name'],
            'last_name'  => $user['last_name'],
            'is_active'  => $user['is_active'],
        ];

        $data = [
            'email'       => $this->request->getPost('email'),
            'first_name'  => $this->request->getPost('first_name'),
            'last_name'   => $this->request->getPost('last_name'),
            'middle_name' => $this->request->getPost('middle_name') ?: null,
            'phone'       => $this->request->getPost('phone') ?: null,
            'is_active'   => (bool) $this->request->getPost('is_active'),
        ];

        $this->users->update($id, $data);

        $newValues = $data;
        $this->audit->logAction(
            (int) session()->get('user_id'),
            'update',
            'admin',
            'users',
            $id,
            $oldValues,
            $newValues
        );

        return redirect()->to('/admin/users/' . $id)
            ->with('success', 'User updated successfully.');
    }

    public function toggle(int $id)
    {
        $user = $this->users->find($id);
        if ($user === null) {
            return redirect()->to('/admin/users')->with('error', 'User not found.');
        }

        // Prevent self-disable
        if ((int) $user['id'] === (int) session()->get('user_id')) {
            return redirect()->to('/admin/users/' . $id)
                ->with('error', 'You cannot deactivate your own account.');
        }

        $newStatus = ! (bool) $user['is_active'];
        $this->users->update($id, ['is_active' => $newStatus]);

        $this->audit->logAction(
            (int) session()->get('user_id'),
            $newStatus ? 'activate' : 'deactivate',
            'admin',
            'users',
            $id,
            ['is_active' => $user['is_active']],
            ['is_active' => $newStatus]
        );

        return redirect()->to('/admin/users/' . $id)
            ->with('success', 'User ' . ($newStatus ? 'activated' : 'deactivated') . '.');
    }

    public function resetPassword(int $id)
    {
        $user = $this->users->find($id);
        if ($user === null) {
            return redirect()->to('/admin/users')->with('error', 'User not found.');
        }

        $newPassword = (string) $this->request->getPost('new_password');
        if (strlen($newPassword) < 10) {
            return redirect()->back()->with('error', 'Password must be at least 10 characters.');
        }

        // Use the model's helper so the hashing rule lives in one place.
        try {
            $this->users->setPassword($id, $newPassword);
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        $this->audit->logAction(
            (int) session()->get('user_id'),
            'password_reset',
            'admin',
            'users',
            $id
        );

        return redirect()->to('/admin/users/' . $id)
            ->with('success', 'Password reset successfully.');
    }

    public function assignRole(int $userId)
    {
        $user = $this->users->find($userId);
        if ($user === null) {
            return redirect()->to('/admin/users')->with('error', 'User not found.');
        }

        $roleId = (int) $this->request->getPost('role_id');
        $role   = $this->roles->find($roleId);
        if ($role === null) {
            return redirect()->back()->with('error', 'Invalid role.');
        }

        $this->userRoles->assignRole($userId, $roleId);

        $this->audit->logAction(
            (int) session()->get('user_id'),
            'assign_role',
            'admin',
            'user_roles',
            $userId,
            null,
            ['role_id' => $roleId, 'role_name' => $role['name']]
        );

        return redirect()->to('/admin/users/' . $userId)
            ->with('success', 'Role assigned.');
    }

    public function revokeRole(int $userId)
    {
        $user = $this->users->find($userId);
        if ($user === null) {
            return redirect()->to('/admin/users')->with('error', 'User not found.');
        }

        $roleId = (int) $this->request->getPost('role_id');
        $role   = $this->roles->find($roleId);

        $this->userRoles->revokeRole($userId, $roleId);

        $this->audit->logAction(
            (int) session()->get('user_id'),
            'revoke_role',
            'admin',
            'user_roles',
            $userId,
            ['role_id' => $roleId, 'role_name' => $role['name'] ?? null],
            null
        );

        return redirect()->to('/admin/users/' . $userId)
            ->with('success', 'Role revoked.');
    }

    /**
     * Delete (or anonymize) a user. Layered policy:
     *
     *   1. mode=soft      → flip is_active=0, write audit. Safe default.
     *   2. mode=anonymize → scrub PII, keep row for FK history.
     *   3. mode=hard      → DELETE the row, only allowed if the user has
     *                       zero rows in RESTRICT-protected tables.
     *
     * Hard-delete requires the caller to type the user's email into the
     * confirmation form so the request carries `confirm_email`. The
     * server re-checks that value matches the live row, so a typo or a
     * stale page won't actually delete the wrong person.
     */
    public function delete(int $id)
    {
        $user = $this->users->find($id);
        if ($user === null) {
            return $this->deleteResponse(false, 'User not found.', 404, '/admin/users');
        }

        // Hard guard: an admin cannot delete themselves.
        $currentUserId = (int) session()->get('user_id');
        if ((int) $user['id'] === $currentUserId) {
            return $this->deleteResponse(
                false,
                'You cannot delete your own account. Ask another administrator.',
                400
            );
        }

        $mode = (string) $this->request->getPost('mode');
        if (! in_array($mode, ['soft', 'anonymize', 'hard'], true)) {
            return $this->deleteResponse(false, 'Invalid delete mode.', 400);
        }

        // Hard-delete requires typing the user's email as a second factor.
        // We compare case-insensitively and trimmed against the live row
        // (not the value the form was rendered with) so a typo'd or stale
        // page won't pass.
        if ($mode === 'hard') {
            $confirm = strtolower(trim((string) $this->request->getPost('confirm_email')));
            if ($confirm !== strtolower(trim((string) $user['email']))) {
                return $this->deleteResponse(
                    false,
                    'Confirmation email does not match. Please type the user\'s email exactly.',
                    400
                );
            }
        }

        $actor = $currentUserId;

        if ($mode === 'soft') {
            $this->users->update($id, ['is_active' => false]);
            $this->audit->logAction(
                $actor, 'deactivate', 'admin', 'users', $id,
                ['is_active' => true],
                ['is_active' => false, 'via' => 'delete_soft']
            );
            return $this->deleteResponse(true, 'User deactivated. They can no longer sign in but their record and history are preserved.');
        }

        if ($mode === 'anonymize') {
            $ok = $this->users->anonymize($id);
            if (! $ok) {
                return $this->deleteResponse(false, 'Could not anonymize user.', 500);
            }
            $this->audit->logAction(
                $actor, 'anonymize', 'admin', 'users', $id,
                null,
                ['via' => 'delete_anonymize']
            );
            return $this->deleteResponse(true, 'User anonymized. Personal information has been removed; clinical and audit history remain intact.');
        }

        // mode === 'hard'
        $blockers = $this->users->countDeleteBlockers($id);
        if ($blockers > 0) {
            return $this->deleteResponse(
                false,
                "Cannot hard-delete: user has {$blockers} related record(s) in clinical, inventory, or outreach tables. Use 'Anonymize' instead, which preserves history.",
                409
            );
        }

        // Capture the row for the audit log before it's gone.
        $snapshot = [
            'email'      => $user['email'],
            'first_name' => $user['first_name'],
            'last_name'  => $user['last_name'],
        ];

        $ok = $this->users->hardDelete($id);
        if (! $ok) {
            return $this->deleteResponse(false, 'Hard delete failed (database error or FK violation).', 500);
        }

        $this->audit->logAction(
            $actor, 'hard_delete', 'admin', 'users', $id,
            $snapshot,
            ['via' => 'delete_hard']
        );

        return $this->deleteResponse(true, 'User permanently deleted.', 200, '/admin/users');
    }

    /**
     * Shape delete responses consistently. JSON for fetch() callers, redirect
     * + flash for no-JS / direct POSTs. Always includes the fresh CSRF token
     * so the JS can refresh its form before retrying without forcing a page
     * reload — CSRF regeneration is enabled in Security.php.
     */
    private function deleteResponse(bool $ok, string $message, int $status = 200, ?string $redirectTo = null)
    {
        $redirectTo = $redirectTo ?? '/admin/users/' . ($this->request->getPost('id') ?? '');

        if ($this->request->isAJAX() || str_contains((string) $this->request->getHeaderLine('Accept'), 'json')) {
            $csrfToken = csrf_token();
            $csrfHash  = csrf_hash();
            return $this->response
                ->setStatusCode($status)
                ->setContentType('application/json')
                ->setJSON([
                    'ok' => $ok,
                    'message' => $message,
                    'csrf'    => [
                        'name'  => $csrfToken,
                        'value' => $csrfHash,
                    ],
                ]);
        }

        return redirect()->to($redirectTo)->with($ok ? 'success' : 'error', $message);
    }
}