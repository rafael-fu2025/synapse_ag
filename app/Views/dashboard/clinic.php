<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-user-check"></i></div>
        <div class="stat-info">
            <h3><?= esc($patientsToday) ?></h3>
            <p>Patients Today</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-clipboard-check"></i></div>
        <div class="stat-info">
            <h3><?= esc($completedConsults) ?></h3>
            <p>Completed Consults</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-info">
            <h3><?= esc($inProgress) ?></h3>
            <p>In Progress</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="stat-info">
            <h3><?= esc($lowStockAlerts) ?></h3>
            <p>Low Stock Alerts</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-stethoscope"></i> Today's Queue
    </div>
    <div class="card-body">
        <p class="muted-text">
            No patients in queue. The consultation queue will appear here once the Clinic module is active.
        </p>
        <div class="placeholder-box">
            <i class="fas fa-qrcode placeholder-icon"></i>
            <p class="placeholder-text">QR/RFID check-in will auto-populate this queue.</p>
        </div>
    </div>
</div>

<div class="card insight-card">
    <div class="card-header insight-header">
        <i class="fas fa-robot"></i> AI-Generated Clinic Operations Insights — Last 30 Days
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
    .muted-text { color: var(--gray-500); font-size: 0.9rem; }
    .placeholder-box {
        margin-top: 1rem;
        padding: 2rem;
        background: var(--gray-50);
        border-radius: 0.75rem;
        border: 1px dashed var(--gray-200);
        text-align: center;
    }
    .placeholder-icon { font-size: 2rem; color: var(--gray-300); margin-bottom: 0.75rem; }
    .placeholder-text { color: var(--gray-500); font-size: 0.9rem; margin: 0; }
    .insight-card { margin-top: 1.25rem; }
    .insight-header { background: var(--primary-900); color: white; }
    .insight-body { background: var(--primary-50); padding: 1rem; }
    .insight-narrative {
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: 0.75rem;
        padding: 1rem;
        color: var(--gray-700);
        line-height: 1.6;
        margin: 0;
    }
</style>
<?= $this->endSection() ?>
