<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-info">
            <h3><?= number_format($totalAppts) ?></h3>
            <p>Appointments</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-user-xmark"></i></div>
        <div class="stat-info">
            <h3><?= number_format($noShowCount) ?></h3>
            <p>No-Shows (<?= esc($noShowRate) ?>%)</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-bell"></i></div>
        <div class="stat-info">
            <h3><?= number_format($crisisAlerts) ?></h3>
            <p>Crisis Alerts</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fas fa-clipboard-list"></i></div>
        <div class="stat-info">
            <h3><?= number_format($severeScreenings) ?></h3>
            <p>Severe Screenings (≥15)</p>
        </div>
    </div>
</div>

<?= view('reports/_shared') ?>

<div class="reports-grid">
    <div class="card chart-card">
        <h4 class="chart-title"><i class="fas fa-chart-line"></i> Appointments per Day</h4>
        <div class="chart-container tall">
            <canvas id="counsDailyChart"></canvas>
        </div>
    </div>
    <div class="card chart-card">
        <h4 class="chart-title"><i class="fas fa-chart-pie"></i> Status Breakdown</h4>
        <div class="chart-container tall">
            <canvas id="counsStatusChart"></canvas>
        </div>
    </div>
</div>

<div class="reports-grid">
    <div class="card chart-card">
        <h4 class="chart-title"><i class="fas fa-tags"></i> Appointment Type</h4>
        <div class="chart-container">
            <canvas id="counsTypeChart"></canvas>
        </div>
    </div>
    <div class="card chart-card">
        <h4 class="chart-title"><i class="fas fa-triangle-exclamation"></i> Crisis Alerts by Severity</h4>
        <?php if (empty($crisisBySeverity)): ?>
            <div class="empty-state">No crisis alerts in this period.</div>
        <?php else: ?>
            <table class="table-mini">
                <thead><tr><th scope="col">Severity</th><th scope="col" style="text-align: right;">Count</th></tr></thead>
                <tbody>
                <?php foreach ($crisisBySeverity as $row): ?>
                    <tr>
                        <td>
                            <?php
                                $cls = match ($row['severity']) {
                                    'critical' => 'badge-red',
                                    'high'     => 'badge-amber',
                                    'moderate' => 'badge-blue',
                                    default    => 'badge-gray',
                                };
                            ?>
                            <span class="badge <?= $cls ?>"><?= esc($row['severity']) ?></span>
                        </td>
                        <td style="text-align: right;"><strong><?= number_format($row['cnt']) ?></strong></td>
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

    const daily = <?= json_encode($dailyTrend) ?>;
    new Chart(document.getElementById('counsDailyChart'), {
        type: 'line',
        data: {
            labels: daily.map(d => d.day),
            datasets: [{
                label: 'Appointments',
                data: daily.map(d => Number(d.cnt)),
                borderColor: palette[2],
                backgroundColor: 'rgba(245,158,11,0.12)',
                fill: true,
                tension: 0.3,
                pointRadius: 3,
                pointBackgroundColor: palette[2],
            }]
        },
        options: { ...baseOpts, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    const status = <?= json_encode($statusBreakdown) ?>;
    new Chart(document.getElementById('counsStatusChart'), {
        type: 'doughnut',
        data: {
            labels: status.map(s => s.status),
            datasets: [{ data: status.map(s => Number(s.cnt)), backgroundColor: palette }]
        },
        options: baseOpts
    });

    const types = <?= json_encode($typeBreakdown) ?>;
    new Chart(document.getElementById('counsTypeChart'), {
        type: 'bar',
        data: {
            labels: types.map(t => t.type),
            datasets: [{
                label: 'Appointments',
                data: types.map(t => Number(t.cnt)),
                backgroundColor: palette[4],
                borderRadius: 4,
            }]
        },
        options: { ...baseOpts, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
})();
</script>
<?= $this->endSection() ?>