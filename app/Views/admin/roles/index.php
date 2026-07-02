<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <p style="color: var(--gray-600); margin: 0; font-size: 0.875rem;">
        <?= count($roles) ?> role<?= count($roles) === 1 ? '' : 's' ?> defined
    </p>
    <a href="<?= base_url('admin/roles/create') ?>"
       data-synapse-form-link
       data-dialog-title="New Role"
       data-dialog-icon="fas fa-shield-halved"
       data-dialog-width
       style="padding: 0.55rem 1.1rem; background: var(--primary-600); color: white; text-decoration: none; border-radius: 0.5rem; font-weight: 600; font-size: 0.85rem;">
        <i class="fas fa-plus"></i> New Role
    </a>
</div>

<div class="card">
    <div class="card-body" style="padding: 0;">
        <table class="table-mini">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Description</th>
                    <th style="text-align: right;">Permissions</th>
                    <th style="text-align: right;">Users</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($roles)): ?>
                <tr><td colspan="5" class="empty-state">No roles defined yet.</td></tr>
            <?php else: ?>
                <?php foreach ($roles as $r): ?>
                    <tr>
                        <td>
                            <strong><?= esc($r['display_name']) ?></strong>
                            <br><span style="font-size: 0.7rem; color: var(--gray-500); font-family: monospace;"><?= esc($r['name']) ?></span>
                        </td>
                        <td style="color: var(--gray-600); font-size: 0.8rem; max-width: 380px;">
                            <?= esc($r['description'] ?? '—') ?>
                        </td>
                        <td style="text-align: right;">
                            <span class="badge badge-blue"><?= number_format($r['permission_count']) ?></span>
                        </td>
                        <td style="text-align: right;">
                            <span class="badge badge-purple"><?= number_format($r['user_count']) ?></span>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="<?= base_url('admin/roles/' . $r['id']) ?>" style="color: var(--primary-600); text-decoration: none; font-size: 0.8rem; margin-right: 0.75rem;">
                                <i class="fas fa-eye"></i> Permissions
                            </a>
                            <a href="<?= base_url('admin/roles/' . $r['id'] . '/edit') ?>"
                               data-synapse-form-link
                               data-dialog-title="Edit Role"
                               data-dialog-icon="fas fa-user-shield"
                               data-dialog-width
                               style="color: var(--info); text-decoration: none; font-size: 0.8rem;">
                                <i class="fas fa-pen"></i> Edit
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    /* .table-mini is now centralized in synapse-ui.css */
    .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 99px; font-size: 0.7rem; font-weight: 600; }
    .badge-blue { background: var(--primary-50); color: var(--primary-700); }
    .badge-purple { background: #F5F3FF; color: #6D28D9; }
    .empty-state { padding: 2.5rem 1rem; text-align: center; color: var(--gray-500); }
</style>
<?= $this->endSection() ?>