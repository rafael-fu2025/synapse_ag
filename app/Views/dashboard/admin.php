<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3><?= esc($totalUsers) ?></h3>
            <p>Total Users</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-stethoscope"></i></div>
        <div class="stat-info">
            <h3><?= esc($consultationsToday) ?></h3>
            <p>Consultations Today</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fas fa-hand-holding-heart"></i></div>
        <div class="stat-info">
            <h3><?= esc($appointmentsThisWeek) ?></h3>
            <p>Appointments This Week</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-pills"></i></div>
        <div class="stat-info">
            <h3><?= esc($lowStockMedicines) ?></h3>
            <p>Low Stock Medicines</p>
        </div>
    </div>
</div>

<div class="section-grid">
    <div class="card">
        <div class="card-header">
            <i class="fas fa-chart-line"></i> System Overview
        </div>
        <div class="card-body">
            <p class="muted-text">
                Welcome to the SYNAPSE administration panel. System statistics and activity logs will appear here once modules are active.
            </p>
            <div class="quick-actions-box">
                <p class="quick-actions-label">Quick Actions</p>
                <div class="quick-action-pills">
                    <span class="pill pill-teal"><i class="fas fa-user-plus"></i> Manage Users</span>
                    <span class="pill pill-green"><i class="fas fa-shield-halved"></i> Audit Logs</span>
                    <span class="pill pill-orange"><i class="fas fa-chart-bar"></i> Reports</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-clock-rotate-left"></i> Recent Activity
        </div>
        <div class="card-body">
            <div class="activity-list">
                <div class="activity-item">
                    <div class="activity-dot"></div>
                    <div>
                        <p class="activity-text">System initialized — ready for module development</p>
                        <p class="activity-time">Just now</p>
                    </div>
                </div>
                <p class="activity-placeholder">
                    Activity feed will populate as modules come online.
                </p>
            </div>
        </div>
    </div>
</div>

<div class="card insight-card">
    <div class="card-header insight-header">
        <i class="fas fa-robot"></i> AI-Generated System Insights — Last 30 Days
    </div>
    <div class="card-body insight-body">
        <div class="insight-col">
            <h4 class="insight-title"><i class="fas fa-stethoscope"></i> Clinic Operations</h4>
            <?php if (! empty($clinicSummary)): ?>
                <div class="insight-narrative"><?= esc($clinicSummary) ?></div>
            <?php else: ?>
                <div class="insight-narrative placeholder-text">
                    No AI summary available yet. Summaries are generated nightly.
                </div>
            <?php endif; ?>
        </div>
        <div class="insight-col">
            <h4 class="insight-title insight-title-alt"><i class="fas fa-hand-holding-heart"></i> Counselling Services</h4>
            <?php if (! empty($counsellingSummary)): ?>
                <div class="insight-narrative"><?= esc($counsellingSummary) ?></div>
            <?php else: ?>
                <div class="insight-narrative placeholder-text">
                    No AI summary available yet. Summaries are generated nightly.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .section-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem;
    }

    .muted-text {
        color: var(--gray-500);
        font-size: 0.875rem;
    }

    .quick-actions-box {
        margin-top: 1.25rem;
        padding: 1rem;
        background: var(--gray-50);
        border-radius: 0.75rem;
        border: 1px solid var(--gray-200);
    }

    .quick-actions-label {
        font-size: 0.8rem;
        color: var(--gray-700);
        font-weight: 600;
        margin-bottom: 0.75rem;
        display: block;
    }

    .quick-action-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .pill {
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .pill-teal {
        background: var(--primary-100);
        color: var(--primary-700);
    }

    .pill-green {
        background: #ECFDF5;
        color: #15803D;
    }

    .pill-orange {
        background: #FFFBEB;
        color: #B45309;
    }

    .activity-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .activity-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--gray-100);
    }

    .activity-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--success);
        flex-shrink: 0;
    }

    .activity-text {
        font-size: 0.875rem;
        color: var(--gray-700);
        margin: 0;
    }

    .activity-time {
        font-size: 0.75rem;
        color: var(--gray-400);
        margin: 0.25rem 0 0;
    }

    .activity-placeholder {
        font-size: 0.875rem;
        color: var(--gray-400);
        text-align: center;
        padding: 1rem 0;
        margin: 0;
    }

    .insight-card {
        margin-top: 1.25rem;
    }

    .insight-header {
        background: var(--primary-900);
        color: white;
    }

    .insight-body {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        background: var(--primary-50);
    }

    .insight-col {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .insight-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--primary-800);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0;
    }

    .insight-title-alt {
        color: var(--primary-700);
    }

    .insight-narrative {
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: 0.75rem;
        padding: 1rem;
        color: var(--gray-700);
        font-size: 0.9rem;
        line-height: 1.6;
    }

    @media (max-width: 900px) {
        .section-grid,
        .insight-body {
            grid-template-columns: 1fr;
        }
    }
</style>
<?= $this->endSection() ?>
