<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
/* Workflow progress — used by the stepper below and exposes the
   current state to the page so we don't recompute the same flags. */
$steps = [
    ['label' => 'Check-In',  'icon' => 'fa-door-open',      'done' => true],
    ['label' => 'Vitals',    'icon' => 'fa-heart-pulse',    'done' => $consult['vitals'] !== null],
    ['label' => 'Diagnosis', 'icon' => 'fa-stethoscope',    'done' => ! empty($consult['diagnosis'])],
    ['label' => 'Treatment', 'icon' => 'fa-pills',          'done' => ! empty($consult['treatments'])],
    ['label' => 'Complete',  'icon' => 'fa-flag-checkered', 'done' => $consult['status'] !== 'in_progress'],
];
?>

<style>
/* Workflow stepper — connector lines are drawn by each step's
   ::after pseudo-element so they always start at the right edge of
   the current circle and end at the left edge of the next circle,
   no matter how the labels wrap or how the row is sized. */
.syn-stepper {
    display: flex;
    align-items: flex-start;
    gap: 0;
    margin-bottom: 1.5rem;
}
.syn-step {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    min-width: 0;
}
.syn-step:not(:last-child)::after {
    content: '';
    position: absolute;
    /* Vertical center of the 32px circle = 16px from the top. */
    top: 15px;
    /* From the right edge of the current circle (50% + 16px)
       to the left edge of the next circle (100% + 50% - 16px). */
    left: calc(50% + 18px);
    right: calc(-50% + 18px);
    height: 2px;
    background: #E5E7EB;
    transition: background 200ms;
}
.syn-step.is-done::after {
    background: #10B981;
}
.syn-step__circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    background: #F3F4F6;
    color: #9CA3AF;
    border: 2px solid #E5E7EB;
    position: relative;
    z-index: 1;
    transition: background 200ms, color 200ms, border-color 200ms;
}
.syn-step.is-done .syn-step__circle {
    background: #10B981;
    color: white;
    border-color: #10B981;
}
.syn-step__label {
    margin-top: 0.4rem;
    font-size: 0.65rem;
    font-weight: 500;
    color: #9CA3AF;
    text-align: center;
    line-height: 1.2;
}
.syn-step.is-done .syn-step__label {
    color: #059669;
}
</style>

<!-- Workflow Progress Bar -->
<div class="syn-stepper">
    <?php foreach ($steps as $step): ?>
        <div class="syn-step <?= $step['done'] ? 'is-done' : '' ?>">
            <div class="syn-step__circle">
                <i class="fas <?= $step['done'] ? 'fa-check' : $step['icon'] ?>"></i>
            </div>
            <p class="syn-step__label"><?= esc($step['label']) ?></p>
        </div>
    <?php endforeach; ?>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
    <!-- Left Column -->
    <div>
        <!-- Patient & Consultation Info -->
        <div class="card">
            <div class="card-header"><i class="fas fa-user" style="margin-right: 0.5rem; color: var(--primary-500);"></i> Patient & Consultation</div>
            <div class="card-body">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                    <div style="width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-500), #8B5CF6); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.9rem; flex-shrink: 0;">
                        <?= strtoupper(substr($consult['student_first'], 0, 1)) ?><?= strtoupper(substr($consult['student_last'], 0, 1)) ?>
                    </div>
                    <div>
                        <p style="font-weight: 600; font-size: 0.95rem;"><?= esc($consult['student_first'] . ' ' . $consult['student_last']) ?></p>
                        <p style="font-size: 0.75rem; color: #6B7280;"><?= esc($consult['student_number']) ?> <?php if ($consult['blood_type']): ?>â€¢ <span style="color: #DC2626; font-weight: 600;"><?= esc($consult['blood_type']) ?></span><?php endif; ?></p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; font-size: 0.8rem;">
                    <div><span style="color: #9CA3AF;">Date:</span> <?= date('M d, Y h:i A', strtotime($consult['consultation_date'])) ?></div>
                    <div><span style="color: #9CA3AF;">Check-in:</span> <?= strtoupper($consult['check_in_method']) ?></div>
                    <div><span style="color: #9CA3AF;">Attending:</span> <?= esc($consult['staff_first'] . ' ' . $consult['staff_last']) ?></div>
                    <div><span style="color: #9CA3AF;">Status:</span>
                        <span style="padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; <?php
                            echo match($consult['status']) {
                                'completed' => 'background: #ECFDF5; color: #059669;',
                                'follow_up' => 'background: #EFF6FF; color: #2563EB;',
                                default     => 'background: #FFFBEB; color: #D97706;',
                            };
                        ?>"><?= ucfirst(str_replace('_', ' ', $consult['status'])) ?></span>
                    </div>
                </div>

                <?php if (! empty($consult['allergies'])): ?>
                    <div style="margin-top: 0.75rem; padding: 0.5rem; background: #FEF2F2; border-radius: 0.375rem; border: 1px solid #FECACA;">
                        <p style="font-size: 0.7rem; font-weight: 600; color: #DC2626;"><i class="fas fa-triangle-exclamation"></i> Allergies:</p>
                        <?php foreach ($consult['allergies'] as $a): ?>
                            <span style="font-size: 0.65rem; padding: 0.1rem 0.35rem; background: white; border: 1px solid #FECACA; border-radius: 999px; margin-right: 0.2rem; color: #991B1B;"><?= esc($a['allergen']) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chief Complaint -->
        <div class="card" style="margin-top: 1.25rem;">
            <div class="card-header"><i class="fas fa-comment-medical" style="margin-right: 0.5rem; color: #3B82F6;"></i> Chief Complaint</div>
            <div class="card-body">
                <p style="font-size: 0.85rem; line-height: 1.6;"><?= esc($consult['chief_complaint']) ?></p>
                <?php if ($consult['triage_priority']): ?>
                    <div style="margin-top: 0.75rem;">
                        <span style="font-size: 0.75rem; color: #6B7280;">Final Priority: </span>
                        <span style="padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; <?php
                            echo match($consult['triage_priority']) {
                                'urgent' => 'background: #FEF2F2; color: #DC2626;',
                                'high'   => 'background: #FFF7ED; color: #EA580C;',
                                'medium' => 'background: #FFFBEB; color: #D97706;',
                                default  => 'background: #ECFDF5; color: #059669;',
                            };
                        ?>"><?= ucfirst($consult['triage_priority']) ?></span>
                        <?php if ($consult['triage_override']): ?>
                            <span style="font-size: 0.65rem; color: #9CA3AF; margin-left: 0.25rem;">(overridden)</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($aiPrediction) && $aiPrediction): ?>
                    <div style="margin-top: 1rem; padding: 0.75rem; background: #F5F3FF; border: 1px solid var(--primary-100); border-radius: 0.5rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span style="font-size: 0.75rem; font-weight: 600; color: var(--primary-600);"><i class="fas fa-robot"></i> AI Triage Analysis</span>
                            <span style="font-size: 0.65rem; color: #6B7280;">Confidence: <strong><?= round($aiPrediction['confidence_score'] * 100, 1) ?>%</strong></span>
                        </div>
                        <p style="font-size: 0.75rem; color: #374151; margin-bottom: 0.5rem;">
                            Predicted Priority: 
                            <span style="font-weight: 600; color: <?= match($aiPrediction['predicted_priority']) { 'urgent' => '#DC2626', 'high' => '#EA580C', 'medium' => '#D97706', default => '#059669' } ?>;">
                                <?= ucfirst($aiPrediction['predicted_priority']) ?>
                            </span>
                        </p>
                        <?php if (!empty($aiPrediction['features_used'])): ?>
                            <div style="font-size: 0.7rem; color: #6B7280;">
                                <strong>Key Indicators Detected:</strong>
                                <?php $features = json_decode($aiPrediction['features_used'], true); ?>
                                <ul style="margin: 0.25rem 0 0 1rem; padding: 0;">
                                    <?php foreach ($features as $f): ?>
                                        <li><?= esc($f) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Vital Signs -->
        <div class="card" style="margin-top: 1.25rem;">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <span><i class="fas fa-heart-pulse" style="margin-right: 0.5rem; color: #EF4444;"></i> Vital Signs</span>
                <?php if ($consult['vitals'] === null && $consult['status'] === 'in_progress'): ?>
                    <a href="/clinic/consultations/vitals/<?= $consult['id'] ?>" style="padding: 0.3rem 0.6rem; background: var(--primary-50); color: var(--primary-600); border-radius: 0.375rem; font-size: 0.7rem; text-decoration: none; font-weight: 500;">Record Vitals</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($consult['vitals']): $v = $consult['vitals']; ?>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem;">
                        <?php if ($v['temperature']): ?>
                            <div style="text-align: center; padding: 0.5rem; background: #F9FAFB; border-radius: 0.375rem;">
                                <p style="font-size: 1.1rem; font-weight: 700; color: #111827;"><?= $v['temperature'] ?>Â°C</p>
                                <p style="font-size: 0.65rem; color: #9CA3AF;">Temp</p>
                            </div>
                        <?php endif; ?>
                        <?php if ($v['blood_pressure_sys'] && $v['blood_pressure_dia']): ?>
                            <div style="text-align: center; padding: 0.5rem; background: #F9FAFB; border-radius: 0.375rem;">
                                <p style="font-size: 1.1rem; font-weight: 700; color: #111827;"><?= $v['blood_pressure_sys'] ?>/<?= $v['blood_pressure_dia'] ?></p>
                                <p style="font-size: 0.65rem; color: #9CA3AF;">BP (mmHg)</p>
                            </div>
                        <?php endif; ?>
                        <?php if ($v['heart_rate']): ?>
                            <div style="text-align: center; padding: 0.5rem; background: #F9FAFB; border-radius: 0.375rem;">
                                <p style="font-size: 1.1rem; font-weight: 700; color: #111827;"><?= $v['heart_rate'] ?></p>
                                <p style="font-size: 0.65rem; color: #9CA3AF;">HR (bpm)</p>
                            </div>
                        <?php endif; ?>
                        <?php if ($v['weight_kg']): ?>
                            <div style="text-align: center; padding: 0.5rem; background: #F9FAFB; border-radius: 0.375rem;">
                                <p style="font-size: 1.1rem; font-weight: 700; color: #111827;"><?= $v['weight_kg'] ?> kg</p>
                                <p style="font-size: 0.65rem; color: #9CA3AF;">Weight</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p style="font-size: 0.8rem; color: #9CA3AF; text-align: center;">No vitals recorded yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div>
        <!-- Diagnosis & Notes -->
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <span><i class="fas fa-stethoscope" style="margin-right: 0.5rem; color: #8B5CF6;"></i> Diagnosis & Notes</span>
                <?php if ($consult['status'] === 'in_progress'): ?>
                    <a href="/clinic/consultations/diagnosis/<?= $consult['id'] ?>" style="padding: 0.3rem 0.6rem; background: #F5F3FF; color: #7C3AED; border-radius: 0.375rem; font-size: 0.7rem; text-decoration: none; font-weight: 500;"><?= $consult['diagnosis'] ? 'Edit' : 'Add Diagnosis' ?></a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($consult['diagnosis']): ?>
                    <p style="font-size: 0.85rem; font-weight: 600; margin-bottom: 0.5rem;"><?= esc($consult['diagnosis']) ?></p>
                    <?php if ($consult['notes']): ?>
                        <p style="font-size: 0.8rem; color: #6B7280; line-height: 1.6;"><?= nl2br(esc($consult['notes'])) ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p style="font-size: 0.8rem; color: #9CA3AF; text-align: center;">No diagnosis entered yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Treatments -->
        <div class="card" style="margin-top: 1.25rem;">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <span><i class="fas fa-pills" style="margin-right: 0.5rem; color: #10B981;"></i> Treatments</span>
                <?php if ($consult['status'] === 'in_progress'): ?>
                    <a href="/clinic/treatments/create/<?= $consult['id'] ?>"
                       data-synapse-form-link
                       data-dialog-title="Add Treatment"
                       data-dialog-icon="fas fa-pills"
                       data-dialog-width
                       style="padding: 0.3rem 0.6rem; background: #ECFDF5; color: #059669; border-radius: 0.375rem; font-size: 0.7rem; text-decoration: none; font-weight: 500;">+ Add Treatment</a>
                <?php endif; ?>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($consult['treatments'])): ?>
                    <p style="padding: 1.5rem; font-size: 0.8rem; color: #9CA3AF; text-align: center;">No treatments recorded.</p>
                <?php else: ?>
                    <?php foreach ($consult['treatments'] as $t): ?>
                        <div style="padding: 0.75rem 1.25rem; border-bottom: 1px solid #F3F4F6;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; background: #ECFDF5; color: #059669; text-transform: capitalize;"><?= str_replace('_', ' ', $t['treatment_type']) ?></span>
                                <span style="font-size: 0.7rem; color: #9CA3AF;">by <?= esc($t['admin_first'] . ' ' . $t['admin_last']) ?></span>
                            </div>
                            <p style="font-size: 0.85rem; margin-top: 0.35rem;"><?= esc($t['description']) ?></p>
                            <?php if ($t['generic_name']): ?>
                                <p style="font-size: 0.75rem; color: var(--primary-600); margin-top: 0.2rem;">
                                    <i class="fas fa-capsules"></i> <?= esc($t['generic_name']) ?>
                                    <?= $t['brand_name'] ? '(' . esc($t['brand_name']) . ')' : '' ?>
                                    â€” <?= $t['quantity_used'] ?> <?= esc($t['unit'] ?? 'units') ?>
                                    <span style="color: #9CA3AF;">Batch: <?= esc($t['batch_number']) ?></span>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Referrals -->
        <div class="card" style="margin-top: 1.25rem;">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <span><i class="fas fa-arrow-right-arrow-left" style="margin-right: 0.5rem; color: #F59E0B;"></i> Referrals</span>
                <?php if ($consult['status'] === 'in_progress'): ?>
                    <a href="/clinic/referrals/create/<?= $consult['id'] ?>"
                       data-synapse-form-link
                       data-dialog-title="Refer to Counselling"
                       data-dialog-icon="fas fa-arrow-right-arrow-left"
                       style="padding: 0.3rem 0.6rem; background: #FFFBEB; color: #D97706; border-radius: 0.375rem; font-size: 0.7rem; text-decoration: none; font-weight: 500;">+ Refer to Counselling</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($consult['referrals'])): ?>
                    <p style="font-size: 0.8rem; color: #9CA3AF; text-align: center;">No referrals for this consultation.</p>
                <?php else: ?>
                    <?php foreach ($consult['referrals'] as $r): ?>
                        <div style="padding: 0.5rem 0; border-bottom: 1px solid #F3F4F6;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="font-size: 0.8rem; font-weight: 500;"><?= ucfirst(str_replace('_', ' â†’ ', $r['direction'])) ?></span>
                                <span style="padding: 0.15rem 0.4rem; border-radius: 999px; font-size: 0.6rem; font-weight: 600; <?php
                                    echo match($r['status']) {
                                        'accepted' => 'background: #ECFDF5; color: #059669;',
                                        'declined' => 'background: #FEF2F2; color: #DC2626;',
                                        default    => 'background: #FFFBEB; color: #D97706;',
                                    };
                                ?>"><?= ucfirst($r['status']) ?></span>
                            </div>
                            <p style="font-size: 0.75rem; color: #6B7280; margin-top: 0.2rem;"><?= esc($r['reason']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Actions -->
        <?php if ($consult['status'] === 'in_progress'): ?>
            <div class="card" style="margin-top: 1.25rem;">
                <div class="card-body" style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <form method="POST" action="/clinic/consultations/complete/<?= $consult['id'] ?>" style="display: inline;" class="js-confirm-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="status" value="follow_up">
                        <button type="submit"
                                data-confirm-title="Mark for follow-up?"
                                data-confirm-body="This consultation will be flagged for follow-up. You can still add notes or prescriptions afterwards."
                                data-confirm-text="Mark Follow-Up"
                                data-confirm-danger="false"
                                style="padding: 0.5rem 1rem; background: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE; border-radius: 0.5rem; font-family: 'Inter', sans-serif; font-size: 0.8rem; font-weight: 500; cursor: pointer;">
                            <i class="fas fa-calendar-check"></i> Mark Follow-Up
                        </button>
                    </form>
                    <form method="POST" action="/clinic/consultations/complete/<?= $consult['id'] ?>" style="display: inline;" class="js-confirm-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="status" value="completed">
                        <button type="submit"
                                data-confirm-title="Complete this consultation?"
                                data-confirm-body="This will close the visit. You won't be able to add new vitals, diagnosis, or treatments after completion."
                                data-confirm-text="Complete Consultation"
                                data-confirm-danger="true"
                                style="padding: 0.5rem 1rem; background: #10B981; color: white; border: none; border-radius: 0.5rem; font-family: 'Inter', sans-serif; font-size: 0.8rem; font-weight: 600; cursor: pointer;">
                            <i class="fas fa-check"></i> Complete Consultation
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Confirmation dialog for state transitions (complete / follow-up).
    // Buttons opt-in via data-confirm-title / -body / -text / -danger.
    // synapse.dialog.confirm() uses onConfirm/onCancel callbacks, NOT a Promise,
    // so we wire through those.
    document.querySelectorAll('form.js-confirm-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var btn = e.submitter || form.querySelector('button[type="submit"]');
            if (!btn) return;

            // If the user has already confirmed, let the submission proceed.
            if (form.dataset.confirmed === '1') {
                form.dataset.confirmed = '';
                return;
            }

            var title  = btn.getAttribute('data-confirm-title') || 'Are you sure?';
            var body   = btn.getAttribute('data-confirm-body')  || '';
            var text   = btn.getAttribute('data-confirm-text')  || 'Confirm';
            var danger = btn.getAttribute('data-confirm-danger') === 'true';

            // Block the first submission, then re-submit only after the user confirms.
            e.preventDefault();

            function doSubmit() {
                form.dataset.confirmed = '1';
                form.submit();
            }

            if (window.synapse && window.synapse.dialog && typeof window.synapse.dialog.confirm === 'function') {
                window.synapse.dialog.confirm({
                    title: title,
                    body: body,
                    confirmText: text,
                    danger: danger,
                    onConfirm: doSubmit
                });
            } else if (window.confirm(title + '\n\n' + body)) {
                // Fallback to native confirm if the dialog API isn't loaded yet.
                doSubmit();
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
