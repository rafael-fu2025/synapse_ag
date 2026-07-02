<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div style="margin-bottom: 1rem;">
    <a href="<?= base_url('admin/roles/' . $role['id']) ?>" style="color: var(--gray-500); text-decoration: none; font-size: 0.85rem;">
        <i class="fas fa-arrow-left"></i> Back to role
    </a>
</div>

<div class="card" style="max-width: 640px;">
    <div class="card-header"><i class="fas fa-pen"></i> Edit Role</div>
    <div class="card-body">
        <form method="post" action="<?= base_url('admin/roles/update/' . $role['id']) ?>"
              data-synapse-form-dialog
              data-dialog-title="Edit Role"
              data-dialog-subtitle="<?= esc($role['name']) ?>"
              data-dialog-icon="fas fa-user-shield"
              data-dialog-submit-label="Save Changes"
              data-dialog-cancel-label="Cancel">
            <?= csrf_field() ?>

            <div style="display: grid; gap: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Slug *</label>
                    <input type="text" name="name" required maxlength="50" pattern="[a-z_]+"
                           value="<?= esc($role['name']) ?>"
                           style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.4rem; font-family: monospace;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Display Name *</label>
                    <input type="text" name="display_name" required maxlength="100"
                           value="<?= esc($role['display_name']) ?>"
                           style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.4rem;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Description</label>
                    <textarea name="description" maxlength="500" rows="3"
                              style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.4rem; font-family: inherit; resize: vertical;"><?= esc($role['description'] ?? '') ?></textarea>
                </div>
            </div>

            <noscript>
                <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem;">
                    <button type="submit"
                            style="padding: 0.6rem 1.4rem; background: var(--primary-600); color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="<?= base_url('admin/roles/' . $role['id']) ?>"
                       style="padding: 0.6rem 1.4rem; background: var(--gray-100); color: var(--gray-700); text-decoration: none; border-radius: 0.5rem; font-weight: 600;">
                        Cancel
                    </a>
                </div>
            </noscript>
        </form>
    </div>
</div>
<?= $this->endSection() ?>