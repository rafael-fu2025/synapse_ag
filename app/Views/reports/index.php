<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-calendar-days"></i></div>
        <div class="stat-info">
            <h3 style="font-size: 1rem;"><?= esc(date('M d, Y', strtotime($range['start']))) ?></h3>
            <p>Period Start</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-info">
            <h3 style="font-size: 1rem;"><?= esc(date('M d, Y', strtotime($range['end']))) ?></h3>
            <p>Period End</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-chart-bar"></i></div>
        <div class="stat-info">
            <h3><?= count($modules) ?></h3>
            <p>Report Modules</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-filter"></i> Date Range Filter
    </div>
    <div class="card-body">
        <form method="get" action="<?= base_url('reports') ?>" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
            <div>
                <label for="start" style="display: block; font-size: 0.8rem; color: var(--gray-600); margin-bottom: 0.25rem;">Start Date</label>
                <input type="text" class="syn-datepicker" id="start" name="start" value="<?= esc($range['start']) ?>" placeholder="Start"
                       style="padding: 0.5rem 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.5rem; font-family: inherit;">
            </div>
            <div>
                <label for="end" style="display: block; font-size: 0.8rem; color: var(--gray-600); margin-bottom: 0.25rem;">End Date</label>
                <input type="text" class="syn-datepicker" id="end" name="end" value="<?= esc($range['end']) ?>" placeholder="End"
                       style="padding: 0.5rem 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.5rem; font-family: inherit;">
            </div>
            <button type="submit" class="btn"
                    style="padding: 0.55rem 1.25rem; background: var(--primary-600); color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer;">
                <i class="fas fa-rotate"></i> Update
            </button>
            <span style="font-size: 0.8rem; color: var(--gray-500);">
                Each module uses its own date-aware query and accepts the same range via query parameters.
            </span>
        </form>
    </div>
</div>

<div class="reports-module-grid">
    <?php foreach ($modules as $key => $m): ?>
        <a href="<?= esc($m['url']) ?>?start=<?= esc($range['start']) ?>&end=<?= esc($range['end']) ?>"
           class="report-module-card">
            <div class="report-module-icon">
                <i class="fas <?= esc($m['icon']) ?>"></i>
            </div>
            <div class="report-module-body">
                <h3 class="report-module-title"><?= esc($m['label']) ?></h3>
                <p class="report-module-desc"><?= esc($m['description']) ?></p>
                <div class="report-module-kpi">
                    <span class="report-module-kpi-value"><?= number_format($m['kpi']) ?></span>
                    <span class="report-module-kpi-label">records in selected period</span>
                </div>
            </div>
            <div class="report-module-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
        </a>
    <?php endforeach; ?>
</div>

<style>
    .reports-module-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 1.25rem;
        margin-top: 1.25rem;
    }

    .report-module-card {
        display: flex;
        align-items: stretch;
        gap: 1rem;
        padding: 1.5rem;
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: 0.75rem;
        text-decoration: none;
        color: inherit;
        box-shadow: var(--shadow-sm);
        transition: all var(--transition-base);
    }

    .report-module-card:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
        border-color: var(--primary-300);
    }

    .report-module-icon {
        width: 56px;
        height: 56px;
        border-radius: 0.875rem;
        background: linear-gradient(135deg, var(--primary-500), var(--primary-700));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }

    .report-module-body {
        flex: 1;
        min-width: 0;
    }

    .report-module-title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0 0 0.25rem;
    }

    .report-module-desc {
        font-size: 0.825rem;
        color: var(--gray-600);
        margin: 0 0 0.75rem;
        line-height: 1.4;
    }

    .report-module-kpi {
        display: flex;
        align-items: baseline;
        gap: 0.5rem;
    }

    .report-module-kpi-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary-700);
    }

    .report-module-kpi-label {
        font-size: 0.75rem;
        color: var(--gray-500);
    }

    .report-module-arrow {
        display: flex;
        align-items: center;
        color: var(--gray-400);
        font-size: 0.9rem;
        align-self: center;
        transition: transform var(--transition-base);
    }

    .report-module-card:hover .report-module-arrow {
        color: var(--primary-600);
        transform: translateX(3px);
    }
</style>
<?= $this->endSection() ?>