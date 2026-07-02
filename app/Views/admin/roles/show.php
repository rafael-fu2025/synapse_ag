<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
    <a href="<?= base_url('admin/roles') ?>" style="color: var(--gray-500); text-decoration: none; font-size: 0.85rem;">
        <i class="fas fa-arrow-left"></i> Back to roles
    </a>
    <a href="<?= base_url('admin/roles/' . $role['id'] . '/edit') ?>"
       style="padding: 0.55rem 1.1rem; background: var(--info); color: white; text-decoration: none; border-radius: 0.5rem; font-weight: 600; font-size: 0.85rem;">
        <i class="fas fa-pen"></i> Edit Role
    </a>
</div>

<div class="reports-grid">
    <div class="card">
        <div class="card-header"><i class="fas fa-info-circle"></i> Role Info</div>
        <div class="card-body">
            <table class="kv-table">
                <tr><th>Slug</th><td style="font-family: monospace;"><?= esc($role['name']) ?></td></tr>
                <tr><th>Display Name</th><td><?= esc($role['display_name']) ?></td></tr>
                <tr><th>Description</th><td><?= esc($role['description'] ?? '—') ?></td></tr>
                <tr><th>Created</th><td><?= esc(date('M d, Y', strtotime($role['created_at']))) ?></td></tr>
                <tr><th>Assigned Permissions</th><td><span class="badge badge-blue"><?= count($assignedPermIds) ?></span></td></tr>
                <tr><th>Users with this Role</th><td><span class="badge badge-purple"><?= count($users) ?></span></td></tr>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><i class="fas fa-users"></i> Users</div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($users)): ?>
                <div class="empty-state">No users have this role.</div>
            <?php else: ?>
                <table class="table-mini">
                    <thead><tr><th>Name</th><th>Email</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('admin/users/' . $u['id']) ?>" style="color: var(--primary-600); text-decoration: none;">
                                    <?= esc(trim($u['first_name'] . ' ' . $u['last_name'])) ?>
                                </a>
                            </td>
                            <td style="font-family: monospace; font-size: 0.75rem; color: var(--gray-600);"><?= esc($u['email']) ?></td>
                            <td>
                                <?php if ($u['is_active']): ?>
                                    <span class="badge badge-green">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-gray">Inactive</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card" style="margin-top: 1.25rem;">
    <div class="card-header"><i class="fas fa-key"></i> Permission Matrix</div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($allPerms)): ?>
            <div class="empty-state">No permissions defined. Seed the <code>permissions</code> table to use this matrix.</div>
        <?php else: ?>
            <?php foreach ($allPerms as $module => $perms): ?>
                <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--gray-100);">
                    <h4 style="font-size: 0.85rem; font-weight: 700; color: var(--gray-700); margin: 0 0 0.5rem; text-transform: capitalize;">
                        <i class="fas fa-folder"></i> <?= esc($module) ?>
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 0.5rem;">
                        <?php foreach ($perms as $p): ?>
                            <?php $isAssigned = in_array($p['id'], $assignedPermIds, true); ?>
                            <form method="post" action="<?= base_url('admin/roles/toggle-permission/' . $role['id']) ?>" style="margin: 0;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="permission_id" value="<?= (int) $p['id'] ?>">
                                <button type="submit"
                                        style="width: 100%; text-align: left; padding: 0.5rem 0.75rem;
                                               background: <?= $isAssigned ? 'rgba(16,185,129,0.08)' : 'var(--gray-50)' ?>;
                                               border: 1px solid <?= $isAssigned ? 'rgba(16,185,129,0.3)' : 'var(--gray-200)' ?>;
                                               border-radius: 0.4rem; cursor: pointer;
                                               color: <?= $isAssigned ? '#15803D' : 'var(--gray-700)' ?>;
                                               font-size: 0.8rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-<?= $isAssigned ? 'check-square' : 'square' ?>"></i>
                                    <span style="flex: 1;">
                                        <code style="font-size: 0.75rem;"><?= esc($p['name']) ?></code>
                                        <?php if (! empty($p['description'])): ?>
                                            <br><span style="font-size: 0.7rem; color: var(--gray-500); font-family: inherit;"><?= esc($p['description']) ?></span>
                                        <?php endif; ?>
                                    </span>
                                </button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
    /* .table-mini and .kv-table are now centralized in synapse-ui.css */
    .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 99px; font-size: 0.7rem; font-weight: 600; }
    .badge-blue { background: var(--primary-50); color: var(--primary-700); }
    .badge-green { background: rgba(16,185,129,0.1); color: #15803D; }
    .badge-gray { background: var(--gray-100); color: var(--gray-700); }
    .badge-purple { background: #F5F3FF; color: #6D28D9; }
    .empty-state { padding: 2.5rem 1rem; text-align: center; color: var(--gray-500); }
</style>
<?= $this->endSection() ?>