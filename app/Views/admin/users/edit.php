<?= $this->extend('layouts/main') ?>

<?php
    $formErrors = session()->getFlashdata('errors')
        ?? session()->getFlashdata('_ci_validation_errors')
        ?? [];

    $fieldError = function (string $field) use ($formErrors): ?string {
        return $formErrors[$field] ?? null;
    };

    // is_active needs coercion: checkboxes only post when checked,
    // so set_value('is_active') returns null if unchecked.
    $isActiveChecked = set_value('is_active', $user['is_active'] ? '1' : '0') === '1'
        || set_value('is_active', null) === '1';
?>

<?= $this->section('content') ?>

<div style="margin-bottom: 1rem;">
    <a href="<?= base_url('admin/users/' . $user['id']) ?>" style="color: var(--gray-500); text-decoration: none; font-size: 0.85rem;">
        <i class="fas fa-arrow-left"></i> Back to user
    </a>
</div>

<div class="card" style="max-width: 720px;">
    <div class="card-header"><i class="fas fa-pen"></i> Edit User</div>
    <div class="card-body">
        <form method="post" action="<?= base_url('admin/users/update/' . $user['id']) ?>" autocomplete="off" novalidate
              data-synapse-form-dialog
              data-dialog-title="Edit User"
              data-dialog-subtitle="<?= esc($user['first_name'] . ' ' . $user['last_name']) ?>"
              data-dialog-icon="fas fa-user-edit"
              data-dialog-submit-label="Save Changes"
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
                           value="<?= set_value('first_name', $user['first_name']) ?>"
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
                           value="<?= set_value('last_name', $user['last_name']) ?>"
                           style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.4rem;">
                    <?php if ($e = $fieldError('last_name')): ?>
                        <small id="user-last-name-err" class="syn-field-error" style="display:block; color: var(--danger); font-size: 0.75rem; margin-top: 0.2rem;"><?= esc($e) ?></small>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="user-middle-name" style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Middle Name</label>
                    <input type="text" id="user-middle-name" name="middle_name" maxlength="100"
                           value="<?= set_value('middle_name', $user['middle_name'] ?? '') ?>"
                           style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.4rem;">
                </div>
                <div>
                    <label for="user-phone" style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Phone</label>
                    <input type="tel" id="user-phone" name="phone" maxlength="20" autocomplete="tel"
                           value="<?= set_value('phone', $user['phone'] ?? '') ?>"
                           style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.4rem;">
                </div>
                <div style="grid-column: 1 / -1;">
                    <label for="user-email" style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem;">Email <span aria-hidden="true">*</span></label>
                    <input type="email" id="user-email" name="email" required maxlength="255"
                           aria-required="true" autocomplete="email"
                           <?php if ($e = $fieldError('email')): ?>aria-invalid="true" aria-describedby="user-email-err"<?php endif; ?>
                           value="<?= set_value('email', $user['email']) ?>"
                           style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.4rem;">
                    <?php if ($e = $fieldError('email')): ?>
                        <small id="user-email-err" class="syn-field-error" style="display:block; color: var(--danger); font-size: 0.75rem; margin-top: 0.2rem;"><?= esc($e) ?></small>
                    <?php endif; ?>
                </div>
                <div style="grid-column: 1 / -1;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" id="user-is-active" name="is_active" value="1"
                               <?= $isActiveChecked ? 'checked' : '' ?>>
                        <span style="font-size: 0.85rem; font-weight: 600;">Active</span>
                    </label>
                </div>
            </div>

            <div style="margin-top: 1.5rem; padding: 0.75rem; background: var(--gray-50); border-radius: 0.4rem; font-size: 0.8rem; color: var(--gray-600);">
                <i class="fas fa-circle-info" aria-hidden="true"></i>
                Roles and password are managed separately — use the user detail page to assign roles or reset the password.
            </div>

            <noscript>
            <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                <button type="submit"
                        style="padding: 0.6rem 1.4rem; background: var(--primary-600); color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <a href="<?= base_url('admin/users/' . $user['id']) ?>"
                   style="padding: 0.6rem 1.4rem; background: var(--gray-100); color: var(--gray-700); text-decoration: none; border-radius: 0.5rem; font-weight: 600;">
                    Cancel
                </a>
            </div>
            </noscript>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
