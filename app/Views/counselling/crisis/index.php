<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Stats -->
<div class="stats-grid" style="margin-bottom: 1.25rem;">
    <div class="stat-card" style="border-left: 3px solid #DC2626;">
        <div class="stat-icon" style="background: #FEF2F2; color: #DC2626;"><i class="fas fa-bell"></i></div>
        <div class="stat-info"><h3><?= $stats['triggered'] ?></h3><p>Triggered</p></div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #F59E0B;">
        <div class="stat-icon" style="background: #FFFBEB; color: #F59E0B;"><i class="fas fa-eye"></i></div>
        <div class="stat-info"><h3><?= $stats['acknowledged'] ?></h3><p>Acknowledged</p></div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #3B82F6;">
        <div class="stat-icon" style="background: #EFF6FF; color: #3B82F6;"><i class="fas fa-spinner"></i></div>
        <div class="stat-info"><h3><?= $stats['inProgress'] ?></h3><p>In Progress</p></div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #8B5CF6;">
        <div class="stat-icon" style="background: #F5F3FF; color: #8B5CF6;"><i class="fas fa-arrow-up"></i></div>
        <div class="stat-info"><h3><?= $stats['escalated'] ?></h3><p>Escalated</p></div>
    </div>
</div>

<!-- Alert List -->
<div class="card">
    <div class="card-header"><i class="fas fa-shield-exclamation" style="margin-right: 0.5rem; color: #DC2626;"></i> Active Crisis Alerts</div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($alerts)): ?>
            <div style="padding: 3rem; text-align: center; color: #10B981;">
                <i class="fas fa-check-circle" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                <p style="font-weight: 600;">No active crisis alerts.</p>
                <p style="font-size: 0.8rem; color: #6B7280;">All alerts have been resolved.</p>
            </div>
        <?php else: ?>
            <?php foreach ($alerts as $a): ?>
                <?php
                $minutesAgo = (int) ((time() - strtotime($a['created_at'])) / 60);
                $isOverdue = $a['status'] === 'triggered' && $minutesAgo > 30;
                ?>
                <div style="padding: 1rem 1.25rem; border-bottom: 1px solid #F3F4F6; <?= $isOverdue ? 'background: #FEF2F2;' : '' ?>">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.3rem;">
                                <?php if ($isOverdue): ?>
                                    <span style="padding: 0.1rem 0.4rem; background: #DC2626; color: white; border-radius: 999px; font-size: 0.6rem; font-weight: 700; animation: pulse 1.5s infinite;">⏰ OVERDUE</span>
                                <?php endif; ?>
                                <span style="padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; <?php
                                    echo match($a['severity']) {
                                        'critical' => 'background: #FEF2F2; color: #DC2626;',
                                        'high'     => 'background: #FFF7ED; color: #EA580C;',
                                        default    => 'background: #FFFBEB; color: #D97706;',
                                    };
                                ?>"><?= ucfirst($a['severity']) ?></span>
                                <span style="padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; <?php
                                    echo match($a['status']) {
                                        'triggered'    => 'background: #FEF2F2; color: #DC2626;',
                                        'acknowledged' => 'background: #FFFBEB; color: #D97706;',
                                        'in_progress'  => 'background: #EFF6FF; color: #2563EB;',
                                        'escalated'    => 'background: #F5F3FF; color: #7C3AED;',
                                        default        => 'background: #F3F4F6; color: #6B7280;',
                                    };
                                ?>"><?= ucfirst(str_replace('_', ' ', $a['status'])) ?></span>
                            </div>

                            <p style="font-size: 0.85rem; font-weight: 600; color: #111827;">
                                <?= esc($a['student_first'] . ' ' . $a['student_last']) ?>
                                <span style="font-weight: 400; color: #6B7280;">(<?= esc($a['student_number']) ?>)</span>
                            </p>
                            <p style="font-size: 0.75rem; color: #6B7280; margin-top: 0.15rem;">
                                Trigger: <strong><?= ucfirst(str_replace('_', ' ', $a['trigger_source'])) ?></strong>
                                &nbsp;•&nbsp; <?= $minutesAgo ?> min ago
                                <?php if ($a['counsellor_first']): ?>
                                    &nbsp;•&nbsp; Assigned: <?= esc($a['counsellor_first'] . ' ' . $a['counsellor_last']) ?>
                                <?php endif; ?>
                            </p>
                        </div>

                        <!-- Actions -->
                        <div style="display: flex; gap: 0.4rem; flex-shrink: 0;">
                            <?php if ($a['status'] === 'triggered'): ?>
                                <form method="POST" action="/counselling/crisis/acknowledge/<?= $a['id'] ?>" style="display: inline;"><?= csrf_field() ?>
                                    <button type="button"
                                            data-synapse-confirm
                                            data-synapse-confirm-title="Acknowledge this crisis alert?"
                                            data-synapse-confirm-body="You'll be marked as the responding counsellor and the alert will move to Acknowledged state. Other on-call counsellors will be notified you're handling it."
                                            data-synapse-confirm-text="Acknowledge"
                                            style="padding: 0.35rem 0.6rem; background: #F59E0B; color: white; border: none; border-radius: 0.375rem; font-size: 0.7rem; cursor: pointer; font-weight: 600;">
                                        <i class="fas fa-eye"></i> Acknowledge
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if (in_array($a['status'], ['acknowledged', 'in_progress', 'escalated'])): ?>
                                <!-- Inline resolve form — requires notes -->
                                <form method="POST" action="/counselling/crisis/resolve/<?= $a['id'] ?>" style="display: flex; gap: 0.3rem;">
                                    <?= csrf_field() ?>
                                    <input type="text" name="resolution_notes" placeholder="Resolution notes..." required style="padding: 0.35rem 0.5rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.7rem; width: 160px; font-family: 'Inter', sans-serif;">
                                    <button type="submit"
                                            style="padding: 0.35rem 0.6rem; background: #10B981; color: white; border: none; border-radius: 0.375rem; font-size: 0.7rem; cursor: pointer; font-weight: 600;">
                                        <i class="fas fa-check"></i> Resolve
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($a['status'] !== 'escalated' && $a['status'] !== 'resolved'): ?>
                                <form method="POST" action="/counselling/crisis/escalate/<?= $a['id'] ?>" style="display: inline;"><?= csrf_field() ?>
                                    <button type="button"
                                            data-synapse-confirm
                                            data-synapse-confirm-danger
                                            data-synapse-confirm-title="Escalate to senior staff?"
                                            data-synapse-confirm-body="This will alert senior counselling staff and on-call administrators. Only escalate if this situation is beyond what you can handle or requires immediate higher authority."
                                            data-synapse-confirm-text="Escalate"
                                            style="padding: 0.35rem 0.6rem; background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; border-radius: 0.375rem; font-size: 0.7rem; cursor: pointer; font-weight: 500;">
                                        <i class="fas fa-arrow-up"></i> Escalate
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
</style>
<?= $this->endSection() ?>
