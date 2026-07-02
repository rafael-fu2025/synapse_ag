<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
    <!-- Left: Appointment Info -->
    <div>
        <div class="card">
            <div class="card-header"><i class="fas fa-calendar-check" style="margin-right: 0.5rem; color: #8B5CF6;"></i> Appointment Details</div>
            <div class="card-body">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                    <div style="width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #8B5CF6, #A78BFA); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; flex-shrink: 0;">
                        <?= strtoupper(substr($appt['student_first'], 0, 1)) ?><?= strtoupper(substr($appt['student_last'], 0, 1)) ?>
                    </div>
                    <div>
                        <p style="font-weight: 600;"><?= esc($appt['student_first'] . ' ' . $appt['student_last']) ?></p>
                        <p style="font-size: 0.75rem; color: #6B7280;"><?= esc($appt['student_number']) ?></p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; font-size: 0.8rem;">
                    <div><span style="color: #9CA3AF;">Date:</span> <?= date('l, M d, Y', strtotime($appt['appointment_date'])) ?></div>
                    <div><span style="color: #9CA3AF;">Time:</span> <?= date('h:i A', strtotime($appt['start_time'])) ?> â€“ <?= date('h:i A', strtotime($appt['end_time'])) ?></div>
                    <div><span style="color: #9CA3AF;">Type:</span> <span style="text-transform: capitalize;"><?= str_replace('_', ' ', $appt['type']) ?></span></div>
                    <div><span style="color: #9CA3AF;">Status:</span>
                        <span style="padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; <?php
                            echo match($appt['status']) {
                                'completed'  => 'background: #ECFDF5; color: #059669;',
                                'confirmed'  => 'background: #EFF6FF; color: #2563EB;',
                                'cancelled'  => 'background: #F3F4F6; color: #6B7280;',
                                'no_show'    => 'background: #FEF2F2; color: #DC2626;',
                                default      => 'background: #FFFBEB; color: #D97706;',
                            };
                        ?>"><?= ucfirst(str_replace('_', ' ', $appt['status'])) ?></span>
                    </div>
                    <div><span style="color: #9CA3AF;">Counsellor:</span> <?= esc($appt['counsellor_first'] . ' ' . $appt['counsellor_last']) ?></div>
                </div>

                <?php if (isset($appt['no_show_probability']) && $appt['no_show_probability'] !== null): ?>
                    <?php 
                        $prob = (float) $appt['no_show_probability'];
                        $attendanceProb = 1 - $prob;
                        $color = $prob >= 0.5 ? '#DC2626' : ($prob >= 0.3 ? '#D97706' : '#059669');
                        $bg = $prob >= 0.5 ? '#FEF2F2' : ($prob >= 0.3 ? '#FFFBEB' : '#ECFDF5');
                        $border = $prob >= 0.5 ? '#FECACA' : ($prob >= 0.3 ? '#FDE68A' : '#A7F3D0');
                    ?>
                    <div style="margin-top: 1rem; padding: 0.75rem; background: <?= $bg ?>; border: 1px solid <?= $border ?>; border-radius: 0.375rem; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <span style="font-size: 0.7rem; font-weight: 700; color: <?= $color ?>;"><i class="fas fa-robot"></i> AI Scheduling Insight</span>
                            <p style="margin: 0.25rem 0 0 0; font-size: 0.8rem; color: #374151;">Predicted Attendance Probability</p>
                        </div>
                        <div style="text-align: right;">
                            <span style="font-size: 1.25rem; font-weight: 800; color: <?= $color ?>;"><?= round($attendanceProb * 100) ?>%</span>
                            <?php if ($prob >= 0.3): ?>
                                <p style="margin: 0; font-size: 0.65rem; color: #6B7280;">High No-Show Risk</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($appt['reason']): ?>
                    <div style="margin-top: 0.75rem; padding: 0.6rem; background: #F9FAFB; border-radius: 0.375rem;">
                        <p style="font-size: 0.7rem; color: #9CA3AF;">Reason:</p>
                        <p style="font-size: 0.8rem;"><?= esc($appt['reason']) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Session Notes -->
        <?php if ($appt['status'] === 'confirmed' || $appt['status'] === 'completed'): ?>
        <div class="card" style="margin-top: 1.25rem;">
            <div class="card-header"><i class="fas fa-pencil" style="margin-right: 0.5rem; color: #10B981;"></i> Session Notes</div>
            <div class="card-body">
                <?php if ($appt['status'] === 'confirmed'): ?>
                    <form method="POST" action="/counselling/appointments/complete/<?= $appt['id'] ?>">
                        <?= csrf_field() ?>
                        <textarea name="session_notes" rows="5" required placeholder="Write session notes (encrypted, counsellor-access only)..." style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif; resize: vertical;"><?= esc($appt['session_notes'] ?? '') ?></textarea>
                        <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 0.75rem;">
                            <a href="/counselling/screenings/index?student_id=<?= $appt['student_id'] ?>&appointment_id=<?= $appt['id'] ?>" style="padding: 0.5rem 0.75rem; background: var(--primary-50); color: var(--primary-600); border-radius: 0.375rem; font-size: 0.8rem; text-decoration: none; font-weight: 500;"><i class="fas fa-clipboard-list"></i> Administer Screening</a>
                            <button type="submit"
                                    data-synapse-confirm
                                    data-synapse-confirm-title="Complete this session?"
                                    data-synapse-confirm-body="This will mark the session as completed and lock the notes. You'll be able to view them but not edit further."
                                    data-synapse-confirm-text="Complete Session"
                                    style="padding: 0.5rem 1rem; background: #10B981; color: white; border: none; border-radius: 0.375rem; font-size: 0.8rem; font-weight: 600; cursor: pointer;">
                                <i class="fas fa-check"></i> Complete Session
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <p style="font-size: 0.85rem; white-space: pre-wrap;"><?= esc($appt['session_notes'] ?? 'No notes recorded.') ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <?php if ($appt['status'] === 'scheduled'): ?>
        <div class="card" style="margin-top: 1.25rem;">
            <div class="card-body" style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <form method="POST" action="/counselling/appointments/no-show/<?= $appt['id'] ?>" style="display: inline;"><?= csrf_field() ?>
                    <button type="button"
                            data-synapse-confirm
                            data-synapse-confirm-danger
                            data-synapse-confirm-title="Mark student as no-show?"
                            data-synapse-confirm-body="This will flag the appointment as no-show and count against the student's attendance record. Use only when the student truly did not arrive."
                            data-synapse-confirm-text="Mark No-Show"
                            style="padding: 0.5rem 1rem; background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; border-radius: 0.375rem; font-size: 0.8rem; font-weight: 500; cursor: pointer;">
                        <i class="fas fa-user-xmark"></i> No-Show
                    </button>
                </form>
                <form method="POST" action="/counselling/appointments/start/<?= $appt['id'] ?>" style="display: inline;"><?= csrf_field() ?>
                    <button type="button"
                            data-synapse-confirm
                            data-synapse-confirm-title="Start this session?"
                            data-synapse-confirm-body="Once started, you'll be able to record session notes and end the visit when complete."
                            data-synapse-confirm-text="Start Session"
                            style="padding: 0.5rem 1rem; background: #8B5CF6; color: white; border: none; border-radius: 0.375rem; font-size: 0.8rem; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-play"></i> Start Session
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right: Screening History + Referrals -->
    <div>
        <?php
        // Group and format screening history for Chart.js
        $chartData = [];
        if (!empty($appt['screening_history'])) {
            // Reverse array to show chronological order (oldest to newest)
            $chronologicalHistory = array_reverse($appt['screening_history']);
            foreach ($chronologicalHistory as $s) {
                $type = (stripos($s['template_title'], 'PHQ-9') !== false) ? 'PHQ-9' : ((stripos($s['template_title'], 'GAD-7') !== false) ? 'GAD-7' : 'Other');
                $chartData[] = [
                    'type'  => $type,
                    'score' => (int) ($s['total_score'] ?? 0),
                    'date'  => date('M d', strtotime($s['submitted_at'])),
                ];
            }
        }
        ?>
        <div class="card">
            <div class="card-header"><i class="fas fa-chart-line" style="margin-right: 0.5rem; color: #3B82F6;"></i> Screening History</div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($appt['screening_history'])): ?>
                    <div style="padding: 1.5rem; text-align: center; color: #9CA3AF;">No screenings on file.</div>
                <?php else: ?>
                    <div style="padding: 0.75rem; border-bottom: 1px solid #F3F4F6;">
                        <canvas id="screening-trend-chart" style="width: 100%; height: 180px;"></canvas>
                    </div>
                    <?php foreach ($appt['screening_history'] as $s): ?>
                        <div style="padding: 0.6rem 1rem; border-bottom: 1px solid #F3F4F6; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <p style="font-size: 0.8rem; font-weight: 500;"><?= esc($s['template_title']) ?></p>
                                <p style="font-size: 0.7rem; color: #6B7280;"><?= date('M d, Y', strtotime($s['submitted_at'])) ?></p>
                            </div>
                            <div style="text-align: right;">
                                <p style="font-size: 1.1rem; font-weight: 700; color: <?= ($s['total_score'] ?? 0) >= 10 ? '#DC2626' : '#059669' ?>;"><?= $s['total_score'] ?? 'â€”' ?></p>
                                <p style="font-size: 0.6rem; color: #9CA3AF;">Score</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($appt['screening_history'])): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const rawData = <?= json_encode($chartData) ?>;
            const ctx = document.getElementById('screening-trend-chart');
            if (!ctx) return;

            // Filter datasets
            const phq9Data = rawData.filter(d => d.type === 'PHQ-9');
            const gad7Data = rawData.filter(d => d.type === 'GAD-7');
            
            // Union of unique dates for labels
            const labels = Array.from(new Set(rawData.map(d => d.date)));

            // Map scores to chronological label positions
            const phq9Scores = labels.map(label => {
                const match = phq9Data.find(d => d.date === label);
                return match ? match.score : null;
            });

            const gad7Scores = labels.map(label => {
                const match = gad7Data.find(d => d.date === label);
                return match ? match.score : null;
            });

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'PHQ-9 (Depression)',
                            data: phq9Scores,
                            borderColor: 'var(--primary-600)',
                            backgroundColor: 'rgba(79, 70, 229, 0.1)',
                            tension: 0.3,
                            spanGaps: true,
                            borderWidth: 2,
                            pointRadius: 4
                        },
                        {
                            label: 'GAD-7 (Anxiety)',
                            data: gad7Scores,
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.3,
                            spanGaps: true,
                            borderWidth: 2,
                            pointRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            min: 0,
                            max: 27,
                            ticks: {
                                stepSize: 5,
                                font: { size: 9 }
                            },
                            grid: { color: '#F3F4F6' }
                        },
                        x: {
                            ticks: { font: { size: 9 } },
                            grid: { display: false }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { boxWidth: 10, font: { size: 9 } }
                        }
                    }
                }
            });
        });
        </script>
        <?php endif; ?>

        <div class="card" style="margin-top: 1.25rem;">
            <div class="card-header"><i class="fas fa-arrow-right-arrow-left" style="margin-right: 0.5rem; color: #F59E0B;"></i> Referral History</div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($appt['referrals'])): ?>
                    <div style="padding: 1.5rem; text-align: center; color: #9CA3AF;">No referrals.</div>
                <?php else: ?>
                    <?php foreach ($appt['referrals'] as $r): ?>
                        <div style="padding: 0.6rem 1rem; border-bottom: 1px solid #F3F4F6;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="font-size: 0.75rem; text-transform: capitalize;"><?= str_replace('_', ' â†’ ', $r['direction']) ?></span>
                                <span style="padding: 0.1rem 0.35rem; border-radius: 999px; font-size: 0.6rem; font-weight: 600; <?php
                                    echo match($r['status']) {
                                        'accepted' => 'background: #ECFDF5; color: #059669;',
                                        'declined' => 'background: #FEF2F2; color: #DC2626;',
                                        default    => 'background: #FFFBEB; color: #D97706;',
                                    };
                                ?>"><?= ucfirst($r['status']) ?></span>
                            </div>
                            <p style="font-size: 0.7rem; color: #6B7280; margin-top: 0.15rem;"><?= esc($r['reason']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
