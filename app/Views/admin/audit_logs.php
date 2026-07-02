<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="syn-page-summary">
    <p class="syn-page-summary-text">
        <?= number_format($total) ?> record<?= $total === 1 ? '' : 's' ?> · page <?= $page ?> of <?= max(1, $totalPages) ?>
    </p>
    <div class="syn-page-summary-actions"></div>
</div>

<?php if (! empty($integrity) && ! $integrity['intact']): ?>
    <div class="alert alert-danger">
        <i class="fas fa-triangle-exclamation"></i>
        <div>
            <strong>Chain integrity failure.</strong>
            <?= number_format($integrity['error_count']) ?> tamper event(s) detected in the last <?= number_format($integrity['checked']) ?> entries.
            <a href="<?= base_url('admin/audit/verify') ?>" style="color: white; text-decoration: underline;">Investigate →</a>
        </div>
    </div>
<?php endif; ?>

<div class="card syn-search-card">
    <div class="card-body">
        <form method="get" action="<?= base_url('admin/audit') ?>"
              data-synapse-search
              class="syn-search-bar"
              autocomplete="off">
            <div class="syn-search-row">
                <div class="syn-search-input-wrap">
                    <i class="fas fa-search syn-search-icon" aria-hidden="true"></i>
                    <label for="auditSearch" class="sr-only">Search audit logs</label>
                    <input type="search" id="auditSearch" name="q" value="<?= esc($q) ?>"
                           placeholder="Search entity, IP, user…"
                           autocomplete="off" spellcheck="false"
                           data-synapse-search-trigger>
                </div>
            </div>
            <div class="syn-search-actions">
                <?php if ($q !== '' || $module !== '' || $action !== '' || $userId > 0 || $start !== '' || $end !== ''): ?>
                    <a href="<?= base_url('admin/audit') ?>" class="syn-search-chip" aria-label="Clear all filters">
                        <i class="fas fa-xmark"></i> Clear
                    </a>
                <?php endif; ?>
                <a href="<?= base_url('admin/audit/verify') ?>" class="syn-btn syn-btn--primary-ghost">
                    <i class="fas fa-fingerprint"></i> Verify Chain
                </a>
                <a href="<?= base_url('admin/audit/export') ?>?<?= http_build_query(array_filter([
                    'q' => $q, 'module' => $module, 'action' => $action,
                    'user_id' => $userId > 0 ? $userId : null,
                    'start' => $start, 'end' => $end,
                ])) ?>" class="syn-btn syn-btn--success">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
            </div>
            <div class="syn-search-filters">
                <div class="syn-search-field syn-search-field--select">
                    <label for="moduleFilter" class="sr-only">Module</label>
                    <select id="moduleFilter" name="module" data-synapse-dropdown autocomplete="off">
                        <option value="">All modules</option>
                        <?php foreach ($modules as $m): ?>
                            <option value="<?= esc($m) ?>" <?= $m === $module ? 'selected' : '' ?>><?= esc($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="syn-search-field syn-search-field--select">
                    <label for="actionFilter" class="sr-only">Action</label>
                    <select id="actionFilter" name="action" data-synapse-dropdown autocomplete="off">
                        <option value="">All actions</option>
                        <?php foreach ($actions as $a): ?>
                            <option value="<?= esc($a) ?>" <?= $a === $action ? 'selected' : '' ?>><?= esc($a) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="syn-search-field">
                    <label for="userIdFilter" class="sr-only">User ID</label>
                    <input type="number" id="userIdFilter" name="user_id" value="<?= $userId > 0 ? esc($userId) : '' ?>" min="1" placeholder="User ID" autocomplete="off">
                </div>
                <div class="syn-search-field syn-search-field--date">
                    <label for="startFilter" class="sr-only">From</label>
                    <input type="text" id="startFilter" class="syn-datepicker" name="start" value="<?= esc($start) ?>" placeholder="From date" autocomplete="off" spellcheck="false">
                </div>
                <div class="syn-search-field syn-search-field--date">
                    <label for="endFilter" class="sr-only">To</label>
                    <input type="text" id="endFilter" class="syn-datepicker" name="end" value="<?= esc($end) ?>" placeholder="To date" autocomplete="off" spellcheck="false">
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <i class="fas fa-shield-halved" style="color: var(--primary-500); margin-right: 0.5rem;"></i> System Audit Logs
        </div>
        <div style="font-size: 0.8rem; font-weight: 400; color: var(--gray-500);">
            Total Records: <?= number_format($total) ?>
        </div>
    </div>
    
    <div class="card-body" style="padding: 0;">
        <?php if ($q !== '' || $module !== '' || $action !== '' || $userId > 0 || $start !== '' || $end !== ''): ?>
            <div class="syn-search-result-row" style="padding: 0.75rem 1rem; background: var(--primary-50); border-bottom: 1px solid var(--primary-100);">
                <span class="syn-search-result-count">
                    <i class="fas fa-search"></i>
                    <strong><?= number_format($total) ?></strong>
                    <?= $total === 1 ? 'audit entry' : 'audit entries' ?>
                    <?php if ($q !== ''): ?>for &ldquo;<?= esc($q) ?>&rdquo;<?php endif; ?>
                </span>
                <a href="<?= base_url('admin/audit') ?>" class="syn-search-clear-link">
                    <i class="fas fa-xmark"></i> Clear filters
                </a>
            </div>
        <?php endif; ?>
        <div style="overflow-x: auto;">
            <table class="table" style="margin: 0; font-size: 0.85rem; width: 100%;">
                <colgroup>
                    <col style="width: 18%;">
                    <col style="width: 16%;">
                    <col style="width: 12%;">
                    <col style="width: 24%;">
                    <col style="width: 14%;">
                    <col style="width: 16%;">
                </colgroup>
                <thead>
                    <tr>
                        <th scope="col">Timestamp</th>
                        <th scope="col">User</th>
                        <th scope="col">Action</th>
                        <th scope="col">Module / Entity</th>
                        <th scope="col">IP Address</th>
                        <th scope="col">Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($logs)): ?>
                        <tr><td colspan="6" class="empty-state">
                            <i class="fas fa-shield-halved" aria-hidden="true" style="font-size: 1.5rem; color: var(--gray-400); display: block; margin-bottom: 0.5rem;"></i>
                            <div style="color: var(--gray-600); font-weight: 500;">No audit logs match your filters.</div>
                            <?php if ($q !== '' || $module !== '' || $action !== '' || $userId > 0 || $start !== '' || $end !== ''): ?>
                                <a href="<?= base_url('admin/audit') ?>" style="display: inline-block; margin-top: 0.75rem; font-size: 0.8rem; color: var(--primary-600);">
                                    <i class="fas fa-xmark"></i> Clear filters
                                </a>
                            <?php endif; ?>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach($logs as $log): ?>
                            <tr>
                                <td class="font-mono" style="white-space: nowrap; color: var(--gray-600); font-size: 0.78rem;">
                                    <?= date('M d, Y h:i A', strtotime($log['created_at'])) ?>
                                </td>
                                <td style="font-weight: 500;">
                                    <?php if ($log['user_id']): ?>
                                        <?= search_highlight(trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')), $q) ?>
                                    <?php else: ?>
                                        <span style="color: var(--gray-400);">System / Guest</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $color = 'var(--gray-600)';
                                        $bg = 'var(--gray-100)';
                                        if ($log['action'] === 'create') { $color = 'var(--success)'; $bg = 'rgba(16, 185, 129, 0.1)'; }
                                        if ($log['action'] === 'update') { $color = 'var(--info)'; $bg = 'rgba(59, 130, 246, 0.1)'; }
                                        if ($log['action'] === 'delete') { $color = 'var(--danger)'; $bg = 'rgba(239, 68, 68, 0.1)'; }
                                        if ($log['action'] === 'login') { $color = 'var(--primary-600)'; $bg = 'var(--primary-50)'; }
                                    ?>
                                    <span style="padding: 0.25rem 0.55rem; border-radius: 99px; font-size: 0.7rem; font-weight: 600; color: <?= $color ?>; background: <?= $bg ?>; text-transform: uppercase;">
                                        <?= esc($log['action']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--gray-800); text-transform: capitalize;"><?= search_highlight(str_replace('_', ' ', $log['module']), $q) ?></div>
                                    <div class="font-mono" style="font-size: 0.7rem; color: var(--gray-500);"><?= search_highlight($log['entity_type'], $q) ?> #<?= (int) $log['entity_id'] ?></div>
                                </td>
                                <td class="font-mono" style="color: var(--gray-500); font-size: 0.78rem; word-break: break-all;">
                                    <?= search_highlight($log['ip_address'], $q) ?>
                                </td>
                                <td>
                                    <?php if ($log['old_values'] || $log['new_values']): ?>
                                        <button onclick="viewDetails(<?= htmlspecialchars(json_encode([
                                            'old' => json_decode($log['old_values']),
                                            'new' => json_decode($log['new_values'])
                                        ])) ?>)" style="background: none; border: 1px solid var(--gray-300); padding: 0.35rem 0.75rem; border-radius: 0.375rem; font-size: 0.75rem; cursor: pointer; color: var(--gray-700);">
                                            <i class="fas fa-eye"></i> View Data
                                        </button>
                                    <?php else: ?>
                                        <span style="color: var(--gray-400); font-size: 0.75rem;">No data</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <?= pagination_links([
            'current'  => (int) $page,
            'total'    => (int) $totalPages,
            'perPage'  => 50,
            'totalRec' => (int) $total,
        ], '/admin/audit', array_filter([
            'q'       => $q       ?? '',
            'module'  => $module  ?? '',
            'action'  => $action  ?? '',
            'user_id' => $userId  ?? 0,
            'start'   => $start   ?? '',
            'end'     => $end     ?? '',
        ]), [25, 50, 100, 200]) ?>
    <?php endif; ?>
    </div>
</div>

<!-- Modal for JSON Data -->
<div id="dataModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; width: 90%; max-width: 600px; border-radius: 0.5rem; overflow: hidden; box-shadow: var(--shadow-lg);">
        <div style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center; background: var(--gray-50);">
            <h3 style="margin: 0; font-size: 1rem;">Payload Data</h3>
            <button onclick="document.getElementById('dataModal').style.display='none'" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--gray-500);">&times;</button>
        </div>
        <div style="padding: 1.5rem; max-height: 60vh; overflow-y: auto;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <h4 style="font-size: 0.8rem; color: var(--danger); margin-bottom: 0.5rem;">Old Values</h4>
                    <pre id="oldData" style="background: var(--gray-100); padding: 1rem; border-radius: 0.375rem; font-size: 0.75rem; overflow-x: auto; margin: 0; color: var(--gray-800);"></pre>
                </div>
                <div>
                    <h4 style="font-size: 0.8rem; color: var(--success); margin-bottom: 0.5rem;">New Values</h4>
                    <pre id="newData" style="background: var(--gray-100); padding: 1rem; border-radius: 0.375rem; font-size: 0.75rem; overflow-x: auto; margin: 0; color: var(--gray-800);"></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewDetails(data) {
    document.getElementById('oldData').textContent = data.old ? JSON.stringify(data.old, null, 2) : 'null';
    document.getElementById('newData').textContent = data.new ? JSON.stringify(data.new, null, 2) : 'null';
    document.getElementById('dataModal').style.display = 'flex';
}
</script>
<?= $this->endSection() ?>
