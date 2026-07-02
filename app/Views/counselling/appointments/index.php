<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Crisis Alert Banner -->
<?php if (! empty($crisisAlerts)): ?>
<div style="margin-bottom: 1.25rem; padding: 1rem 1.25rem; background: linear-gradient(135deg, #FEF2F2, #FEE2E2); border: 1px solid #FECACA; border-radius: 0.5rem; border-left: 4px solid #DC2626;">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div>
            <p style="font-size: 0.85rem; font-weight: 700; color: #991B1B;">
                <i class="fas fa-bell" style="animation: pulse 1.5s infinite;"></i>
                ðŸš¨ <?= count($crisisAlerts) ?> Unacknowledged Crisis Alert<?= count($crisisAlerts) > 1 ? 's' : '' ?>
            </p>
            <p style="font-size: 0.75rem; color: #B91C1C; margin-top: 0.2rem;">Must acknowledge within 30 minutes per protocol.</p>
        </div>
        <a href="/counselling/crisis" style="padding: 0.4rem 0.75rem; background: #DC2626; color: white; border-radius: 0.375rem; font-size: 0.75rem; text-decoration: none; font-weight: 600;">View Alerts</a>
    </div>
</div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-calendar-day"></i></div>
        <div class="stat-info"><h3><?= $stats['total'] ?></h3><p>Total Today</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
        <div class="stat-info"><h3><?= $stats['scheduled'] ?></h3><p>Scheduled</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info"><h3><?= $stats['completed'] ?></h3><p>Completed</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #FEF2F2; color: #EF4444;"><i class="fas fa-user-xmark"></i></div>
        <div class="stat-info"><h3><?= $stats['noShow'] ?></h3><p>No-Shows</p></div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.25rem;">
    <!-- Schedule Table -->
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-calendar-check" style="margin-right: 0.5rem; color: #8B5CF6;"></i> Today's Schedule</span>
            <a href="/clinic/students" style="padding: 0.3rem 0.6rem; background: #8B5CF6; color: white; border-radius: 0.375rem; font-size: 0.7rem; text-decoration: none; font-weight: 500;">+ Book Appointment</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($schedule)): ?>
                <div style="padding: 2.5rem; text-align: center; color: #9CA3AF;">
                    <i class="fas fa-calendar-xmark" style="font-size: 1.5rem; display: block; margin-bottom: 0.5rem;"></i>
                    <p style="font-size: 0.85rem;">No appointments scheduled today.</p>
                </div>
            <?php else: ?>
                <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                    <thead>
                        <tr style="background: #F9FAFB; border-bottom: 1px solid #E5E7EB;">
                            <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Time</th>
                            <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Student</th>
                            <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Type</th>
                            <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Status</th>
                            <th style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #374151;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedule as $a): ?>
                            <tr style="border-bottom: 1px solid #F3F4F6;">
                                <td style="padding: 0.6rem 1rem; font-weight: 500; white-space: nowrap;"><?= date('h:i A', strtotime($a['start_time'])) ?> â€“ <?= date('h:i A', strtotime($a['end_time'])) ?></td>
                                <td style="padding: 0.6rem 1rem;">
                                    <div style="font-weight: 500;"><?= esc($a['student_first'] . ' ' . $a['student_last']) ?></div>
                                    <div style="font-size: 0.7rem; color: #6B7280;"><?= esc($a['student_number']) ?></div>
                                </td>
                                <td style="padding: 0.6rem 1rem;">
                                    <span style="padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; background: #F3F4F6; color: #374151; text-transform: capitalize;"><?= str_replace('_', ' ', $a['type']) ?></span>
                                </td>
                                <td style="padding: 0.6rem 1rem;">
                                    <span style="padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; <?php
                                        echo match($a['status']) {
                                            'completed'  => 'background: #ECFDF5; color: #059669;',
                                            'confirmed'  => 'background: #EFF6FF; color: #2563EB;',
                                            'cancelled'  => 'background: #F3F4F6; color: #6B7280;',
                                            'no_show'    => 'background: #FEF2F2; color: #DC2626;',
                                            default      => 'background: #FFFBEB; color: #D97706;',
                                        };
                                    ?>"><?= ucfirst(str_replace('_', ' ', $a['status'])) ?></span>
                                </td>
                                <td style="padding: 0.6rem 1rem; text-align: center;">
                                    <a href="/counselling/appointments/<?= $a['id'] ?>" style="padding: 0.2rem 0.4rem; background: var(--primary-50); color: var(--primary-600); border-radius: 0.375rem; font-size: 0.65rem; text-decoration: none; font-weight: 500;">View</a>
                                    <?php if ($a['status'] === 'scheduled'): ?>
                                        <form method="POST" action="/counselling/appointments/start/<?= $a['id'] ?>" style="display: inline;"><?= csrf_field() ?>
                                            <button type="button"
                                                    data-synapse-confirm
                                                    data-synapse-confirm-title="Start session with <?= esc($a['student_first'] . ' ' . $a['student_last']) ?>?"
                                                    data-synapse-confirm-text="Start Session"
                                                    style="padding: 0.2rem 0.4rem; background: #ECFDF5; color: #059669; border: none; border-radius: 0.375rem; font-size: 0.65rem; cursor: pointer; font-weight: 500;">Start</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar: Pending Referrals -->
    <div>
        <div class="card">
            <div class="card-header"><i class="fas fa-arrow-right-arrow-left" style="margin-right: 0.5rem; color: #F59E0B;"></i> Incoming Referrals</div>
            <div class="card-body">
                <?php if (empty($pendingReferrals)): ?>
                    <p style="font-size: 0.8rem; color: #9CA3AF; text-align: center;">No pending referrals.</p>
                <?php else: ?>
                    <?php foreach (array_slice($pendingReferrals, 0, 5) as $r): ?>
                        <div style="padding: 0.6rem 0; border-bottom: 1px solid #F3F4F6;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <p style="font-size: 0.8rem; font-weight: 500;"><?= esc($r['student_first'] . ' ' . $r['student_last']) ?></p>
                                    <p style="font-size: 0.7rem; color: #6B7280; margin-top: 0.15rem; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= esc($r['reason']) ?></p>
                                </div>
                                <span style="padding: 0.15rem 0.4rem; border-radius: 999px; font-size: 0.6rem; font-weight: 600; <?php
                                    echo match($r['priority']) {
                                        'emergency' => 'background: #FEF2F2; color: #DC2626;',
                                        'urgent'    => 'background: #FFF7ED; color: #EA580C;',
                                        default     => 'background: #ECFDF5; color: #059669;',
                                    };
                                ?>"><?= ucfirst($r['priority']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <a href="/counselling/referrals" style="display: block; text-align: center; margin-top: 0.75rem; font-size: 0.75rem; color: var(--primary-600); text-decoration: none;">View All Referrals â†’</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
