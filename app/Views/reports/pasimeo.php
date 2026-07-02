<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-people-carry-box"></i></div>
        <div class="stat-info">
            <h3><?= number_format($activitiesTotal) ?></h3>
            <p>Activities Conducted</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <h3><?= number_format($totalHours, 1) ?></h3>
            <p>Volunteer Hours</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3><?= number_format($distinctVolunteers) ?></h3>
            <p>Distinct Volunteers</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fas fa-list-check"></i></div>
        <div class="stat-info">
            <h3>
                <?php
                $active = 0;
                foreach ($programs as $p) {
                    if ($p['status'] === 'active') $active = (int) $p['cnt'];
                }
                echo number_format($active);
                ?>
            </h3>
            <p>Active Programs</p>
        </div>
    </div>
</div>

<?= view('reports/_shared') ?>

<div class="reports-grid">
    <div class="card chart-card">
        <h4 class="chart-title"><i class="fas fa-chart-line"></i> Activities per Day</h4>
        <div class="chart-container tall">
            <canvas id="pasDailyChart"></canvas>
        </div>
    </div>
    <div class="card chart-card">
        <h4 class="chart-title"><i class="fas fa-chart-pie"></i> Activity Status</h4>
        <div class="chart-container tall">
            <canvas id="pasStatusChart"></canvas>
        </div>
    </div>
</div>

<div class="reports-grid">
    <div class="card chart-card">
        <h4 class="chart-title"><i class="fas fa-list-check"></i> Volunteer Assignment Status</h4>
        <?php if (empty($assignmentsByStatus)): ?>
            <div class="empty-state">No volunteer assignments in this period.</div>
        <?php else: ?>
            <table class="table-mini">
                <thead><tr><th scope="col">Status</th><th scope="col" style="text-align: right;">Count</th></tr></thead>
                <tbody>
                <?php foreach ($assignmentsByStatus as $row): ?>
                    <tr>
                        <td>
                            <?php
                                $cls = match ($row['status']) {
                                    'confirmed' => 'badge-green',
                                    'assigned'  => 'badge-blue',
                                    'declined'  => 'badge-gray',
                                    'conflict'  => 'badge-red',
                                    default     => 'badge-gray',
                                };
                            ?>
                            <span class="badge <?= $cls ?>"><?= esc($row['status']) ?></span>
                        </td>
                        <td style="text-align: right;"><strong><?= number_format($row['cnt']) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <div class="card chart-card">
        <h4 class="chart-title"><i class="fas fa-trophy"></i> Top Volunteers by Hours</h4>
        <?php if (empty($topVolunteers)): ?>
            <div class="empty-state">No volunteer hours recorded in this period.</div>
        <?php else: ?>
            <table class="table-mini">
                <thead><tr><th scope="col">Volunteer</th><th scope="col" style="text-align: right;">Hours</th><th scope="col" style="text-align: right;">Sessions</th></tr></thead>
                <tbody>
                <?php foreach ($topVolunteers as $row): ?>
                    <tr>
                        <td><?= esc(trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))) ?: '(unknown)' ?></td>
                        <td style="text-align: right;"><strong><?= number_format((float) $row['total_hours'], 2) ?></strong></td>
                        <td style="text-align: right;"><?= number_format($row['sessions']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="card chart-card">
    <h4 class="chart-title"><i class="fas fa-folder-open"></i> Program Status</h4>
    <?php if (empty($programs)): ?>
        <div class="empty-state">No outreach programs yet.</div>
    <?php else: ?>
        <table class="table-mini">
            <thead><tr><th scope="col">Status</th><th scope="col" style="text-align: right;">Count</th></tr></thead>
            <tbody>
            <?php foreach ($programs as $row): ?>
                <tr>
                    <td>
                        <?php
                            $cls = match ($row['status']) {
                                'active'    => 'badge-green',
                                'planning'  => 'badge-blue',
                                'completed' => 'badge-gray',
                                'cancelled' => 'badge-red',
                                default     => 'badge-gray',
                            };
                        ?>
                        <span class="badge <?= $cls ?>"><?= esc($row['status']) ?></span>
                    </td>
                    <td style="text-align: right;"><strong><?= number_format($row['cnt']) ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
(function () {
    const palette = ['var(--primary-500)','#10B981','#F59E0B','#EF4444','#8B5CF6','#14B8A6','#3B82F6','#EC4899'];
    const baseOpts = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { family: 'Inter', size: 11 } } } } };

    const daily = <?= json_encode($dailyTrend) ?>;
    new Chart(document.getElementById('pasDailyChart'), {
        type: 'bar',
        data: {
            labels: daily.map(d => d.day),
            datasets: [{
                label: 'Activities',
                data: daily.map(d => Number(d.cnt)),
                backgroundColor: palette[1],
                borderRadius: 4,
            }]
        },
        options: { ...baseOpts, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    const status = <?= json_encode($activitiesByStatus) ?>;
    new Chart(document.getElementById('pasStatusChart'), {
        type: 'doughnut',
        data: {
            labels: status.map(s => s.status),
            datasets: [{ data: status.map(s => Number(s.cnt)), backgroundColor: palette }]
        },
        options: baseOpts
    });
})();
</script>
<?= $this->endSection() ?>