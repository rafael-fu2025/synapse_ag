<?php
/**
 * Shared partial used by all reports module views.
 *
 * Renders:
 *  - Date-range filter bar
 *  - Export-CSV button (calling /reports/export/{module})
 *  - Standard chart container styles
 *
 * Expects $module (string) and $range (array{start,end}) in scope.
 */
?>
<div class="card" style="margin-bottom: 1.25rem;">
    <div class="card-body" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; justify-content: space-between;">
        <form method="get" action="<?= base_url('reports/' . $module) ?>" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
            <div>
                <label for="start" style="display: block; font-size: 0.75rem; color: var(--gray-600); margin-bottom: 0.2rem;">Start</label>
                <input type="text" class="syn-datepicker" id="start" name="start" value="<?= esc($range['start']) ?>" placeholder="Start"
                       style="padding: 0.4rem 0.6rem; border: 1px solid var(--gray-300); border-radius: 0.4rem; font-family: inherit;">
            </div>
            <div>
                <label for="end" style="display: block; font-size: 0.75rem; color: var(--gray-600); margin-bottom: 0.2rem;">End</label>
                <input type="text" class="syn-datepicker" id="end" name="end" value="<?= esc($range['end']) ?>" placeholder="End"
                       style="padding: 0.4rem 0.6rem; border: 1px solid var(--gray-300); border-radius: 0.4rem; font-family: inherit;">
            </div>
            <button type="submit"
                    style="padding: 0.5rem 1rem; background: var(--primary-600); color: white; border: none; border-radius: 0.4rem; font-weight: 600; cursor: pointer; font-size: 0.85rem;">
                <i class="fas fa-rotate"></i> Update
            </button>
            <a href="<?= base_url('reports') ?>" style="font-size: 0.8rem; color: var(--gray-500); text-decoration: none;">
                <i class="fas fa-arrow-left"></i> All Reports
            </a>
        </form>

        <?php
            // Quick-range presets. Each link re-navigates with start/end set
            // server-side so the date pickers reflect the new range on render.
            $today      = date('Y-m-d');
            $sevenAgo   = date('Y-m-d', strtotime('-7 days'));
            $thirtyAgo  = date('Y-m-d', strtotime('-30 days'));
            $monthStart = date('Y-m-01');
            $presets = [
                ['label' => 'Today',       'start' => $today,      'end' => $today],
                ['label' => 'Last 7 days', 'start' => $sevenAgo,   'end' => $today],
                ['label' => 'Last 30 days','start' => $thirtyAgo,  'end' => $today],
                ['label' => 'This month',  'start' => $monthStart, 'end' => $today],
            ];
        ?>
        <div role="group" aria-label="Quick date range presets"
             style="display: flex; flex-wrap: wrap; gap: 0.35rem; align-items: center; margin-top: 0.5rem;">
            <span style="font-size: 0.7rem; color: var(--gray-500); margin-right: 0.25rem;">Quick:</span>
            <?php foreach ($presets as $p): ?>
                <a href="<?= base_url('reports/' . $module) ?>?start=<?= esc($p['start']) ?>&end=<?= esc($p['end']) ?>"
                   aria-label="Set range to <?= esc($p['label']) ?>"
                   style="padding: 0.25rem 0.6rem; background: var(--gray-100); color: var(--gray-700); border-radius: 0.4rem; font-size: 0.75rem; text-decoration: none; font-weight: 500;">
                    <?= esc($p['label']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <a href="<?= base_url('reports/export/' . $module) ?>?start=<?= esc($range['start']) ?>&end=<?= esc($range['end']) ?>"
           class="btn"
           style="padding: 0.55rem 1.1rem; background: var(--success); color: white; text-decoration: none; border-radius: 0.4rem; font-weight: 600; font-size: 0.85rem;">
            <i class="fas fa-file-csv"></i> Export CSV
        </a>
    </div>
</div>

<style>
    .chart-card { padding: 1.25rem; }
    .chart-container { position: relative; height: 280px; width: 100%; }
    .chart-container.tall { height: 360px; }
    .chart-title { font-size: 0.9rem; font-weight: 700; color: var(--gray-800); margin: 0 0 0.75rem; }
    .reports-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
        gap: 1.25rem;
        margin-bottom: 1.25rem;
    }
    /* .table-mini is now centralized in synapse-ui.css */
    .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 99px; font-size: 0.7rem; font-weight: 600; }
    .badge-green { background: rgba(16,185,129,0.1); color: #15803D; }
    .badge-amber { background: rgba(245,158,11,0.1); color: #B45309; }
    .badge-red   { background: rgba(239,68,68,0.1);  color: #B91C1C; }
    .badge-blue  { background: var(--primary-50); color: var(--primary-700); }
    .badge-gray  { background: var(--gray-100);     color: var(--gray-700); }
    .badge-purple{ background: #F5F3FF;             color: #6D28D9; }
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
    }
    .empty-state {
        padding: 2.5rem 1rem;
        text-align: center;
        color: var(--gray-500);
        font-size: 0.875rem;
    }
</style>