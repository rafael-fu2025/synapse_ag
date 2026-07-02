<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?= view('components/welcome_panel', [
    'welcomeTitle'    => 'Welcome back, {name}',
    'welcomeSubtitle' => 'System is healthy. ' . number_format($activeUsers) . ' of ' . number_format($totalUsers) . ' accounts active. Audit chain ' . ($integrity && $integrity['intact'] ? 'verified' : 'requires attention') . '.',
    'welcomeContext'  => 'Logged in as ' . esc(ucwords(str_replace('_', ' ', (string) session()->get('primary_role') ?? 'admin'))) . ' · ' . date('l, F j, Y'),
    'heroSize'        => 'lg',
    'actions'         => [
        ['label' => 'User management', 'url' => base_url('admin/users'), 'icon' => 'fas fa-users-cog', 'variant' => 'primary'],
        ['label' => 'Audit logs',      'url' => base_url('admin/audit'), 'icon' => 'fas fa-shield-halved', 'variant' => 'secondary'],
        ['label' => 'Reports',         'url' => base_url('reports'),    'icon' => 'fas fa-chart-bar',   'variant' => 'ghost'],
    ],
]) ?>

<div class="page-header">
    <div class="page-header-text">
        <div class="page-header-eyebrow">System overview</div>
        <h1 class="page-header-title">Health at a glance</h1>
    </div>
    <div class="page-header-meta">
        <i class="fas fa-clock"></i>
        <span>Refreshed <?= date('H:i') ?></span>
    </div>
</div>

<div class="stats-grid">
    <a href="<?= base_url('admin/users') ?>" class="stat-card is-clickable" style="text-decoration: none; color: inherit;">
        <div class="stat-icon" style="background: var(--primary-50); color: var(--primary-600);"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3><?= number_format($totalUsers) ?></h3>
            <p>Total Users &middot; <?= number_format($activeUsers) ?> active</p>
        </div>
    </a>
    <a href="<?= base_url('admin/roles') ?>" class="stat-card is-clickable" style="text-decoration: none; color: inherit;">
        <div class="stat-icon" style="background: #F5F3FF; color: #6D28D9;"><i class="fas fa-user-shield"></i></div>
        <div class="stat-info">
            <h3><?= number_format($totalRoles) ?></h3>
            <p>Roles &middot; <?= number_format($totalPermissions) ?> permissions</p>
        </div>
    </a>
    <a href="<?= base_url('admin/audit') ?>" class="stat-card is-clickable" style="text-decoration: none; color: inherit;">
        <div class="stat-icon" style="background: rgba(16,185,129,.08); color: #047857;"><i class="fas fa-shield-halved"></i></div>
        <div class="stat-info">
            <h3><?= number_format($totalAuditLogs) ?></h3>
            <p>Audit Log Entries</p>
        </div>
    </a>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(245,158,11,.08); color: #B45309;"><i class="fas fa-puzzle-piece"></i></div>
        <div class="stat-info">
            <h3><?= number_format($totalSysModules) ?></h3>
            <p>System Modules</p>
        </div>
    </div>
</div>

<?php if ($integrity && !$integrity['intact']): ?>
    <div class="alert alert-danger">
        <i class="fas fa-triangle-exclamation"></i>
        <div>
            <strong>Audit chain integrity failure detected.</strong>
            <?= count($integrity['errors']) ?> tamper event(s) in the last <?= number_format($integrity['checked']) ?> entries.
            <a href="<?= base_url('admin/audit/verify') ?>" style="color: white; text-decoration: underline; margin-left: 0.5rem;">Investigate &rarr;</a>
        </div>
    </div>
<?php elseif ($integrity): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        Audit log chain integrity verified for the last <?= number_format($integrity['checked']) ?> entries.
        <a href="<?= base_url('admin/audit/verify') ?>" style="color: #065F46; text-decoration: underline; margin-left: 0.5rem;">Run full check &rarr;</a>
    </div>
<?php endif; ?>

<div class="reports-grid">
    <div class="card chart-card">
        <h4 class="chart-title"><i class="fas fa-chart-line"></i> Audit Activity (Last 7 Days)</h4>
        <?php if (empty($auditTrend)): ?>
            <div class="empty-state">No audit events in the last week.</div>
        <?php else: ?>
            <div class="chart-container">
                <canvas id="adminAuditChart"></canvas>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header"><i class="fas fa-clock-rotate-left"></i> Recent Activity</div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($recentAudit)): ?>
                <div class="empty-state">No recent activity.</div>
            <?php else: ?>
                <table class="table-mini">
                    <thead><tr><th>When</th><th>Action</th><th>Who</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentAudit as $row): ?>
                        <tr>
                            <td style="white-space: nowrap; color: var(--gray-600); font-size: 0.75rem;">
                                <?= esc(date('M d H:i', strtotime($row['created_at']))) ?>
                            </td>
                            <td>
                                <span class="badge badge-blue"><?= esc($row['action']) ?></span>
                                <span style="font-size: 0.7rem; color: var(--gray-500);"><?= esc($row['module']) ?></span>
                            </td>
                            <td><?= esc(trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: 'System') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="fas fa-th-large"></i> Quick Actions</div>
    <div class="card-body">
        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
            <a href="<?= base_url('admin/users/create') ?>" class="btn"
               style="padding: 0.55rem 1.1rem; background: var(--primary-600); color: white; text-decoration: none; border-radius: 0.5rem; font-weight: 600; font-size: 0.85rem;">
                <i class="fas fa-user-plus"></i> New User
            </a>
            <a href="<?= base_url('admin/roles/create') ?>" class="btn"
               style="padding: 0.55rem 1.1rem; background: var(--primary-100); color: var(--primary-700); text-decoration: none; border-radius: 0.5rem; font-weight: 600; font-size: 0.85rem;">
                <i class="fas fa-user-shield"></i> New Role
            </a>
            <a href="<?= base_url('admin/audit') ?>" class="btn"
               style="padding: 0.55rem 1.1rem; background: var(--gray-100); color: var(--gray-700); text-decoration: none; border-radius: 0.5rem; font-weight: 600; font-size: 0.85rem;">
                <i class="fas fa-shield-halved"></i> Audit Logs
            </a>
            <a href="<?= base_url('admin/audit/verify') ?>" class="btn"
               style="padding: 0.55rem 1.1rem; background: var(--gray-100); color: var(--gray-700); text-decoration: none; border-radius: 0.5rem; font-weight: 600; font-size: 0.85rem;">
                <i class="fas fa-fingerprint"></i> Verify Chain
            </a>
            <a href="<?= base_url('reports') ?>" class="btn"
               style="padding: 0.55rem 1.1rem; background: var(--gray-100); color: var(--gray-700); text-decoration: none; border-radius: 0.5rem; font-weight: 600; font-size: 0.85rem;">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
        </div>
    </div>
</div>

<?php if (! empty($systemModules)): ?>
<div class="card" style="margin-top: 1.25rem;">
    <div class="card-header"><i class="fas fa-puzzle-piece"></i> System Modules</div>
    <div class="card-body" style="padding: 0;">
        <table class="table-mini">
            <thead>
                <tr>
                    <th>Module</th>
                    <th>Description</th>
                    <th>Version</th>
                    <th style="text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($systemModules as $m): ?>
                <tr>
                    <td>
                        <strong><?= esc($m['display_name']) ?></strong>
                        <br><span style="font-size: 0.7rem; color: var(--gray-500);"><?= esc($m['name']) ?></span>
                    </td>
                    <td style="color: var(--gray-600); font-size: 0.8rem;"><?= esc($m['description'] ?? 'â€”') ?></td>
                    <td><?= esc($m['version'] ?? 'â€”') ?></td>
                    <td style="text-align: center;">
                        <?php if ($m['is_enabled']): ?>
                            <span class="badge badge-green">Enabled</span>
                        <?php else: ?>
                            <span class="badge badge-gray">Disabled</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<style>
    .stats-grid a.stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
    .chart-card { padding: 1.25rem; }
    .chart-container { position: relative; height: 280px; width: 100%; }
    .chart-title { font-size: 0.9rem; font-weight: 700; color: var(--gray-800); margin: 0 0 0.75rem; }
    /* .table-mini is now centralized in synapse-ui.css */
    .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 99px; font-size: 0.7rem; font-weight: 600; }
    .badge-blue { background: var(--primary-50); color: var(--primary-700); }
    .badge-green { background: rgba(16,185,129,0.1); color: #15803D; }
    .badge-gray { background: var(--gray-100); color: var(--gray-700); }
    .empty-state { padding: 2.5rem 1rem; text-align: center; color: var(--gray-500); font-size: 0.875rem; }
</style>

<?php if (! empty($auditTrend)): ?>
<script>
(function () {
    const daily = <?= json_encode($auditTrend) ?>;
    new Chart(document.getElementById('adminAuditChart'), {
        type: 'bar',
        data: {
            labels: daily.map(d => d.day),
            datasets: [{
                label: 'Audit Events',
                data: daily.map(d => Number(d.cnt)),
                backgroundColor: 'var(--primary-500)',
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>