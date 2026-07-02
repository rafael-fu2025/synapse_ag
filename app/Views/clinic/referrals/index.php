<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Filters -->
<div class="card" style="margin-bottom: 1.25rem;">
    <div class="card-body" style="padding: 0.75rem 1.25rem;">
        <form method="GET" action="/clinic/referrals" style="display: flex; gap: 0.75rem; align-items: center;">
            <select name="status" data-synapse-dropdown class="syn-select--sm">
                <option value="">All Statuses</option>
                <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="accepted" <?= ($filters['status'] ?? '') === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                <option value="declined" <?= ($filters['status'] ?? '') === 'declined' ? 'selected' : '' ?>>Declined</option>
                <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
            <select name="direction" data-synapse-dropdown class="syn-select--sm">
                <option value="">All Directions</option>
                <option value="clinic_to_counselling" <?= ($filters['direction'] ?? '') === 'clinic_to_counselling' ? 'selected' : '' ?>>Clinic â†’ Counselling</option>
                <option value="counselling_to_clinic" <?= ($filters['direction'] ?? '') === 'counselling_to_clinic' ? 'selected' : '' ?>>Counselling â†’ Clinic</option>
            </select>
            <button type="submit" style="padding: 0.45rem 0.75rem; background: var(--primary-600); color: white; border: none; border-radius: 0.375rem; font-size: 0.8rem; cursor: pointer;">Filter</button>
            <a href="/clinic/referrals" style="font-size: 0.8rem; color: #6B7280; text-decoration: none;">Clear</a>
        </form>
    </div>
</div>

<!-- Referrals Table -->
<div class="card">
    <div class="card-body" style="padding: 0;">
        <?php if (empty($referrals)): ?>
            <div style="padding: 3rem; text-align: center; color: #9CA3AF;">
                <i class="fas fa-arrow-right-arrow-left" style="font-size: 1.5rem; display: block; margin-bottom: 0.5rem;"></i>
                No referrals found.
            </div>
        <?php else: ?>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                <thead>
                    <tr style="background: #F9FAFB; border-bottom: 1px solid #E5E7EB;">
                        <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Date</th>
                        <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Student</th>
                        <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Direction</th>
                        <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Reason</th>
                        <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Priority</th>
                        <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Status</th>
                        <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Referred By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($referrals as $r): ?>
                        <tr style="border-bottom: 1px solid #F3F4F6;">
                            <td style="padding: 0.6rem 1rem; white-space: nowrap;"><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                            <td style="padding: 0.6rem 1rem;">
                                <a href="/clinic/students/<?= $r['student_id'] ?? '' ?>" style="color: var(--primary-600); text-decoration: none; font-weight: 500;">
                                    <?= esc(($r['student_first'] ?? '') . ' ' . ($r['student_last'] ?? '')) ?>
                                </a>
                            </td>
                            <td style="padding: 0.6rem 1rem; font-size: 0.75rem;"><?= ucfirst(str_replace('_', ' â†’ ', $r['direction'])) ?></td>
                            <td style="padding: 0.6rem 1rem; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= esc($r['reason']) ?></td>
                            <td style="padding: 0.6rem 1rem;">
                                <span style="padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; <?php
                                    echo match($r['priority']) {
                                        'emergency' => 'background: #FEF2F2; color: #DC2626;',
                                        'urgent'    => 'background: #FFF7ED; color: #EA580C;',
                                        default     => 'background: #ECFDF5; color: #059669;',
                                    };
                                ?>"><?= ucfirst($r['priority']) ?></span>
                            </td>
                            <td style="padding: 0.6rem 1rem;">
                                <span style="padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; <?php
                                    echo match($r['status']) {
                                        'accepted'  => 'background: #ECFDF5; color: #059669;',
                                        'declined'  => 'background: #FEF2F2; color: #DC2626;',
                                        'completed' => 'background: var(--primary-50); color: var(--primary-700);',
                                        default     => 'background: #FFFBEB; color: #D97706;',
                                    };
                                ?>"><?= ucfirst($r['status']) ?></span>
                            </td>
                            <td style="padding: 0.6rem 1rem; font-size: 0.8rem; color: #6B7280;"><?= esc(($r['referrer_first'] ?? '') . ' ' . ($r['referrer_last'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
