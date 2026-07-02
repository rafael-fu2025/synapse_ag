<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div style="margin-bottom: 1rem;">
    <a href="<?= base_url('admin/audit') ?>" style="color: var(--gray-500); text-decoration: none; font-size: 0.85rem;">
        <i class="fas fa-arrow-left"></i> Back to audit logs
    </a>
</div>

<?php if ($integrity['intact']): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <div>
            <strong>Chain integrity verified.</strong>
            All <?= number_format($integrity['checked']) ?> checked entries are intact. No tampering detected.
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-danger">
        <i class="fas fa-triangle-exclamation"></i>
        <div>
            <strong>Chain integrity FAILED.</strong>
            <?= number_format($integrity['error_count']) ?> tamper event(s) detected across <?= number_format($integrity['checked']) ?> checked entries. This requires immediate investigation.
        </div>
    </div>
<?php endif; ?>

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-header"><i class="fas fa-fingerprint"></i> Verification Summary</div>
    <div class="card-body">
        <table class="kv-table">
            <tr><th>Entries Checked</th><td><strong><?= number_format($integrity['checked']) ?></strong></td></tr>
            <tr><th>Errors Found</th><td>
                <?php if ($integrity['error_count'] > 0): ?>
                    <span style="color: var(--danger); font-weight: 700;"><?= number_format($integrity['error_count']) ?></span>
                <?php else: ?>
                    <span style="color: var(--success); font-weight: 700;">0</span>
                <?php endif; ?>
            </td></tr>
            <tr><th>Algorithm</th><td>SHA-256 chained hash (each entry's hash incorporates the previous entry's hash + canonical JSON content)</td></tr>
        </table>
        <p style="margin-top: 1rem; color: var(--gray-600); font-size: 0.85rem;">
            <i class="fas fa-circle-info"></i>
            This check verifies both the <strong>chain links</strong> (each entry correctly references the previous entry's hash) and the <strong>content integrity</strong> (re-hashing each entry's canonical JSON must reproduce the stored hash).
        </p>
    </div>
</div>

<?php if (! empty($integrity['errors'])): ?>
    <div class="card">
        <div class="card-header" style="background: rgba(239,68,68,0.08); color: var(--danger);">
            <i class="fas fa-triangle-exclamation"></i> Detected Tamper Events
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="table-mini">
                <thead>
                    <tr><th>Log #</th><th>Type</th><th>Expected</th><th>Actual</th><th>Detail</th></tr>
                </thead>
                <tbody>
                <?php foreach ($integrity['errors'] as $err): ?>
                    <tr>
                        <td><strong>#<?= (int) $err['log_id'] ?></strong></td>
                        <td>
                            <?php if (str_contains($err['message'], 'previous_hash')): ?>
                                <span class="badge badge-amber">Broken Link</span>
                            <?php else: ?>
                                <span class="badge badge-red">Content Tamper</span>
                            <?php endif; ?>
                        </td>
                        <td class="font-mono" style="font-size: 0.72rem; color: var(--gray-500);">
                            <?= esc(substr((string) $err['expected'], 0, 24)) ?>…
                        </td>
                        <td class="font-mono" style="font-size: 0.72rem; color: var(--danger);">
                            <?= esc(substr((string) $err['actual'], 0, 24)) ?>…
                        </td>
                        <td style="font-size: 0.8rem;"><?= esc($err['message']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<style>
    /* .table-mini and .kv-table are now centralized in synapse-ui.css */
    .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 99px; font-size: 0.7rem; font-weight: 600; }
    .badge-red { background: rgba(239,68,68,0.1); color: #B91C1C; }
    .badge-amber { background: rgba(245,158,11,0.1); color: #B45309; }
</style>
<?= $this->endSection() ?>