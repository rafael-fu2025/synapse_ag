<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header"><i class="fas fa-arrow-right-arrow-left" style="margin-right: 0.5rem; color: #F59E0B;"></i> Incoming Referrals from Clinic</div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($referrals)): ?>
            <div style="padding: 3rem; text-align: center; color: #9CA3AF;">
                <i class="fas fa-inbox" style="font-size: 1.5rem; display: block; margin-bottom: 0.5rem;"></i>
                No incoming referrals.
            </div>
        <?php else: ?>
            <?php foreach ($referrals as $r): ?>
                <div style="padding: 1rem 1.25rem; border-bottom: 1px solid #F3F4F6;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                <span style="font-size: 0.85rem; font-weight: 600; color: #111827;">
                                    <?= esc(($r['student_first'] ?? '') . ' ' . ($r['student_last'] ?? '')) ?>
                                </span>
                                <span style="padding: 0.1rem 0.4rem; border-radius: 999px; font-size: 0.6rem; font-weight: 600; <?php
                                    echo match($r['priority']) {
                                        'emergency' => 'background: #FEF2F2; color: #DC2626;',
                                        'urgent'    => 'background: #FFF7ED; color: #EA580C;',
                                        default     => 'background: #ECFDF5; color: #059669;',
                                    };
                                ?>"><?= ucfirst($r['priority']) ?></span>
                                <span style="padding: 0.1rem 0.4rem; border-radius: 999px; font-size: 0.6rem; font-weight: 600; <?php
                                    echo match($r['status']) {
                                        'accepted'    => 'background: #ECFDF5; color: #059669;',
                                        'declined'    => 'background: #FEF2F2; color: #DC2626;',
                                        'in_progress' => 'background: #EFF6FF; color: #2563EB;',
                                        'completed'   => 'background: #F3F4F6; color: #6B7280;',
                                        default       => 'background: #FFFBEB; color: #D97706;',
                                    };
                                ?>"><?= ucfirst(str_replace('_', ' ', $r['status'])) ?></span>
                            </div>
                            <p style="font-size: 0.8rem; color: #374151;"><?= esc($r['reason']) ?></p>
                            <p style="font-size: 0.7rem; color: #9CA3AF; margin-top: 0.15rem;">
                                Referred by: <?= esc(($r['referrer_first'] ?? '') . ' ' . ($r['referrer_last'] ?? '')) ?>
                                &nbsp;â€¢&nbsp; <?= date('M d, Y', strtotime($r['created_at'])) ?>
                            </p>
                        </div>

                        <?php if ($r['status'] === 'pending'): ?>
                            <div style="display: flex; gap: 0.3rem; flex-shrink: 0;">
                                <form method="POST" action="/counselling/referrals/accept/<?= $r['id'] ?>" style="display: inline;"><?= csrf_field() ?>
                                    <button type="button"
                                            data-synapse-confirm
                                            data-synapse-confirm-title="Accept this referral?"
                                            data-synapse-confirm-body="You'll be marked as the assigned counsellor and the referral status will change to Accepted."
                                            data-synapse-confirm-text="Accept"
                                            style="padding: 0.35rem 0.6rem; background: #10B981; color: white; border: none; border-radius: 0.375rem; font-size: 0.7rem; cursor: pointer; font-weight: 600;">Accept</button>
                                </form>
                                <form method="POST" action="/counselling/referrals/decline/<?= $r['id'] ?>" style="display: inline;"><?= csrf_field() ?>
                                    <button type="button"
                                            data-synapse-confirm
                                            data-synapse-confirm-danger
                                            data-synapse-confirm-title="Decline this referral?"
                                            data-synapse-confirm-body="The clinic will be notified that this referral was declined. Use this only if you genuinely can't take the case — declining without reason can delay student care."
                                            data-synapse-confirm-text="Decline"
                                            style="padding: 0.35rem 0.6rem; background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; border-radius: 0.375rem; font-size: 0.7rem; cursor: pointer; font-weight: 500;">Decline</button>
                                </form>
                                <a href="/counselling/appointments/create/<?= $r['student_id'] ?? '' ?>"
                                   data-synapse-form-link
                                   data-dialog-title="Book Appointment for <?= esc(($r['student_first'] ?? '') . ' ' . ($r['student_last'] ?? '')) ?>"
                                   data-dialog-icon="fas fa-calendar-plus"
                                   data-dialog-width
                                   style="padding: 0.35rem 0.6rem; background: var(--primary-50); color: var(--primary-600); border-radius: 0.375rem; font-size: 0.7rem; text-decoration: none; font-weight: 500;">Book</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
