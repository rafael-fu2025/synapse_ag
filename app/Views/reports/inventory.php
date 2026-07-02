<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-pills"></i></div>
        <div class="stat-info">
            <h3><?= number_format($totalMedicines) ?></h3>
            <p>Active Medicines</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="stat-info">
            <h3><?= number_format(count($lowStock)) ?></h3>
            <p>Low-Stock Items</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-calendar-xmark"></i></div>
        <div class="stat-info">
            <h3><?= number_format(count($expiring)) ?></h3>
            <p>Expiring (90 days)</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fas fa-hand-holding-medical"></i></div>
        <div class="stat-info">
            <h3><?= number_format($totalDispensed) ?></h3>
            <p>Dispensed in Period</p>
        </div>
    </div>
</div>

<?= view('reports/_shared') ?>

<div class="reports-grid">
    <div class="card chart-card">
        <h4 class="chart-title"><i class="fas fa-chart-line"></i> Dispensing Trend</h4>
        <div class="chart-container tall">
            <canvas id="invTrendChart"></canvas>
        </div>
    </div>
    <div class="card chart-card">
        <h4 class="chart-title"><i class="fas fa-chart-pie"></i> Medicine Categories</h4>
        <div class="chart-container tall">
            <canvas id="invCategoryChart"></canvas>
        </div>
    </div>
</div>

<div class="card chart-card" style="margin-bottom: 1.25rem;">
    <h4 class="chart-title"><i class="fas fa-cubes-stacked"></i> Top Dispensed Medicines</h4>
    <?php if (empty($topDispensed)): ?>
        <div class="empty-state">No dispensing transactions in this period.</div>
    <?php else: ?>
        <table class="table-mini">
            <thead>
                <tr><th scope="col">Medicine</th><th scope="col" style="text-align: right;">Quantity Dispensed</th></tr>
            </thead>
            <tbody>
            <?php foreach ($topDispensed as $row): ?>
                <tr>
                    <td>
                        <strong><?= esc($row['generic_name']) ?></strong>
                        <?php if (!empty($row['brand_name'])): ?>
                            <br><span style="font-size: 0.75rem; color: var(--gray-500);"><?= esc($row['brand_name']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right;"><strong><?= number_format((float) $row['qty']) ?></strong> <span style="color: var(--gray-500); font-size: 0.75rem;"><?= esc($row['unit']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="reports-grid">
    <div class="card chart-card">
        <h4 class="chart-title"><i class="fas fa-triangle-exclamation"></i> Low-Stock Medicines</h4>
        <?php if (empty($lowStock)): ?>
            <div class="empty-state">No medicines below reorder threshold.</div>
        <?php else: ?>
            <table class="table-mini">
                <thead><tr><th scope="col">Medicine</th><th scope="col" style="text-align: right;">Stock</th><th scope="col" style="text-align: right;">Reorder At</th></tr></thead>
                <tbody>
                <?php foreach ($lowStock as $row): ?>
                    <tr>
                        <td>
                            <?= esc($row['generic_name']) ?>
                            <?php if (!empty($row['brand_name'])): ?>
                                <br><span style="font-size: 0.75rem; color: var(--gray-500);"><?= esc($row['brand_name']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;"><span class="badge badge-red"><?= number_format((float) $row['total_stock']) ?> <?= esc($row['unit']) ?></span></td>
                        <td style="text-align: right; color: var(--gray-600);"><?= number_format((float) $row['reorder_threshold']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <div class="card chart-card">
        <h4 class="chart-title"><i class="fas fa-calendar-xmark"></i> Expiring Batches (≤90 days)</h4>
        <?php if (empty($expiring)): ?>
            <div class="empty-state">No batches expiring within 90 days.</div>
        <?php else: ?>
            <table class="table-mini">
                <thead><tr><th scope="col">Medicine</th><th scope="col">Batch</th><th scope="col">Expires</th><th scope="col" style="text-align: right;">Qty</th></tr></thead>
                <tbody>
                <?php foreach (array_slice($expiring, 0, 10) as $row): ?>
                    <?php
                        $daysToExpiry = (int) ((strtotime($row['expiration_date']) - strtotime(date('Y-m-d'))) / 86400);
                        $badge = $daysToExpiry <= 30 ? 'badge-red' : 'badge-amber';
                    ?>
                    <tr>
                        <td><?= esc($row['generic_name']) ?></td>
                        <td><?= esc($row['batch_number']) ?></td>
                        <td>
                            <span class="badge <?= $badge ?>">
                                <?= esc(date('M d, Y', strtotime($row['expiration_date']))) ?>
                                (<?= $daysToExpiry ?>d)
                            </span>
                        </td>
                        <td style="text-align: right;"><?= number_format((int) $row['quantity_remaining']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="card insight-card">
    <div class="card-header insight-header">
        <i class="fas fa-robot"></i> AI-Generated Insights — <?= esc(date('M d, Y', strtotime($range['start']))) ?> to <?= esc(date('M d, Y', strtotime($range['end']))) ?>
    </div>
    <div class="card-body insight-body">
        <p class="insight-narrative"><?= $aiSummary ?></p>
    </div>
</div>

<script>
(function () {
    const palette = ['var(--primary-500)','#10B981','#F59E0B','#EF4444','#8B5CF6','#14B8A6','#3B82F6','#EC4899'];
    const baseOpts = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { family: 'Inter', size: 11 } } } } };

    const trend = <?= json_encode($dispensingTrend) ?>;
    new Chart(document.getElementById('invTrendChart'), {
        type: 'line',
        data: {
            labels: trend.map(t => t.day),
            datasets: [{
                label: 'Units Dispensed',
                data: trend.map(t => Number(t.qty)),
                borderColor: palette[5],
                backgroundColor: 'rgba(20,184,166,0.12)',
                fill: true,
                tension: 0.3,
                pointRadius: 3,
                pointBackgroundColor: palette[5],
            }]
        },
        options: { ...baseOpts, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    const cats = <?= json_encode($categoryBreakdown) ?>;
    new Chart(document.getElementById('invCategoryChart'), {
        type: 'doughnut',
        data: {
            labels: cats.map(c => c.category),
            datasets: [{ data: cats.map(c => Number(c.cnt)), backgroundColor: palette }]
        },
        options: baseOpts
    });
})();
</script>
<?= $this->endSection() ?>