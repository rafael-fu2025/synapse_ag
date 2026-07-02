<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-calendar-day"></i></div>
        <div class="stat-info">
            <h3><?= esc($appointmentsToday) ?></h3>
            <p>Today's Appointments</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-bell"></i></div>
        <div class="stat-info">
            <h3><?= esc($crisisAlerts) ?></h3>
            <p>Crisis Alerts</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-arrow-right-arrow-left"></i></div>
        <div class="stat-info">
            <h3><?= esc($pendingReferrals) ?></h3>
            <p>Pending Referrals</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fas fa-chart-line"></i></div>
        <div class="stat-info">
            <h3><?= esc($activeCaseload) ?></h3>
            <p>Active Caseload</p>
        </div>
    </div>
</div>

<div class="section-grid">
    <div class="card">
        <div class="card-header">
            <i class="fas fa-calendar-check"></i> Today's Schedule
        </div>
        <div class="card-body">
            <p class="muted-text">
                Your appointment schedule will appear here once the Counselling module is active.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-robot"></i> AI Risk Alerts
        </div>
        <div class="card-body">
            <p class="muted-text">
                AI-detected risk scores and trend anomalies will appear here. Visible only to assigned counsellors.
            </p>
        </div>
    </div>
</div>

<div class="card insight-card">
    <div class="card-header insight-header">
        <i class="fas fa-robot"></i> AI-Generated Counselling Operations Insights — Last 30 Days
    </div>
    <div class="card-body insight-body">
        <?php if (! empty($aiSummary)): ?>
            <p class="insight-narrative"><?= esc($aiSummary) ?></p>
        <?php else: ?>
            <p class="insight-narrative placeholder-text">
                No AI summary available yet. Summaries are generated nightly; check back after the next refresh.
            </p>
        <?php endif; ?>
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
        font-size: 0.9rem;
    }

    .insight-card {
        margin-top: 1.25rem;
    }

    .insight-header {
        background: var(--primary-900);
        color: white;
    }

    .insight-body {
        background: var(--primary-50);
        padding: 1rem;
    }

    .insight-narrative {
        margin: 0;
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: 0.75rem;
        padding: 1rem;
        color: var(--gray-700);
        line-height: 1.6;
    }

    @media (max-width: 900px) {
        .section-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
<?= $this->endSection() ?>
