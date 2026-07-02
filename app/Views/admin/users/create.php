<?= $this->extend('layouts/main') ?>

<?php
    // Validation errors may be set explicitly by the controller as
    // 'errors' flashdata, OR auto-populated by CodeIgniter as
    // '_ci_validation_errors'. Read both so inline rendering works
    // regardless of which path triggered the redirect.
    $formErrors = session()->getFlashdata('errors')
        ?? session()->getFlashdata('_ci_validation_errors')
        ?? [];

    // Helper: render inline error for a specific field (if any)
    $fieldError = function (string $field) use ($formErrors): ?string {
        return $formErrors[$field] ?? null;
    };
?>

<?= $this->section('content') ?>

<div style="margin-bottom: 1rem;">
    <a href="<?= base_url('admin/users') ?>" style="color: var(--gray-500); text-decoration: none; font-size: 0.85rem;">
        <i class="fas fa-arrow-left"></i> Back to users
    </a>
</div>

<div class="card" style="max-width: 720px;">
    <div class="card-header"><i class="fas fa-user-plus"></i> New User</div>
    <div class="card-body">
        <form method="post" action="<?= base_url('admin/users/store') ?>" autocomplete="off" novalidate
              data-synapse-form-dialog
              data-dialog-title="New User"
              data-dialog-icon="fas fa-user-plus"
              data-dialog-submit-label="Create User"
              data-dialog-cancel-label="Cancel"
              data-dialog-width>
            <?= csrf_field() ?>

            <?php if (! empty($formErrors)): ?>
                <div class="syn-alert syn-alert--danger" role="alert" style="margin-bottom: 1rem;">
                    <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
                    <div>
                        <strong>Please fix the following:</strong>
                        <ul style="margin: 0.35rem 0 0 1rem; padding: 0;">
                            <?php foreach ($formErrors as $err): ?>
                                <li><?= esc($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label for="user-first-name" style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">First Name <span aria-hidden="true">*</span></label>
                    <input type="text" id="user-first-name" name="first_name" required maxlength="100"
                           aria-required="true"
                           <?php if ($e = $fieldError('first_name')): ?>aria-invalid="true" aria-describedby="user-first-name-err"<?php endif; ?>
                           value="<?= set_value('first_name') ?>"
                           style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.4rem;">
                    <?php if ($e = $fieldError('first_name')): ?>
                        <small id="user-first-name-err" class="syn-field-error" style="display:block; color: var(--danger); font-size: 0.75rem; margin-top: 0.2rem;"><?= esc($e) ?></small>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="user-last-name" style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Last Name <span aria-hidden="true">*</span></label>
                    <input type="text" id="user-last-name" name="last_name" required maxlength="100"
                           aria-required="true"
                           <?php if ($e = $fieldError('last_name')): ?>aria-invalid="true" aria-describedby="user-last-name-err"<?php endif; ?>
                           value="<?= set_value('last_name') ?>"
                           style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.4rem;">
                    <?php if ($e = $fieldError('last_name')): ?>
                        <small id="user-last-name-err" class="syn-field-error" style="display:block; color: var(--danger); font-size: 0.75rem; margin-top: 0.2rem;"><?= esc($e) ?></small>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="user-middle-name" style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Middle Name</label>
                    <input type="text" id="user-middle-name" name="middle_name" maxlength="100"
                           value="<?= set_value('middle_name') ?>"
                           style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.4rem;">
                </div>
                <div>
                    <label for="user-phone" style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Phone</label>
                    <input type="tel" id="user-phone" name="phone" maxlength="20"
                           autocomplete="tel"
                           value="<?= set_value('phone') ?>"
                           style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.4rem;">
                </div>
                <div style="grid-column: 1 / -1;">
                    <label for="user-email" style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Email <span aria-hidden="true">*</span></label>
                    <input type="email" id="user-email" name="email" required maxlength="255"
                           aria-required="true" autocomplete="email"
                           <?php if ($e = $fieldError('email')): ?>aria-invalid="true" aria-describedby="user-email-err"<?php endif; ?>
                           value="<?= set_value('email') ?>"
                           style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.4rem;">
                    <?php if ($e = $fieldError('email')): ?>
                        <small id="user-email-err" class="syn-field-error" style="display:block; color: var(--danger); font-size: 0.75rem; margin-top: 0.2rem;"><?= esc($e) ?></small>
                    <?php endif; ?>
                </div>
                <div style="grid-column: 1 / -1;">
                    <label for="user-password" style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Temporary Password <span aria-hidden="true">*</span></label>
                    <input type="text" id="user-password" name="password" required minlength="10" maxlength="128"
                           aria-required="true" autocomplete="new-password"
                           <?php if ($e = $fieldError('password')): ?>aria-invalid="true" aria-describedby="user-password-err user-password-hint"<?php else: ?>aria-describedby="user-password-hint"<?php endif; ?>
                           value="<?= set_value('password') ?>"
                           style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.4rem; font-family: monospace;">
                    <small id="user-password-hint" style="color: var(--gray-500); font-size: 0.75rem;">Minimum 10 characters. The user should change this on first login.</small>
                    <?php if ($e = $fieldError('password')): ?>
                        <small id="user-password-err" class="syn-field-error" style="display:block; color: var(--danger); font-size: 0.75rem; margin-top: 0.2rem;"><?= esc($e) ?></small>
                    <?php endif; ?>
                </div>
                <div style="grid-column: 1 / -1;">
                    <fieldset style="border: 0; padding: 0; margin: 0;">
                        <legend style="font-size: 0.8rem; font-weight: 600; margin-bottom: 0.5rem; padding: 0;">Roles</legend>
                        <div role="group" aria-label="Assign roles" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.5rem;">
                            <?php
                                $oldRoleIds = (array) old('role_ids', []);
                            ?>
                            <?php foreach ($allRoles as $r): ?>
                                <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; background: var(--gray-50); border-radius: 0.4rem; cursor: pointer;">
                                    <input type="checkbox" name="role_ids[]" value="<?= (int) $r['id'] ?>"
                                           <?= in_array((int) $r['id'], array_map('intval', $oldRoleIds), true) ? 'checked' : '' ?>>
                                    <span>
                                        <strong style="font-size: 0.85rem;"><?= esc($r['display_name']) ?></strong>
                                        <?php if (! empty($r['description'])): ?>
                                            <br><span style="font-size: 0.7rem; color: var(--gray-500);"><?= esc($r['description']) ?></span>
                                        <?php endif; ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                </div>
                <div style="grid-column: 1 / -1;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" id="user-is-active" name="is_active" value="1"
                               <?= old('is_active', '1') ? 'checked' : '' ?>>
                        <span style="font-size: 0.85rem; font-weight: 600;">Active</span>
                        <span style="color: var(--gray-500); font-size: 0.75rem;">Uncheck to create the account in disabled state.</span>
                    </label>
                </div>
            </div>

            <noscript>
            <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem;">
                <button type="submit"
                        style="padding: 0.6rem 1.4rem; background: var(--primary-600); color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-user-plus"></i> Create User
                </button>
                <a href="<?= base_url('admin/users') ?>"
                   style="padding: 0.6rem 1.4rem; background: var(--gray-100); color: var(--gray-700); text-decoration: none; border-radius: 0.5rem; font-weight: 600;">
                    Cancel
                </a>
            </div>
            </noscript>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
