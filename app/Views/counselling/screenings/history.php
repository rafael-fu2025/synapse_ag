<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <span><i class="fas fa-chart-line" style="margin-right: 0.5rem; color: #3B82F6;"></i> Screening History: <?= esc($student['full_name']) ?></span>
        <a href="/counselling/screenings" style="padding: 0.3rem 0.6rem; background: #F3F4F6; color: #374151; border-radius: 0.375rem; font-size: 0.7rem; text-decoration: none;">â† Back</a>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($responses)): ?>
            <div style="padding: 3rem; text-align: center; color: #9CA3AF;">
                <i class="fas fa-clipboard" style="font-size: 1.5rem; display: block; margin-bottom: 0.5rem;"></i>
                No screening records found for this student.
            </div>
        <?php else: ?>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                <thead>
                    <tr style="background: #F9FAFB; border-bottom: 1px solid #E5E7EB;">
                        <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Date</th>
                        <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Assessment</th>
                        <th style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #374151;">Score</th>
                        <th style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #374151;">Severity</th>
                        <th style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #374151;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($responses as $r): ?>
                        <?php
                        $score = (int)($r['total_score'] ?? 0);
                        $isPHQ9 = stripos($r['template_title'] ?? '', 'PHQ-9') !== false;
                        $isGAD7 = stripos($r['template_title'] ?? '', 'GAD-7') !== false;
                        $severity = null;
                        if ($isPHQ9) $severity = \App\Models\AssessmentResponseModel::getPHQ9Severity($score);
                        elseif ($isGAD7) $severity = \App\Models\AssessmentResponseModel::getGAD7Severity($score);
                        ?>
                        <tr style="border-bottom: 1px solid #F3F4F6;">
                            <td style="padding: 0.6rem 1rem; white-space: nowrap;"><?= date('M d, Y', strtotime($r['submitted_at'])) ?></td>
                            <td style="padding: 0.6rem 1rem; font-weight: 500;"><?= esc($r['template_title']) ?></td>
                            <td style="padding: 0.6rem 1rem; text-align: center; font-weight: 700; font-size: 1rem; color: <?= $severity ? $severity['color'] : '#374151' ?>;"><?= $score ?></td>
                            <td style="padding: 0.6rem 1rem; text-align: center;">
                                <?php if ($severity): ?>
                                    <span style="padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; color: white; background: <?= $severity['color'] ?>;"><?= $severity['label'] ?></span>
                                <?php else: ?>
                                    <span style="color: #9CA3AF;">â€”</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.6rem 1rem; text-align: center;">
                                <a href="/counselling/screenings/results/<?= $r['id'] ?>" style="padding: 0.25rem 0.5rem; background: var(--primary-50); color: var(--primary-600); border-radius: 0.375rem; font-size: 0.7rem; text-decoration: none; font-weight: 500;">Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
