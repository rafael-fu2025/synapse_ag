<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
    <a href="<?= base_url('admin/users') ?>" style="color: var(--gray-500); text-decoration: none; font-size: 0.85rem;">
        <i class="fas fa-arrow-left"></i> Back to users
    </a>
    <div style="display: flex; gap: 0.5rem;">
        <a href="<?= base_url('admin/users/' . $user['id'] . '/edit') ?>"
           data-synapse-form-link
           data-dialog-title="Edit User"
           data-dialog-subtitle="<?= esc(trim($user['first_name'] . ' ' . $user['last_name'])) ?>"
           data-dialog-icon="fas fa-user-edit"
           data-dialog-width
           style="padding: 0.55rem 1.1rem; background: var(--info); color: white; text-decoration: none; border-radius: 0.5rem; font-weight: 600; font-size: 0.85rem;">
            <i class="fas fa-pen"></i> Edit
        </a>
    </div>
</div>

<div class="reports-grid">
    <div class="card">
        <div class="card-header"><i class="fas fa-id-card"></i> Profile</div>
        <div class="card-body">
            <table class="kv-table">
                <tr><th>Full Name</th><td><?= esc(trim($user['first_name'] . ' ' . ($user['middle_name'] ?? '') . ' ' . $user['last_name'])) ?></td></tr>
                <tr><th>Email</th><td style="font-family: monospace; font-size: 0.85rem;"><?= esc($user['email']) ?></td></tr>
                <tr><th>Phone</th><td><?= esc($user['phone'] ?: '—') ?></td></tr>
                <tr><th>Status</th><td>
                    <?php if ($user['is_active']): ?>
                        <span class="badge badge-green">Active</span>
                    <?php else: ?>
                        <span class="badge badge-gray">Inactive</span>
                    <?php endif; ?>
                </td></tr>
                <tr><th>2FA</th><td>
                    <?php if ($user['two_factor_enabled']): ?>
                        <span class="badge badge-green">Enabled</span>
                    <?php else: ?>
                        <span class="badge badge-gray">Disabled</span>
                    <?php endif; ?>
                </td></tr>
                <tr><th>Last Login</th><td><?= $user['last_login_at'] ? esc(date('M d, Y h:i A', strtotime($user['last_login_at']))) : '<span style="color: var(--gray-400);">never</span>' ?></td></tr>
                <tr><th>Created</th><td><?= esc(date('M d, Y h:i A', strtotime($user['created_at']))) ?></td></tr>
            </table>

            <?php if ((int) $user['id'] !== (int) session()->get('user_id')): ?>
                <form method="post" action="<?= base_url('admin/users/toggle/' . $user['id']) ?>" style="margin-top: 1rem;">
                    <?= csrf_field() ?>
                    <button type="submit"
                            data-synapse-confirm
                            data-synapse-confirm-title="<?= $user['is_active'] ? 'Deactivate this account?' : 'Activate this account?' ?>"
                            data-synapse-confirm-body="<?= $user['is_active'] ? 'The user will be unable to sign in until reactivated. Their data and roles will be preserved.' : 'The user will regain the ability to sign in with their existing credentials.' ?>"
                            data-synapse-confirm-text="<?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>"
                            <?= $user['is_active'] ? 'data-synapse-confirm-danger' : '' ?>
                            style="padding: 0.5rem 1rem; background: <?= $user['is_active'] ? 'var(--danger)' : 'var(--success)' ?>; color: white; border: none; border-radius: 0.4rem; font-weight: 600; cursor: pointer; font-size: 0.85rem;">
                        <i class="fas fa-<?= $user['is_active'] ? 'ban' : 'check' ?>"></i>
                        <?= $user['is_active'] ? 'Deactivate Account' : 'Activate Account' ?>
                    </button>
                </form>
            <?php endif; ?>

            <?php if ((int) $user['id'] !== (int) session()->get('user_id')): ?>
                <hr style="margin: 1.25rem 0; border: 0; border-top: 1px solid var(--gray-200);">
                <h4 style="font-size: 0.8rem; font-weight: 700; margin: 0 0 0.5rem; color: var(--danger);">Danger zone</h4>
                <button type="button"
                        id="delete-user-btn"
                        style="padding: 0.5rem 1rem; background: var(--danger); color: white; border: none; border-radius: 0.4rem; font-weight: 600; cursor: pointer; font-size: 0.85rem;">
                    <i class="fas fa-trash"></i> Delete account…
                </button>
                <p style="font-size: 0.72rem; color: var(--gray-500); margin: 0.5rem 0 0;">
                    Three options: deactivate (reversible), anonymize (PII removed, history preserved), or hard-delete (permanent).
                </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><i class="fas fa-user-shield"></i> Roles</div>
        <div class="card-body">
            <?php if (empty($userRoles)): ?>
                <p style="color: var(--gray-500); font-size: 0.85rem; margin-bottom: 1rem;">No roles assigned.</p>
            <?php else: ?>
                <?php foreach ($userRoles as $r): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid var(--gray-100);">
                        <div>
                            <strong><?= esc($r['display_name']) ?></strong>
                            <br><span style="font-size: 0.7rem; color: var(--gray-500); font-family: monospace;"><?= esc($r['name']) ?></span>
                        </div>
                        <form method="post" action="<?= base_url('admin/users/revoke-role/' . $user['id']) ?>" style="margin: 0;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="role_id" value="<?= (int) $r['id'] ?>">
                            <button type="submit"
                                    data-synapse-confirm
                                    data-synapse-confirm-title="Revoke this role?"
                                    data-synapse-confirm-body="The user will lose access to all permissions granted by this role. You can re-assign it later."
                                    data-synapse-confirm-text="Revoke"
                                    style="padding: 0.3rem 0.7rem; background: var(--gray-100); color: var(--danger); border: none; border-radius: 0.3rem; font-size: 0.75rem; cursor: pointer;">
                                <i class="fas fa-xmark"></i> Revoke
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <form method="post" action="<?= base_url('admin/users/assign-role/' . $user['id']) ?>" style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                <?= csrf_field() ?>
                <select name="role_id" required data-synapse-dropdown style="flex: 1;">
                    <option value="">Select role…</option>
                    <?php
                    $assignedIds = array_column($userRoles, 'id');
                    foreach ($allRoles as $r):
                        if (in_array($r['id'], $assignedIds, true)) continue;
                    ?>
                        <option value="<?= (int) $r['id'] ?>"><?= esc($r['display_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" style="padding: 0.45rem 0.9rem; background: var(--primary-600); color: white; border: none; border-radius: 0.4rem; font-weight: 600; cursor: pointer; font-size: 0.85rem;">
                    Assign
                </button>
            </form>

            <hr style="margin: 1.5rem 0; border: 0; border-top: 1px solid var(--gray-200);">

            <h4 style="font-size: 0.85rem; font-weight: 700; margin: 0 0 0.5rem;">Reset Password</h4>
            <form method="post" action="<?= base_url('admin/users/reset-password/' . $user['id']) ?>" style="margin: 0;">
                <?= csrf_field() ?>
                <div style="display: flex; gap: 0.5rem;">
                    <div style="flex: 1; position: relative;">
                        <input id="reset-password-input" type="password" name="new_password"
                               placeholder="New password (≥10 chars)" required minlength="10"
                               autocomplete="new-password" aria-describedby="reset-password-rules"
                               style="width: 100%; padding: 0.45rem 2.25rem 0.45rem 0.6rem; border: 1px solid var(--gray-300); border-radius: 0.4rem; font-family: monospace;">
                        <button type="button" id="reset-password-toggle" aria-label="Show password" aria-pressed="false" title="Show password"
                                style="position: absolute; right: 0.4rem; top: 50%; transform: translateY(-50%); background: transparent; border: 0; color: var(--gray-500); cursor: pointer; padding: 0.25rem 0.4rem; line-height: 1;">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <button type="submit" data-synapse-confirm
                            data-synapse-confirm-title="Reset this user's password?"
                            data-synapse-confirm-body="The new password will replace the user's current password immediately. They will need to use the new password on their next sign-in."
                            data-synapse-confirm-text="Reset password" data-synapse-confirm-danger
                            style="padding: 0.45rem 0.9rem; background: var(--warning); color: white; border: none; border-radius: 0.4rem; font-weight: 600; cursor: pointer; font-size: 0.85rem;">
                        Reset
                    </button>
                </div>
                <div id="reset-password-rules" style="margin-top: 0.5rem; font-size: 0.72rem; color: var(--gray-600);">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                        <div aria-hidden="true" style="flex: 1; height: 4px; background: var(--gray-200); border-radius: 2px; overflow: hidden;">
                            <div id="reset-password-bar" style="width: 0%; height: 100%; background: var(--danger); transition: width 0.2s ease, background-color 0.2s ease;"></div>
                        </div>
                        <span id="reset-password-label" aria-live="polite" style="min-width: 4.5rem; text-align: right; font-weight: 600;">—</span>
                    </div>
                    <ul id="reset-password-checks" style="list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 0.15rem 0.75rem;">
                        <li data-rule="length" style="color: var(--gray-500);"><i class="fas fa-circle" style="font-size: 0.4rem; vertical-align: middle; margin-right: 0.3rem;"></i>10+ characters</li>
                        <li data-rule="lower"  style="color: var(--gray-500);"><i class="fas fa-circle" style="font-size: 0.4rem; vertical-align: middle; margin-right: 0.3rem;"></i>Lowercase letter</li>
                        <li data-rule="upper"  style="color: var(--gray-500);"><i class="fas fa-circle" style="font-size: 0.4rem; vertical-align: middle; margin-right: 0.3rem;"></i>Uppercase letter</li>
                        <li data-rule="digit"  style="color: var(--gray-500);"><i class="fas fa-circle" style="font-size: 0.4rem; vertical-align: middle; margin-right: 0.3rem;"></i>Number</li>
                        <li data-rule="symbol" style="color: var(--gray-500);"><i class="fas fa-circle" style="font-size: 0.4rem; vertical-align: middle; margin-right: 0.3rem;"></i>Symbol (!@#$…)</li>
                    </ul>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card" style="margin-top: 1.25rem;">
    <div class="card-header"><i class="fas fa-clock-rotate-left"></i> Recent Activity (last 20)</div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($activity)): ?>
            <div class="empty-state">No recent activity for this user.</div>
        <?php else: ?>
            <table class="table-mini">
                <thead><tr><th>When</th><th>Action</th><th>Module</th><th>Entity</th><th>IP</th></tr></thead>
                <tbody>
                <?php foreach ($activity as $row): ?>
                    <tr>
                        <td style="white-space: nowrap; color: var(--gray-600); font-size: 0.75rem;"><?= esc(date('M d, Y h:i A', strtotime($row['created_at']))) ?></td>
                        <td><span class="badge badge-blue"><?= esc($row['action']) ?></span></td>
                        <td><?= esc($row['module']) ?></td>
                        <td style="font-size: 0.75rem; color: var(--gray-500);"><?= esc($row['entity_type']) ?> #<?= esc($row['entity_id']) ?></td>
                        <td style="font-family: monospace; font-size: 0.75rem; color: var(--gray-500);"><?= esc($row['ip_address']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php if ((int) $user['id'] !== (int) session()->get('user_id')): ?>
<!-- Delete user dialog. Rendered permanently in the DOM so the button
     click handler can find it on every page load. -->
<div id="delete-user-dialog" class="syn-dialog syn-dialog--wide syn-dialog--danger" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="delete-dialog-title">
    <div class="syn-dialog-header">
        <h2 id="delete-dialog-title" class="syn-dialog-title">
            <i class="fas fa-user-xmark" style="color: var(--danger); margin-right: 0.4rem;"></i>
            Delete or anonymize user
        </h2>
        <p class="syn-dialog-desc">
            <?= esc(trim($user['first_name'] . ' ' . $user['last_name'])) ?>
            &nbsp;<code style="font-size: 0.85rem; color: var(--gray-600);"><?= esc($user['email']) ?></code>
        </p>
    </div>
    <div class="syn-dialog-body">
        <p style="font-size: 0.85rem; color: var(--gray-700); margin: 0 0 1rem;">
            Choose how this user should be handled. This action is logged to the audit trail.
        </p>

        <fieldset style="border: 0; padding: 0; margin: 0 0 1rem;">
            <legend style="font-size: 0.8rem; font-weight: 600; padding: 0;">Action</legend>

            <label style="display: flex; gap: 0.6rem; padding: 0.6rem; border: 1px solid var(--gray-200); border-radius: 0.5rem; margin-bottom: 0.5rem; cursor: pointer;">
                <input type="radio" name="delete-mode" value="soft" checked>
                <span>
                    <strong style="display: block;">Deactivate (recommended)</strong>
                    <span style="font-size: 0.78rem; color: var(--gray-600);">Sets <code>is_active=false</code>. Reversible. All history preserved.</span>
                </span>
            </label>
            <label style="display: flex; gap: 0.6rem; padding: 0.6rem; border: 1px solid var(--gray-200); border-radius: 0.5rem; margin-bottom: 0.5rem; cursor: pointer;">
                <input type="radio" name="delete-mode" value="anonymize">
                <span>
                    <strong style="display: block;">Anonymize</strong>
                    <span style="font-size: 0.78rem; color: var(--gray-600);">Scrubs PII (name, email, phone, 2FA, password). Row kept so clinical and audit history remain attributable. Cannot be undone.</span>
                </span>
            </label>
            <label style="display: flex; gap: 0.6rem; padding: 0.6rem; border: 1px solid var(--gray-300); border-radius: 0.5rem; cursor: pointer; background: #FEF2F2;">
                <input type="radio" name="delete-mode" value="hard">
                <span>
                    <strong style="display: block; color: var(--danger);">Hard delete (permanent)</strong>
                    <span style="font-size: 0.78rem; color: var(--gray-600);">Removes the row entirely. Only allowed if the user has zero clinical / inventory / outreach records. Requires typing the email below.</span>
                </span>
            </label>
        </fieldset>

        <form id="delete-user-form"
              method="post"
              action="<?= base_url('admin/users/delete/' . $user['id']) ?>"
              style="margin: 0;">
            <?= csrf_field() ?>
            <input type="hidden" name="mode" id="delete-mode-input" value="soft">

            <div id="delete-hard-confirm" hidden style="margin-bottom: 1rem; padding: 0.75rem; background: #FEF2F2; border: 1px solid #FECACA; border-radius: 0.4rem;">
                <label for="delete-confirm-email" style="display: block; font-size: 0.78rem; font-weight: 600; margin-bottom: 0.25rem;">
                    Type <code><?= esc($user['email']) ?></code> to confirm permanent deletion:
                </label>
                <input type="text" id="delete-confirm-email" name="confirm_email" autocomplete="off"
                       placeholder="<?= esc($user['email']) ?>"
                       style="width: 100%; padding: 0.45rem 0.6rem; border: 1px solid var(--danger); border-radius: 0.4rem; font-family: monospace;">
            </div>

            <div id="delete-error" hidden role="alert" style="margin-bottom: 1rem; padding: 0.6rem 0.8rem; background: #FEF2F2; border: 1px solid #FECACA; border-radius: 0.4rem; color: #991B1B; font-size: 0.85rem;"></div>
            <div id="delete-success" hidden role="status" style="margin-bottom: 1rem; padding: 0.6rem 0.8rem; background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 0.4rem; color: #065F46; font-size: 0.85rem;"></div>
        </form>
    </div>
    <div class="syn-dialog-actions">
        <button type="button" data-synapse-close class="syn-btn syn-btn--secondary">
            <i class="fas fa-xmark"></i> Cancel
        </button>
        <button type="button" id="delete-confirm-btn" class="syn-btn syn-btn--primary" style="background: var(--danger);">
            <i class="fas fa-check"></i> Confirm
        </button>
    </div>
</div>

<script>
(function () {
    'use strict';
    var btn        = document.getElementById('delete-user-btn');
    var dialog     = document.getElementById('delete-user-dialog');
    var modeInput  = document.getElementById('delete-mode-input');
    var hardBox    = document.getElementById('delete-hard-confirm');
    var errorBox   = document.getElementById('delete-error');
    var successBox = document.getElementById('delete-success');
    var confirmBtn = document.getElementById('delete-confirm-btn');
    var form       = document.getElementById('delete-user-form');
    var csrf       = form ? form.querySelector('input[name="csrf_test_name"]') : null;

    if (!btn || !dialog || !form) return;

    function openDialog() {
        errorBox.hidden = true;
        successBox.hidden = true;
        hardBox.hidden = true;
        document.getElementById('delete-confirm-email').value = '';
        var softRadio = document.querySelector('input[name="delete-mode"][value="soft"]');
        if (softRadio) softRadio.checked = true;
        modeInput.value = 'soft';
        confirmBtn.disabled = false;
        updateConfirmLabel();

        if (!document.getElementById('delete-user-backdrop')) {
            var backdrop = document.createElement('div');
            backdrop.id = 'delete-user-backdrop';
            backdrop.className = 'syn-dialog-backdrop is-open';
            backdrop.setAttribute('role', 'dialog');
            backdrop.setAttribute('aria-modal', 'true');
            backdrop.addEventListener('click', function (e) {
                if (e.target === backdrop) closeDialog();
            });
            document.body.appendChild(backdrop);
            backdrop.appendChild(dialog);
        }
        dialog.style.display = '';
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', escListener);
        setTimeout(function () { confirmBtn.focus(); }, 50);
    }

    function closeDialog() {
        dialog.style.display = 'none';
        var backdrop = document.getElementById('delete-user-backdrop');
        if (backdrop) backdrop.remove();
        document.body.style.overflow = '';
        document.removeEventListener('keydown', escListener);
    }

    function escListener(e) {
        if (e.key === 'Escape') closeDialog();
    }

    function updateConfirmLabel() {
        var m = modeInput.value;
        if (m === 'soft') {
            confirmBtn.innerHTML = '<i class="fas fa-ban"></i> Deactivate';
            confirmBtn.style.background = 'var(--warning)';
        } else if (m === 'anonymize') {
            confirmBtn.innerHTML = '<i class="fas fa-user-secret"></i> Anonymize';
            confirmBtn.style.background = 'var(--gray-700)';
        } else {
            confirmBtn.innerHTML = '<i class="fas fa-trash"></i> Delete permanently';
            confirmBtn.style.background = 'var(--danger)';
        }
    }

    btn.addEventListener('click', openDialog);
    var closeBtn = dialog.querySelector('[data-synapse-close]');
    if (closeBtn) closeBtn.addEventListener('click', closeDialog);

    document.querySelectorAll('input[name="delete-mode"]').forEach(function (r) {
        r.addEventListener('change', function () {
            modeInput.value = r.value;
            hardBox.hidden = (r.value !== 'hard');
            errorBox.hidden = true;
            updateConfirmLabel();
        });
    });

    confirmBtn.addEventListener('click', function () {
        errorBox.hidden = true;
        successBox.hidden = true;

        var mode = modeInput.value;
        var fd = new FormData();
        fd.append('mode', mode);
        if (mode === 'hard') {
            var confirmEmail = document.getElementById('delete-confirm-email').value.trim();
            if (confirmEmail === '') {
                errorBox.textContent = "Please type the user's email to confirm permanent deletion.";
                errorBox.hidden = false;
                return;
            }
            fd.append('confirm_email', confirmEmail);
        }
        if (csrf) fd.append(csrf.name, csrf.value);

        confirmBtn.disabled = true;
        var originalHtml = confirmBtn.innerHTML;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Working...';

        fetch(form.action, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (res) {
            return res.json().then(function (j) { return { ok: res.ok, status: res.status, body: j }; });
        })
        .then(function (r) {
            if (r.ok) {
                successBox.textContent = r.body.message;
                successBox.hidden = false;
                confirmBtn.innerHTML = '<i class="fas fa-check"></i> Done';
                setTimeout(function () {
                    window.location.href = '<?= base_url('admin/users') ?>';
                }, 1500);
            } else {
                errorBox.textContent = r.body.message || ('Error ' + r.status);
                errorBox.hidden = false;
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalHtml;
                updateConfirmLabel();
                // Refresh the CSRF token so the next attempt doesn't fail
                // with 403 (CI4 regenerates it on every POST).
                if (r.body && r.body.csrf && r.body.csrf.name) {
                    var existing = form.querySelector('input[name="' + r.body.csrf.name + '"]');
                    if (existing) existing.value = r.body.csrf.value;
                    if (csrf && csrf.name === r.body.csrf.name) {
                        csrf.value = r.body.csrf.value;
                    }
                }
            }
        })
        .catch(function (err) {
            errorBox.textContent = 'Network error: ' + (err.message || err);
            errorBox.hidden = false;
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalHtml;
            updateConfirmLabel();
        });
    });

    // Reset-password strength meter (also lives here because show.php
    // is its natural home).
    var pwdInput  = document.getElementById('reset-password-input');
    var pwdToggle = document.getElementById('reset-password-toggle');
    var pwdIcon   = pwdToggle ? pwdToggle.querySelector('i') : null;
    var pwdBar    = document.getElementById('reset-password-bar');
    var pwdLabel  = document.getElementById('reset-password-label');
    var pwdChecks = document.querySelectorAll('#reset-password-checks li');

    if (pwdInput && pwdToggle && pwdBar && pwdLabel) {
        pwdToggle.addEventListener('click', function () {
            var showing = pwdInput.type === 'text';
            pwdInput.type = showing ? 'password' : 'text';
            pwdToggle.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            pwdToggle.setAttribute('aria-pressed', showing ? 'false' : 'true');
            pwdToggle.title = showing ? 'Show password' : 'Hide password';
            if (pwdIcon) {
                pwdIcon.classList.toggle('fa-eye', showing);
                pwdIcon.classList.toggle('fa-eye-slash', !showing);
            }
        });

        var LEVELS = [
            { score: 0, label: 'Too short', color: 'var(--danger)',  width: '0%' },
            { score: 1, label: 'Weak',      color: 'var(--danger)',  width: '20%' },
            { score: 2, label: 'Fair',      color: 'var(--warning)', width: '45%' },
            { score: 3, label: 'Good',      color: 'var(--info)',    width: '70%' },
            { score: 4, label: 'Strong',    color: 'var(--success)', width: '100%' }
        ];

        function evaluate(pw) {
            var rules = {
                length: pw.length >= 10,
                lower:  /[a-z]/.test(pw),
                upper:  /[A-Z]/.test(pw),
                digit:  /\d/.test(pw),
                symbol: /[^A-Za-z0-9]/.test(pw)
            };
            pwdChecks.forEach(function (li) {
                var rule = li.getAttribute('data-rule');
                var ok   = rules[rule];
                var i    = li.querySelector('i');
                li.style.color = ok ? 'var(--success)' : 'var(--gray-500)';
                if (i) {
                    i.className = ok ? 'fas fa-check' : 'fas fa-circle';
                    i.style.fontSize = ok ? '0.7rem' : '0.4rem';
                    i.style.marginRight = '0.3rem';
                }
            });
            if (!rules.length) {
                pwdBar.style.width = LEVELS[0].width;
                pwdBar.style.backgroundColor = LEVELS[0].color;
                pwdLabel.textContent = pw.length === 0 ? '\u2014' : LEVELS[0].label;
                return;
            }
            var passed = Object.values(rules).filter(Boolean).length;
            var lvl = LEVELS[Math.min(passed, LEVELS.length - 1)];
            pwdBar.style.width = lvl.width;
            pwdBar.style.backgroundColor = lvl.color;
            pwdLabel.textContent = lvl.label;
            pwdLabel.style.color = lvl.color;
        }
        pwdInput.addEventListener('input', function () { evaluate(pwdInput.value); });
        evaluate('');
    }
})();
</script>
<?php endif; ?>

<style>
    /* .table-mini and .kv-table are now centralized in synapse-ui.css */
    .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 99px; font-size: 0.7rem; font-weight: 600; }
    .badge-blue { background: var(--primary-50); color: var(--primary-700); }
    .badge-green { background: rgba(16,185,129,0.1); color: #15803D; }
    .badge-gray { background: var(--gray-100); color: var(--gray-700); }
    .empty-state { padding: 2.5rem 1rem; text-align: center; color: var(--gray-500); }
</style>
<?= $this->endSection() ?>