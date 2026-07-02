<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-stethoscope"></i></div>
        <div class="stat-info">
            <h3><?= number_format($totalConsultations) ?></h3>
            <p>Consultations</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-share"></i></div>
        <div class="stat-info">
            <h3><?= number_format($referralsCount) ?></h3>
            <p>Referrals Sent</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="stat-info">
            <h3>
                <?php
                $crit = 0;
                foreach ($triageBreakdown as $t) {
                    if (in_array($t['priority'], ['high', 'urgent'], true)) $crit += (int) $t['cnt'];
                }
                echo number_format($crit);
                ?>
            </h3>
            <p>High / Urgent Triage</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fas fa-list-ul"></i></div>
        <div class="stat-info">
            <h3><?= count($topComplaints) ?></h3>
            <p>Distinct Complaints</p>
        </div>
    </div>
</div>

<?= view('reports/_shared') ?>

<div class="reports-grid">
    <div class="card chart-card">
        <h4 class="chart-title"><i class="fas fa-chart-line"></i> Consultations per Day</h4>
        <div class="chart-container tall">
            <canvas id="clinicDailyChart"></canvas>
        </div>
    </div>
    <div class="card chart-card">
        <h4 class="chart-title"><i class="fas fa-chart-pie"></i> Triage Priority Breakdown</h4>
        <div class="chart-container tall">
            <canvas id="clinicTriageChart"></canvas>
        </div>
    </div>
</div>

<div class="reports-grid">
    <div class="card chart-card">
        <h4 class="chart-title"><i class="fas fa-chart-bar"></i> Consultation Status</h4>
        <div class="chart-container">
            <canvas id="clinicStatusChart"></canvas>
        </div>
    </div>
    <div class="card chart-card">
        <h4 class="chart-title"><i class="fas fa-notes-medical"></i> Top Chief Complaints</h4>
        <?php if (empty($topComplaints)): ?>
            <div class="empty-state">No chief complaints recorded in this period.</div>
        <?php else: ?>
            <table class="table-mini">
                <thead><tr><th scope="col">Complaint</th><th scope="col" style="text-align: right;">Count</th></tr></thead>
                <tbody>
                <?php foreach ($topComplaints as $row): ?>
                    <tr>
                        <td><?= esc($row['chief_complaint']) ?></td>
                        <td style="text-align: right;"><strong><?= number_format($row['cnt']) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="card chart-card">
    <h4 class="chart-title"><i class="fas fa-arrow-right-arrow-left"></i> Referrals by Direction</h4>
    <?php if (empty($referralsByDirection)): ?>
        <div class="empty-state">No referrals sent in this period.</div>
    <?php else: ?>
        <table class="table-mini">
            <thead><tr><th scope="col">Direction</th><th scope="col" style="text-align: right;">Count</th></tr></thead>
            <tbody>
            <?php foreach ($referralsByDirection as $row): ?>
                <tr>
                    <td>
                        <?php if ($row['direction'] === 'clinic_to_counselling'): ?>
                            <span class="badge badge-blue">Clinic ←’ Counselling</span>
                        <?php elseif ($row['direction'] === 'counselling_to_clinic'): ?>
                            <span class="badge badge-purple">Counselling ←’ Clinic</span>
                        <?php else: ?>
                            <span class="badge badge-gray"><?= esc($row['direction']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right;"><strong><?= number_format($row['cnt']) ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
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

    // Daily trend
    const daily = <?= json_encode($dailyTrend) ?>;
    new Chart(document.getElementById('clinicDailyChart'), {
        type: 'line',
        data: {
            labels: daily.map(d => d.day),
            datasets: [{
                label: 'Consultations',
                data: daily.map(d => Number(d.cnt)),
                borderColor: palette[0],
                backgroundColor: 'rgba(99,102,241,0.12)',
                fill: true,
                tension: 0.3,
                pointRadius: 3,
                pointBackgroundColor: palette[0],
            }]
        },
        options: { ...baseOpts, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    // Triage breakdown (pie)
    const triage = <?= json_encode($triageBreakdown) ?>;
    new Chart(document.getElementById('clinicTriageChart'), {
        type: 'doughnut',
        data: {
            labels: triage.map(t => t.priority || 'unset'),
            datasets: [{ data: triage.map(t => Number(t.cnt)), backgroundColor: palette }]
        },
        options: baseOpts
    });

    // Status breakdown (bar)
    const status = <?= json_encode($statusBreakdown) ?>;
    new Chart(document.getElementById('clinicStatusChart'), {
        type: 'bar',
        data: {
            labels: status.map(s => s.status),
            datasets: [{
                label: 'Consultations',
                data: status.map(s => Number(s.cnt)),
                backgroundColor: palette[1],
                borderRadius: 4,
            }]
        },
        options: { ...baseOpts, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
})();
</script>
<?= $this->endSection() ?>