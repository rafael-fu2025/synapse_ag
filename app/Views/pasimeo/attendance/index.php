<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <span><i class="fas fa-clipboard-check" style="margin-right: 0.5rem; color: #8B5CF6;"></i> <?= esc($activity['title']) ?> — Attendance</span>
        <div style="display: flex; gap: 0.4rem;">
            <form method="POST" action="/pasimeo/attendance/verify-all/<?= $activity['id'] ?>" style="display: inline;"><?= csrf_field() ?>
                <button type="button"
                        data-synapse-confirm
                        data-synapse-confirm-title="Verify all unverified attendance?"
                        data-synapse-confirm-body="This will mark every unverified attendance record as verified by you. Make sure you've reviewed each row first — this action can't be undone."
                        data-synapse-confirm-text="Verify All"
                        style="padding: 0.3rem 0.6rem; background: #10B981; color: white; border: none; border-radius: 0.375rem; font-size: 0.65rem; cursor: pointer; font-weight: 500;"><i class="fas fa-check-double"></i> Verify All</button>
            </form>
            <a href="/pasimeo/activities/<?= $activity['id'] ?>" style="padding: 0.3rem 0.6rem; background: #F3F4F6; color: #374151; border-radius: 0.375rem; font-size: 0.65rem; text-decoration: none;">← Back</a>
        </div>
    </div>
    <div class="card-body">
        <!-- Manual Check-In Form -->
        <div style="margin-bottom: 1.25rem; padding: 1rem; background: #F5F3FF; border: 1px solid #DDD6FE; border-radius: 0.5rem;">
            <p style="font-size: 0.8rem; font-weight: 600; color: #5B21B6; margin-bottom: 0.5rem;"><i class="fas fa-user-check"></i> Manual Check-In</p>
            <form method="POST" action="/pasimeo/attendance/check-in" style="display: flex; gap: 0.5rem; align-items: flex-end;">
                <?= csrf_field() ?>
                <input type="hidden" name="activity_id" value="<?= $activity['id'] ?>">
                <div style="flex: 1;">
                    <label style="font-size: 0.7rem; color: #6B7280;">Volunteer</label>
                    <select name="user_id" required data-synapse-dropdown class="syn-select--sm">
                        <option value="">Select volunteer...</option>
                        <?php foreach ($activity['volunteers'] as $v): ?>
                            <?php if ($v['status'] !== 'declined'): ?>
                                <option value="<?= $v['user_id'] ?>"><?= esc($v['first_name'] . ' ' . $v['last_name']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" style="padding: 0.4rem 0.75rem; background: #8B5CF6; color: white; border: none; border-radius: 0.375rem; font-size: 0.8rem; cursor: pointer; font-weight: 600;">Check In</button>
            </form>
        </div>

        <!-- Attendance Table -->
        <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
            <thead>
                <tr style="background: #F9FAFB; border-bottom: 1px solid #E5E7EB;">
                    <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Volunteer</th>
                    <th style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #374151;">Check In</th>
                    <th style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #374151;">Check Out</th>
                    <th style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #374151;">Hours</th>
                    <th style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #374151;">Method</th>
                    <th style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #374151;">Verified</th>
                    <th style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #374151;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($activity['attendance'])): ?>
                    <tr><td colspan="7" style="padding: 2rem; text-align: center; color: #9CA3AF;">No attendance records yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($activity['attendance'] as $a): ?>
                        <tr style="border-bottom: 1px solid #F3F4F6;">
                            <td style="padding: 0.6rem 1rem; font-weight: 500;"><?= esc($a['first_name'] . ' ' . $a['last_name']) ?></td>
                            <td style="padding: 0.6rem 1rem; text-align: center; font-size: 0.75rem;"><?= $a['check_in_time'] ? date('h:i A', strtotime($a['check_in_time'])) : '—' ?></td>
                            <td style="padding: 0.6rem 1rem; text-align: center; font-size: 0.75rem;"><?= $a['check_out_time'] ? date('h:i A', strtotime($a['check_out_time'])) : '—' ?></td>
                            <td style="padding: 0.6rem 1rem; text-align: center; font-weight: 700; color: #10B981;"><?= $a['hours_credited'] ? number_format($a['hours_credited'], 1) . 'h' : '—' ?></td>
                            <td style="padding: 0.6rem 1rem; text-align: center;">
                                <span style="padding: 0.1rem 0.4rem; border-radius: 999px; font-size: 0.6rem; font-weight: 600; <?php
                                    echo match($a['check_in_method']) {
                                        'qr'     => 'background: #ECFDF5; color: #059669;',
                                        'rfid'   => 'background: #EFF6FF; color: #2563EB;',
                                        default  => 'background: #F3F4F6; color: #6B7280;',
                                    };
                                ?>"><?= strtoupper($a['check_in_method']) ?></span>
                            </td>
                            <td style="padding: 0.6rem 1rem; text-align: center;">
                                <?php if ($a['verified_by']): ?>
                                    <span style="color: #10B981;" title="Verified by <?= esc($a['verifier_first'] . ' ' . $a['verifier_last']) ?>"><i class="fas fa-check-circle"></i></span>
                                <?php else: ?>
                                    <span style="color: #D97706;"><i class="fas fa-clock"></i></span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.6rem 1rem; text-align: center; display: flex; gap: 0.2rem; justify-content: center;">
                                <?php if ($a['check_in_time'] && ! $a['check_out_time']): ?>
                                    <form method="POST" action="/pasimeo/attendance/check-out/<?= $a['id'] ?>" style="display: inline;"><?= csrf_field() ?>
                                        <button type="button"
                                                data-synapse-confirm
                                                data-synapse-confirm-title="Check out <?= esc($a['first_name'] . ' ' . $a['last_name']) ?>?"
                                                data-synapse-confirm-body="This will record their check-out time and credit hours worked. Make sure the volunteer is actually leaving."
                                                data-synapse-confirm-text="Check Out"
                                                style="padding: 0.2rem 0.4rem; background: #EFF6FF; color: #2563EB; border: none; border-radius: 0.375rem; font-size: 0.6rem; cursor: pointer; font-weight: 500;">Out</button>
                                    </form>
                                <?php endif; ?>
                                <?php if (! $a['verified_by']): ?>
                                    <form method="POST" action="/pasimeo/attendance/verify/<?= $a['id'] ?>" style="display: inline;"><?= csrf_field() ?>
                                        <button type="button"
                                                data-synapse-confirm
                                                data-synapse-confirm-title="Verify this attendance record?"
                                                data-synapse-confirm-body="Once verified, this attendance will be locked and the volunteer's hours will be officially credited."
                                                data-synapse-confirm-text="Verify"
                                                style="padding: 0.2rem 0.4rem; background: #ECFDF5; color: #059669; border: none; border-radius: 0.375rem; font-size: 0.6rem; cursor: pointer; font-weight: 500;">✓ Verify</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
