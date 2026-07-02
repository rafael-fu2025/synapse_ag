<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
    <!-- Score Card -->
    <div class="card">
        <div class="card-header"><i class="fas fa-chart-pie" style="margin-right: 0.5rem; color: #8B5CF6;"></i> Screening Results</div>
        <div class="card-body" style="text-align: center;">
            <p style="font-size: 0.8rem; color: #6B7280; margin-bottom: 0.5rem;"><?= esc($response['template_title']) ?></p>

            <!-- Score Gauge -->
            <div style="position: relative; width: 120px; height: 120px; margin: 1rem auto; border-radius: 50%; background: conic-gradient(<?= $severity['color'] ?? '#6B7280' ?> <?= min(100, ($response['total_score'] / ($response['template_title'] === 'PHQ-9' ? 27 : 21)) * 100) ?>%, #F3F4F6 0%); display: flex; align-items: center; justify-content: center;">
                <div style="width: 90px; height: 90px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                    <span style="font-size: 2rem; font-weight: 800; color: <?= $severity['color'] ?? '#374151' ?>;"><?= $response['total_score'] ?></span>
                    <span style="font-size: 0.6rem; color: #9CA3AF;">score</span>
                </div>
            </div>

            <?php if ($severity): ?>
                <div style="display: inline-block; padding: 0.3rem 1rem; border-radius: 999px; font-size: 0.8rem; font-weight: 700; color: white; background: <?= $severity['color'] ?>;">
                    <?= $severity['label'] ?>
                </div>
            <?php endif; ?>

            <div style="margin-top: 1rem; font-size: 0.75rem; color: #6B7280;">
                Submitted: <?= date('M d, Y h:i A', strtotime($response['submitted_at'])) ?>
            </div>

            <?php if ($response['student']): ?>
                <div style="margin-top: 0.75rem; padding: 0.5rem; background: #F9FAFB; border-radius: 0.375rem;">
                    <p style="font-size: 0.8rem; font-weight: 500;"><?= esc($response['student']['full_name']) ?></p>
                    <p style="font-size: 0.7rem; color: #6B7280;"><?= esc($response['student']['student_number']) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detailed Responses -->
    <div class="card">
        <div class="card-header"><i class="fas fa-list-ol" style="margin-right: 0.5rem; color: #3B82F6;"></i> Detailed Responses</div>
        <div class="card-body" style="padding: 0;">
            <?php foreach ($response['questions'] as $i => $q): ?>
                <?php $answer = $response['responses'][$q['id']] ?? 'â€”'; ?>
                <div style="padding: 0.6rem 1rem; border-bottom: 1px solid #F3F4F6; display: flex; justify-content: space-between; align-items: center; gap: 0.75rem;">
                    <p style="font-size: 0.8rem; color: #374151; flex: 1;">
                        <span style="color: #8B5CF6; font-weight: 700;"><?= $i + 1 ?>.</span> <?= esc($q['question_text']) ?>
                    </p>
                    <span style="flex-shrink: 0; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: 700; font-size: 0.85rem; <?php
                        $val = (int) $answer;
                        if ($val === 0)      echo 'background: #ECFDF5; color: #059669;';
                        elseif ($val === 1)   echo 'background: #FFFBEB; color: #D97706;';
                        elseif ($val === 2)   echo 'background: #FFF7ED; color: #EA580C;';
                        else                  echo 'background: #FEF2F2; color: #DC2626;';
                    ?>"><?= is_numeric($answer) ? $answer : 'â€”' ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if (isset($aiRisk) && $aiRisk): ?>
<div class="card" style="margin-top: 1.25rem; border: 1px solid var(--primary-100); border-left: 4px solid <?= $aiRisk['risk_level'] === 'critical' ? '#DC2626' : ($aiRisk['risk_level'] === 'high' ? '#EA580C' : 'var(--primary-600)') ?>;">
    <div class="card-body" style="padding: 1rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
            <div>
                <h4 style="margin: 0; font-size: 0.9rem; color: #111827; font-weight: 700;"><i class="fas fa-robot" style="color: var(--primary-600); margin-right: 0.5rem;"></i> AI Risk Analysis</h4>
                <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #6B7280;">Based on longitudinal trend analysis</p>
            </div>
            <span style="padding: 0.25rem 0.6rem; border-radius: 999px; font-size: 0.7rem; font-weight: 600; <?php
                echo match($aiRisk['risk_level']) {
                    'critical' => 'background: #FEF2F2; color: #DC2626;',
                    'high'     => 'background: #FFF7ED; color: #EA580C;',
                    'elevated' => 'background: #FFFBEB; color: #D97706;',
                    default    => 'background: #ECFDF5; color: #059669;',
                };
            ?>"><?= strtoupper($aiRisk['risk_level']) ?> RISK</span>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
            <div>
                <p style="font-size: 0.65rem; color: #9CA3AF; margin: 0 0 0.25rem 0; text-transform: uppercase;">Trend Direction</p>
                <p style="font-size: 0.85rem; font-weight: 600; margin: 0; color: <?= $aiRisk['trend_direction'] === 'escalating' ? '#DC2626' : ($aiRisk['trend_direction'] === 'improving' ? '#059669' : '#374151') ?>;">
                    <?php if ($aiRisk['trend_direction'] === 'escalating'): ?>
                        <i class="fas fa-arrow-trend-up"></i>
                    <?php elseif ($aiRisk['trend_direction'] === 'improving'): ?>
                        <i class="fas fa-arrow-trend-down"></i>
                    <?php else: ?>
                        <i class="fas fa-minus"></i>
                    <?php endif; ?>
                    <?= ucfirst($aiRisk['trend_direction']) ?>
                </p>
            </div>
            <div>
                <p style="font-size: 0.65rem; color: #9CA3AF; margin: 0 0 0.25rem 0; text-transform: uppercase;">Projected Score (30d)</p>
                <p style="font-size: 0.85rem; font-weight: 600; margin: 0; color: #111827;">~<?= round($aiRisk['projected_score']) ?></p>
            </div>
            <div>
                <p style="font-size: 0.65rem; color: #9CA3AF; margin: 0 0 0.25rem 0; text-transform: uppercase;">Data Points</p>
                <p style="font-size: 0.85rem; font-weight: 600; margin: 0; color: #111827;"><?= $aiRisk['data_points_used'] ?> sessions</p>
            </div>
        </div>

        <?php if ($aiRisk['anomaly_detected']): ?>
            <div style="margin-top: 1rem; padding: 0.6rem 0.8rem; background: #FEF2F2; border: 1px solid #FECACA; border-radius: 0.375rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-triangle-exclamation" style="color: #DC2626;"></i>
                <p style="margin: 0; font-size: 0.75rem; color: #991B1B;"><strong>Anomaly Detected:</strong> Significant sudden increase in symptoms compared to baseline (Magnitude: <?= round($aiRisk['anomaly_magnitude'], 1) ?> SD).</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Score Trend Chart -->
<?php if (count($scoreHistory) > 1): ?>
<div class="card" style="margin-top: 1.25rem;">
    <div class="card-header"><i class="fas fa-chart-line" style="margin-right: 0.5rem; color: #10B981;"></i> Score Trend</div>
    <div class="card-body">
        <div style="display: flex; align-items: flex-end; gap: 2px; height: 120px; padding: 0.5rem;">
            <?php
            $maxScore = max(array_column($scoreHistory, 'total_score'));
            $maxScore = $maxScore ?: 1;
            foreach ($scoreHistory as $h):
                $pct = ($h['total_score'] / $maxScore) * 100;
                $barColor = $h['total_score'] >= 15 ? '#DC2626' : ($h['total_score'] >= 10 ? '#F59E0B' : '#10B981');
            ?>
                <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%;">
                    <span style="font-size: 0.55rem; font-weight: 700; color: #374151;"><?= $h['total_score'] ?></span>
                    <div style="width: 100%; max-width: 32px; background: <?= $barColor ?>; border-radius: 0.25rem 0.25rem 0 0; height: <?= max(4, $pct) ?>%; transition: height 300ms;"></div>
                    <span style="font-size: 0.5rem; color: #9CA3AF; margin-top: 0.15rem; white-space: nowrap;"><?= date('M d', strtotime($h['submitted_at'])) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div style="margin-top: 1.25rem; display: flex; gap: 0.75rem; justify-content: center;">
    <a href="/counselling/screenings" style="padding: 0.6rem 1.25rem; background: #F3F4F6; color: #374151; border-radius: 0.5rem; font-size: 0.85rem; text-decoration: none;">â† Back to Screenings</a>
    <?php if ($response['student']): ?>
        <a href="/counselling/screenings/history/<?= $response['student_id'] ?>" style="padding: 0.6rem 1.25rem; background: var(--primary-600); color: white; border-radius: 0.5rem; font-size: 0.85rem; text-decoration: none; font-weight: 500;"><i class="fas fa-chart-line"></i> Full History</a>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
