<?= $this->extend('layouts/main') ?>

<?php
    /**
     * Sortable headers helper.
     * Renders an <a> around the header label that flips direction on
     * re-click and preserves the active filters via query string.
     */
    $sortHeader = function (string $key, string $label, string $align = 'left') use ($sortKey, $sortDir, $q, $roleName, $status) {
        $isActive = ($sortKey === $key);
        $nextDir  = ($isActive && $sortDir === 'ASC') ? 'desc' : 'asc';
        $params   = array_filter([
            'sort'  => $key,
            'dir'   => $nextDir,
            'q'     => $q,
            'role'  => $roleName !== '' ? $roleName : null,
            'status'=> $status !== 'all' ? $status : null,
        ]);
        $href     = base_url('admin/users') . '?' . http_build_query($params);
        $caret    = $isActive
            ? '<i class="fas fa-caret-' . ($sortDir === 'ASC' ? 'up' : 'down') . '" style="margin-left: 0.25rem; font-size: 0.7rem;"></i>'
            : '<i class="fas fa-sort" style="margin-left: 0.25rem; font-size: 0.65rem; opacity: 0.4;"></i>';
        return '<a href="' . esc($href) . '" '
             . 'style="color: inherit; text-decoration: none; white-space: nowrap;" '
             . 'aria-sort="' . ($isActive ? ($sortDir === 'ASC' ? 'ascending' : 'descending') : 'none') . '">'
             . esc($label) . $caret
             . '</a>';
    };
?>

<?= $this->section('content') ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
    <div>
        <p style="color: var(--gray-600); margin: 0; font-size: 0.875rem;">
            <?= number_format($total) ?> user<?= $total === 1 ? '' : 's' ?> total · page <?= $page ?> of <?= max(1, $totalPages) ?>
        </p>
    </div>
    <a href="<?= base_url('admin/users/create') ?>"
       data-synapse-form-link
       data-dialog-title="New User"
       data-dialog-icon="fas fa-user-plus"
       data-dialog-width
       style="padding: 0.55rem 1.1rem; background: var(--primary-600); color: white; text-decoration: none; border-radius: 0.5rem; font-weight: 600; font-size: 0.85rem;">
        <i class="fas fa-user-plus"></i> New User
    </a>
</div>

<div class="card syn-search-card">
    <div class="card-body">
        <form method="get" action="<?= base_url('admin/users') ?>"
              data-synapse-search
              data-synapse-state='<?= esc(json_encode([
                  'sort' => $sortKey,
                  'dir'  => $sortDir,
              ])) ?>'
              class="syn-search-bar"
              autocomplete="off">
            <div class="syn-search-row">
                <div class="syn-search-input-wrap">
                    <i class="fas fa-search syn-search-icon" aria-hidden="true"></i>
                    <label for="userSearch" class="sr-only">Search users</label>
                    <input type="search" id="userSearch" name="q" value="<?= esc($q) ?>"
                           placeholder="Search email or name…"
                           autocomplete="off" spellcheck="false"
                           data-synapse-search-trigger>
                </div>
            </div>
            <div class="syn-search-actions">
                <?php if ($q !== '' || $roleName !== '' || $status !== 'all' || $sortKey !== 'created' || $sortDir !== 'DESC'): ?>
                    <a href="<?= base_url('admin/users') ?>" class="syn-search-chip" aria-label="Clear all filters">
                        <i class="fas fa-xmark"></i> Clear
                    </a>
                <?php endif; ?>
                <a href="<?= base_url('admin/users/create') ?>"
                   class="syn-btn syn-btn--primary"
                   data-synapse-form-link
                   data-dialog-title="New User"
                   data-dialog-icon="fas fa-user-plus"
                   data-dialog-width>
                    <i class="fas fa-plus"></i> New User
                </a>
            </div>
            <div class="syn-search-filters">
                <div class="syn-search-field syn-search-field--select">
                    <label for="roleFilter" class="sr-only">Role</label>
                    <select id="roleFilter" name="role" data-synapse-dropdown>
                        <option value="">All roles</option>
                        <?php foreach ($allRoles as $r): ?>
                            <option value="<?= esc($r['name']) ?>" <?= $r['name'] === $roleName ? 'selected' : '' ?>>
                                <?= esc($r['display_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="syn-search-field syn-search-field--select">
                    <label for="statusFilter" class="sr-only">Status</label>
                    <select id="statusFilter" name="status" data-synapse-dropdown>
                        <option value="all"      <?= $status === 'all'      ? 'selected' : '' ?>>All status</option>
                        <option value="active"   <?= $status === 'active'   ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding: 0;">
        <form id="bulk-action-form" method="post" action="<?= base_url('admin/users/bulk-toggle') ?>" data-bulk-form>
            <?= csrf_field() ?>
            <!-- Bulk action bar: hidden until at least one row is selected -->
            <div id="bulk-action-bar"
                 role="region"
                 aria-label="Bulk actions"
                 style="display: none; padding: 0.65rem 1rem; background: var(--primary-50); border-bottom: 1px solid var(--gray-200); align-items: center; gap: 0.75rem; font-size: 0.85rem;">
                <span aria-live="polite" style="color: var(--primary-700); font-weight: 600;">
                    <span id="bulk-action-count">0</span> selected
                </span>
                <!-- Hidden field populated by JS before submission so the
                     server knows which bulk action was chosen (no-JS fallback:
                     buttons carry name="action" via formaction). -->
                <input type="hidden" name="action" id="bulk-action-value" value="">
                <button type="submit"
                        data-bulk-action="activate"
                        data-synapse-confirm
                        data-synapse-confirm-title="Activate selected users?"
                        data-synapse-confirm-body="The selected accounts will be re-enabled and able to sign in."
                        data-synapse-confirm-text="Activate"
                        style="margin-left: auto; padding: 0.35rem 0.8rem; background: var(--success); color: white; border: none; border-radius: 0.4rem; font-weight: 600; cursor: pointer; font-size: 0.8rem;">
                    <i class="fas fa-check"></i> Activate
                </button>
                <button type="submit"
                        data-bulk-action="deactivate"
                        data-synapse-confirm
                        data-synapse-confirm-title="Deactivate selected users?"
                        data-synapse-confirm-body="The selected users will be unable to sign in until reactivated. Their data and roles will be preserved."
                        data-synapse-confirm-text="Deactivate"
                        data-synapse-confirm-danger
                        style="padding: 0.35rem 0.8rem; background: var(--danger); color: white; border: none; border-radius: 0.4rem; font-weight: 600; cursor: pointer; font-size: 0.8rem;">
                    <i class="fas fa-ban"></i> Deactivate
                </button>
                <button type="button"
                        id="bulk-delete-btn"
                        style="padding: 0.35rem 0.8rem; background: var(--gray-900); color: white; border: none; border-radius: 0.4rem; font-weight: 600; cursor: pointer; font-size: 0.8rem;">
                    <i class="fas fa-trash"></i> Delete…
                </button>
                <button type="button" id="bulk-clear" aria-label="Clear selection" style="padding: 0.35rem 0.6rem; background: transparent; color: var(--gray-600); border: 1px solid var(--gray-300); border-radius: 0.4rem; cursor: pointer; font-size: 0.8rem;">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <div style="overflow-x: auto;">
                <?php if ($q !== ''): ?>
                    <div class="syn-search-result-row" style="padding: 0.75rem 1rem; background: var(--primary-50); border-bottom: 1px solid var(--primary-100);">
                        <span class="syn-search-result-count">
                            <i class="fas fa-search"></i>
                            <strong><?= number_format($total) ?></strong>
                            <?= $total === 1 ? 'match' : 'matches' ?>
                            for &ldquo;<?= esc($q) ?>&rdquo;
                        </span>
                        <a href="<?= base_url('admin/users') ?>" class="syn-search-clear-link">
                            <i class="fas fa-xmark"></i> Clear filters
                        </a>
                    </div>
                <?php endif; ?>
                <table class="table-mini">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 2.5rem; padding-left: 1rem;">
                                <input type="checkbox"
                                       id="bulk-select-all"
                                       aria-label="Select all users on this page"
                                       style="cursor: pointer;">
                            </th>
                            <th scope="col"><?= $sortHeader('name', 'User') ?></th>
                            <th scope="col"><?= $sortHeader('email', 'Email') ?></th>
                            <th scope="col">Roles</th>
                            <th scope="col">Status</th>
                            <th scope="col"><?= $sortHeader('last_login', 'Last Login') ?></th>
                            <th scope="col"><?= $sortHeader('created', 'Created') ?></th>
                            <th scope="col" style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="8" class="empty-state">
                            <i class="fas fa-user-slash" aria-hidden="true" style="font-size: 1.5rem; color: var(--gray-400); display: block; margin-bottom: 0.5rem;"></i>
                            <div style="color: var(--gray-600); font-weight: 500;">No users match your filters.</div>
                            <?php if ($q !== '' || $roleName !== '' || $status !== 'all'): ?>
                                <a href="<?= base_url('admin/users') ?>" style="display: inline-block; margin-top: 0.75rem; font-size: 0.8rem; color: var(--primary-600);">
                                    <i class="fas fa-xmark"></i> Clear filters
                                </a>
                            <?php endif; ?>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td style="padding-left: 1rem;">
                                    <input type="checkbox"
                                           class="bulk-row"
                                           name="ids[]"
                                           value="<?= (int) $u['id'] ?>"
                                           aria-label="Select <?= esc(trim($u['first_name'] . ' ' . $u['last_name'])) ?>"
                                           style="cursor: pointer;">
                                </td>
                                <td>
                                    <strong><?= search_highlight(trim($u['first_name'] . ' ' . ($u['middle_name'] ?? '') . ' ' . $u['last_name']), $q) ?></strong>
                                </td>
                                <td class="syn-cell-muted" style="font-family: monospace; font-size: 0.75rem;">
                                    <?= search_highlight($u['email'], $q) ?>
                                </td>
                                <td>
                                    <?php if (empty($u['roles'])): ?>
                                        <span class="syn-badge syn-badge--neutral">none</span>
                                    <?php else: ?>
                                        <?php foreach ($u['roles'] as $r): ?>
                                            <span class="syn-badge syn-badge--primary"><?= esc($r['display_name']) ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($u['is_active']): ?>
                                        <span class="syn-badge syn-badge--success">Active</span>
                                    <?php else: ?>
                                        <span class="syn-badge syn-badge--neutral">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="syn-cell-muted" style="font-size: 0.75rem;">
                                    <?= $u['last_login_at'] ? esc(date('M d, Y h:i A', strtotime($u['last_login_at']))) : '<span class="syn-cell-muted" style="font-style: italic;">never</span>' ?>
                                </td>
                                <td class="syn-cell-muted" style="font-size: 0.75rem;">
                                    <?= esc(date('M d, Y h:i A', strtotime($u['created_at']))) ?>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="<?= base_url('admin/users/' . $u['id']) ?>" class="syn-cell-action syn-cell-action--view">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="<?= base_url('admin/users/' . $u['id'] . '/edit') ?>"
                                       class="syn-cell-action syn-cell-action--ghost"
                                       data-synapse-form-link
                                       data-dialog-title="Edit User"
                                       data-dialog-icon="fas fa-user-edit"
                                       data-dialog-width>
                                        <i class="fas fa-pen"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div style="padding: 0 1.25rem;">
                    <?= pagination_links($pager, '/admin/users', array_filter([
                        'q' => $q, 'role' => $roleName, 'status' => $status !== 'all' ? $status : null,
                        'sort' => $sortKey, 'dir' => $sortDir,
                    ]), [10, 25, 50, 100]) ?>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<style>
    /* .table-mini is now centralized in synapse-ui.css (sections 1-9) */
    .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 99px; font-size: 0.7rem; font-weight: 600; margin-right: 0.2rem; }
    .badge-blue { background: var(--primary-50); color: var(--primary-700); }
    .badge-green { background: rgba(16,185,129,0.1); color: #15803D; }
    .badge-gray { background: var(--gray-100); color: var(--gray-700); }
    /* Outline variant — used for "none" / "empty" states so it doesn't
       visually collide with the gray "Inactive" status pill. */
    .badge-outline { background: transparent; color: var(--gray-500); border: 1px dashed var(--gray-300); font-style: italic; }
    .empty-state { padding: 2.5rem 1rem; text-align: center; color: var(--gray-500); }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var form        = document.getElementById('bulk-action-form');
    var selectAll   = document.getElementById('bulk-select-all');
    var rowBoxes    = document.querySelectorAll('.bulk-row');
    var bar         = document.getElementById('bulk-action-bar');
    var countEl     = document.getElementById('bulk-action-count');
    var clearBtn    = document.getElementById('bulk-clear');
    var actionField = document.getElementById('bulk-action-value');
    var csrfField   = form ? form.querySelector('input[name="csrf_test_name"]') : null;

    if (!form || !selectAll || !bar) return;

    // Each click on an action button writes its chosen action into the
    // hidden field BEFORE the synapse-ui.js confirm handler fires
    // form.submit(). Without this, the native POST body has no `action`
    // parameter and the controller rejects it.
    bar.querySelectorAll('button[data-bulk-action]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (actionField) actionField.value = btn.dataset.bulkAction;
        });
    });

    function getChecked() {
        return Array.prototype.filter.call(rowBoxes, function (b) { return b.checked; });
    }

    function updateBar() {
        var checked = getChecked();
        var n = checked.length;
        countEl.textContent = n;
        bar.style.display = n > 0 ? 'flex' : 'none';
        // Update select-all indeterminate + checked state
        selectAll.checked = (n === rowBoxes.length && n > 0);
        selectAll.indeterminate = (n > 0 && n < rowBoxes.length);
    }

    selectAll.addEventListener('change', function () {
        var on = selectAll.checked;
        rowBoxes.forEach(function (b) { b.checked = on; });
        updateBar();
    });

    rowBoxes.forEach(function (b) {
        b.addEventListener('change', updateBar);
    });

    clearBtn.addEventListener('click', function () {
        rowBoxes.forEach(function (b) { b.checked = false; });
        updateBar();
    });

    // Intercept the bulk action buttons: capture which action was clicked,
    // then submit via fetch() so we can show a toast without a full reload.
    // Falling back to native form submit if anything goes wrong.
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var submitter = e.submitter;
        if (!submitter || !submitter.dataset.bulkAction) return;

        var action = submitter.dataset.bulkAction;
        var checked = getChecked();
        if (checked.length === 0) return;

        var fd = new FormData();
        fd.append('action', action);
        checked.forEach(function (b) { fd.append('ids[]', b.value); });
        if (csrfField) fd.append(csrfField.name, csrfField.value);

        // Disable buttons + show spinner while we wait
        var buttons = bar.querySelectorAll('button[type="submit"]');
        buttons.forEach(function (btn) { btn.disabled = true; });

        fetch(form.action, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (res) { return res.json().then(function (j) { return { ok: res.ok, body: j }; }); })
        .then(function (r) {
            if (window.synapse && window.synapse.toast) {
                window.synapse.toast({
                    type: r.ok ? 'success' : 'error',
                    title: r.ok ? 'Done' : 'Could not update',
                    message: r.body.message || 'Operation complete.'
                });
            }
            if (r.ok) {
                // Reload the page so the user sees the new Active/Inactive
                // status without us having to rebuild the table client-side.
                setTimeout(function () { window.location.reload(); }, 400);
            } else {
                buttons.forEach(function (btn) { btn.disabled = false; });
            }
        })
        .catch(function (err) {
            if (window.synapse && window.synapse.toast) {
                window.synapse.toast({ type: 'error', title: 'Network error', message: err.message || String(err) });
            }
            buttons.forEach(function (btn) { btn.disabled = false; });
        });
    });

    // ---------------------------------------------------------------
    // Bulk delete / anonymize dialog
    // ---------------------------------------------------------------
    var delBtn       = document.getElementById('bulk-delete-btn');
    var delDialog    = document.getElementById('bulk-delete-dialog');
    var delCountEl   = document.getElementById('bulk-delete-count');
    var delModeRadios= document.querySelectorAll('input[name="bulk-delete-mode"]');
    var delModeField = document.getElementById('bulk-delete-mode-field');
    var delHardBox   = document.getElementById('bulk-delete-hard-confirm');
    var delPhrase    = document.getElementById('bulk-delete-phrase');
    var delError     = document.getElementById('bulk-delete-error');
    var delSuccess   = document.getElementById('bulk-delete-success');
    var delConfirm   = document.getElementById('bulk-delete-confirm');
    var delCancel    = delDialog ? delDialog.querySelector('[data-synapse-close]') : null;

    if (delBtn && delDialog) {
        function refreshDeleteConfirmLabel() {
            var m = delModeField.value;
            if (m === 'soft') {
                delConfirm.innerHTML = '<i class="fas fa-ban"></i> Deactivate selected';
                delConfirm.style.background = 'var(--warning)';
            } else if (m === 'anonymize') {
                delConfirm.innerHTML = '<i class="fas fa-user-secret"></i> Anonymize selected';
                delConfirm.style.background = 'var(--gray-700)';
            } else {
                delConfirm.innerHTML = '<i class="fas fa-trash"></i> Delete permanently';
                delConfirm.style.background = 'var(--danger)';
            }
        }

        function openDeleteDialog() {
            var checked = getChecked();
            if (checked.length === 0) return;
            delCountEl.textContent = checked.length;
            // Reset UI state
            delError.hidden = true;
            delSuccess.hidden = true;
            delPhrase.value = '';
            // Pick first radio (soft) by default
            document.querySelector('input[name="bulk-delete-mode"][value="soft"]').checked = true;
            delModeField.value = 'soft';
            delHardBox.hidden = true;
            refreshDeleteConfirmLabel();
            delConfirm.disabled = false;
            delConfirm.innerHTML = delConfirm.innerHTML.replace(/Done/g, 'Confirm');

            // Wrap in backdrop
            if (!document.getElementById('bulk-delete-backdrop')) {
                var bd = document.createElement('div');
                bd.id = 'bulk-delete-backdrop';
                bd.className = 'syn-dialog-backdrop is-open';
                bd.setAttribute('role', 'dialog');
                bd.setAttribute('aria-modal', 'true');
                bd.addEventListener('click', function (e) { if (e.target === bd) closeDeleteDialog(); });
                document.body.appendChild(bd);
                bd.appendChild(delDialog);
            }
            delDialog.style.display = '';
            document.body.style.overflow = 'hidden';
            setTimeout(function () { delConfirm.focus(); }, 50);
        }

        function closeDeleteDialog() {
            delDialog.style.display = 'none';
            var bd = document.getElementById('bulk-delete-backdrop');
            if (bd) bd.remove();
            document.body.style.overflow = '';
        }

        delBtn.addEventListener('click', openDeleteDialog);
        if (delCancel) delCancel.addEventListener('click', closeDeleteDialog);

        delModeRadios.forEach(function (r) {
            r.addEventListener('change', function () {
                delModeField.value = r.value;
                delHardBox.hidden = (r.value !== 'hard');
                delError.hidden = true;
                refreshDeleteConfirmLabel();
            });
        });

        delConfirm.addEventListener('click', function () {
            var mode = delModeField.value;
            var checked = getChecked();
            if (checked.length === 0) return;

            var fd = new FormData();
            fd.append('mode', mode);
            checked.forEach(function (b) { fd.append('ids[]', b.value); });
            if (mode === 'hard') {
                if (delPhrase.value.trim().toUpperCase() !== 'DELETE') {
                    delError.textContent = "Type DELETE in capital letters to confirm permanent deletion.";
                    delError.hidden = false;
                    return;
                }
                fd.append('confirm_phrase', delPhrase.value.trim());
            }
            if (csrfField) fd.append(csrfField.name, csrfField.value);

            delConfirm.disabled = true;
            var originalHtml = delConfirm.innerHTML;
            delConfirm.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Working...';

            fetch('<?= base_url('admin/users/bulk-delete') ?>', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (res) { return res.json().then(function (j) { return { ok: res.ok, status: res.status, body: j }; }); })
            .then(function (r) {
                if (r.body && r.body.csrf && r.body.csrf.name && csrfField) {
                    csrfField.value = r.body.csrf.value;
                }
                if (r.ok) {
                    delSuccess.textContent = r.body.message || 'Done.';
                    delSuccess.hidden = false;
                    delConfirm.innerHTML = '<i class="fas fa-check"></i> Done';
                    // Show a more detailed toast so the admin sees skips
                    if (window.synapse && window.synapse.toast) {
                        window.synapse.toast({
                            type: 'success',
                            title: 'Done',
                            message: r.body.message || 'Operation complete.'
                        });
                    }
                    setTimeout(function () { window.location.reload(); }, 800);
                } else {
                    var msg = r.body.message || ('Error ' + r.status);
                    if (r.body.skipped && Object.keys(r.body.skipped).length > 0) {
                        msg += ' Skipped: ' + Object.entries(r.body.skipped).map(function (kv) {
                            return '#' + kv[0] + ' (' + kv[1] + ')';
                        }).join(', ');
                    }
                    delError.textContent = msg;
                    delError.hidden = false;
                    delConfirm.disabled = false;
                    delConfirm.innerHTML = originalHtml;
                    refreshDeleteConfirmLabel();
                    if (window.synapse && window.synapse.toast) {
                        window.synapse.toast({ type: 'error', title: 'Could not complete', message: msg });
                    }
                }
            })
            .catch(function (err) {
                delError.textContent = 'Network error: ' + (err.message || err);
                delError.hidden = false;
                delConfirm.disabled = false;
                delConfirm.innerHTML = originalHtml;
                refreshDeleteConfirmLabel();
            });
        });
    }
});
</script>

<!-- Bulk delete / anonymize dialog (kept in DOM so the button handler
     can always find it). -->
<div id="bulk-delete-dialog" class="syn-dialog syn-dialog--wide syn-dialog--danger" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="bulk-delete-title">
    <div class="syn-dialog-header">
        <h2 id="bulk-delete-title" class="syn-dialog-title">
            <i class="fas fa-trash" style="color: var(--danger); margin-right: 0.4rem;"></i>
            Bulk delete or anonymize
        </h2>
        <p class="syn-dialog-desc">
            <strong id="bulk-delete-count">0</strong> users selected
        </p>
    </div>
    <div class="syn-dialog-body">
        <p style="font-size: 0.85rem; color: var(--gray-700); margin: 0 0 1rem;">
            Choose how the selected users should be handled. All actions are logged to the audit trail.
        </p>

        <fieldset style="border: 0; padding: 0; margin: 0 0 1rem;">
            <legend style="font-size: 0.8rem; font-weight: 600; padding: 0;">Action</legend>

            <label style="display: flex; gap: 0.6rem; padding: 0.6rem; border: 1px solid var(--gray-200); border-radius: 0.5rem; margin-bottom: 0.5rem; cursor: pointer;">
                <input type="radio" name="bulk-delete-mode" value="soft" checked>
                <span>
                    <strong style="display: block;">Deactivate (recommended)</strong>
                    <span style="font-size: 0.78rem; color: var(--gray-600);">Sets <code>is_active=false</code> for everyone selected. Reversible.</span>
                </span>
            </label>
            <label style="display: flex; gap: 0.6rem; padding: 0.6rem; border: 1px solid var(--gray-200); border-radius: 0.5rem; margin-bottom: 0.5rem; cursor: pointer;">
                <input type="radio" name="bulk-delete-mode" value="anonymize">
                <span>
                    <strong style="display: block;">Anonymize</strong>
                    <span style="font-size: 0.78rem; color: var(--gray-600);">Scrubs PII on every selected user. Rows kept for history. Cannot be undone.</span>
                </span>
            </label>
            <label style="display: flex; gap: 0.6rem; padding: 0.6rem; border: 1px solid var(--gray-300); border-radius: 0.5rem; cursor: pointer; background: #FEF2F2;">
                <input type="radio" name="bulk-delete-mode" value="hard">
                <span>
                    <strong style="display: block; color: var(--danger);">Hard delete (permanent)</strong>
                    <span style="font-size: 0.78rem; color: var(--gray-600);">Removes each user row. Users with clinical / inventory / outreach records will be reported as skipped. Requires typing <code>DELETE</code> below.</span>
                </span>
            </label>
        </fieldset>

        <input type="hidden" name="mode" id="bulk-delete-mode-field" value="soft">

        <div id="bulk-delete-hard-confirm" hidden style="margin-bottom: 1rem; padding: 0.75rem; background: #FEF2F2; border: 1px solid #FECACA; border-radius: 0.4rem;">
            <label for="bulk-delete-phrase" style="display: block; font-size: 0.78rem; font-weight: 600; margin-bottom: 0.25rem;">
                Type <code>DELETE</code> (in capital letters) to confirm permanent bulk deletion:
            </label>
            <input type="text" id="bulk-delete-phrase" autocomplete="off" placeholder="DELETE"
                   style="width: 100%; padding: 0.45rem 0.6rem; border: 1px solid var(--danger); border-radius: 0.4rem; font-family: monospace;">
        </div>

        <div id="bulk-delete-error" hidden role="alert" style="margin-bottom: 1rem; padding: 0.6rem 0.8rem; background: #FEF2F2; border: 1px solid #FECACA; border-radius: 0.4rem; color: #991B1B; font-size: 0.85rem;"></div>
        <div id="bulk-delete-success" hidden role="status" style="margin-bottom: 1rem; padding: 0.6rem 0.8rem; background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 0.4rem; color: #065F46; font-size: 0.85rem;"></div>
    </div>
    <div class="syn-dialog-actions">
        <button type="button" data-synapse-close class="syn-btn syn-btn--secondary">
            <i class="fas fa-xmark"></i> Cancel
        </button>
        <button type="button" id="bulk-delete-confirm" class="syn-btn syn-btn--primary" style="background: var(--danger);">
            <i class="fas fa-check"></i> Confirm
        </button>
    </div>
</div>
<?= $this->endSection() ?>