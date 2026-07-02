<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.25rem;">
    <!-- Left: Patient Card -->
    <div class="card">
        <div class="card-header"><i class="fas fa-user" style="margin-right: 0.5rem; color: var(--primary-500);"></i> Patient Information</div>
        <div class="card-body" style="text-align: center;">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-500), #8B5CF6); display: inline-flex; align-items: center; justify-content: center; color: white; font-size: 1.1rem; font-weight: 700; margin-bottom: 0.75rem;">
                <?= strtoupper(substr($student['first_name'], 0, 1)) ?><?= strtoupper(substr($student['last_name'], 0, 1)) ?>
            </div>
            <h3 style="font-size: 1rem; font-weight: 700;"><?= esc($student['full_name']) ?></h3>
            <p style="font-size: 0.8rem; color: var(--primary-500); font-weight: 600;"><?= esc($student['student_number']) ?></p>

            <?php if (! empty($student['allergies'])): ?>
                <div style="margin-top: 1rem; padding: 0.75rem; background: #FEF2F2; border: 1px solid #FECACA; border-radius: 0.5rem; text-align: left;">
                    <p style="font-size: 0.75rem; font-weight: 600; color: #DC2626; margin-bottom: 0.3rem;">
                        <i class="fas fa-triangle-exclamation"></i> Known Allergies
                    </p>
                    <?php foreach ($student['allergies'] as $a): ?>
                        <span style="display: inline-block; padding: 0.15rem 0.4rem; background: white; border: 1px solid #FECACA; border-radius: 999px; font-size: 0.65rem; margin: 0.1rem; font-weight: 500; color: #991B1B;"><?= esc($a['allergen']) ?> (<?= $a['severity'] ?>)</span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($student['blood_type']): ?>
                <p style="margin-top: 0.75rem; font-size: 0.8rem;"><span style="padding: 0.15rem 0.5rem; background: #FEF2F2; color: #DC2626; border-radius: 999px; font-size: 0.7rem; font-weight: 600;"><i class="fas fa-droplet"></i> <?= esc($student['blood_type']) ?></span></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right: Consultation Form -->
    <div class="card">
        <div class="card-header"><i class="fas fa-stethoscope" style="margin-right: 0.5rem; color: #10B981;"></i> New Consultation</div>
        <div class="card-body">
            <?php $errors = session()->get('errors') ?? []; ?>

            <form method="POST" action="/clinic/consultations/store" novalidate
                  data-synapse-form-dialog
                  data-dialog-title="New Consultation"
                  data-dialog-icon="fas fa-stethoscope"
                  data-dialog-submit-label="Start Consultation"
                  data-dialog-cancel-label="Cancel"
                  data-dialog-width>
                <?= csrf_field() ?>
                <input type="hidden" name="student_id" value="<?= $student['id'] ?>">

                <?php if (! empty($errors)): ?>
                    <div role="alert" id="consult-errors" class="syn-alert syn-alert--danger" style="margin-bottom: 1.25rem;">
                        <i class="fas fa-triangle-exclamation"></i>
                        <div>
                            <strong>Please fix the following before submitting:</strong>
                            <ul style="margin: 0.5rem 0 0; padding-left: 1.25rem;">
                                <?php foreach ($errors as $field => $msg): ?>
                                    <li><?= esc($msg) ?></li>
                                <?php endforeach ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <fieldset style="margin-bottom: 1.25rem; padding: 0; border: 0;">
                    <legend style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem; padding: 0;">Check-In Method</legend>
                    <div role="radiogroup" aria-label="Check-in method" style="display: flex; gap: 0.5rem;">
                        <?php foreach (['manual' => 'Manual', 'qr' => 'QR Code', 'rfid' => 'RFID'] as $val => $label): ?>
                            <label style="flex: 1; padding: 0.5rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; text-align: center; cursor: pointer; font-size: 0.8rem; transition: all 150ms;">
                                <input type="radio" name="check_in_method" value="<?= $val ?>" <?= $val === 'manual' ? 'checked' : '' ?> style="display: none;" onchange="this.parentElement.style.borderColor='var(--primary-500)'; this.parentElement.style.background='var(--primary-50)';">
                                <i class="fas fa-<?= $val === 'manual' ? 'keyboard' : ($val === 'qr' ? 'qrcode' : 'wifi') ?>"></i> <?= $label ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <div style="margin-bottom: 1.25rem;">
                    <label for="chief_complaint" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Chief Complaint *</label>
                    <textarea id="chief_complaint" name="chief_complaint" rows="4" required aria-required="true"
                        <?= isset($errors['chief_complaint']) ? 'aria-invalid="true" aria-describedby="chief_complaint-err"' : '' ?>
                        placeholder="Describe the patient's primary reason for visit..."
                        style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif; resize: vertical;"><?= old('chief_complaint') ?></textarea>
                    <?php if (isset($errors['chief_complaint'])): ?>
                        <p id="chief_complaint-err" style="margin: 0.3rem 0 0; font-size: 0.75rem; color: #DC2626;"><?= esc($errors['chief_complaint']) ?></p>
                    <?php endif; ?>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label for="triage_priority" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Triage Priority Override (Leave blank to use AI Recommendation)</label>
                    <select id="triage_priority" name="triage_priority" data-synapse-dropdown>
                        <option value="">â€” Use AI Recommendation â€”</option>
                        <option value="low">ðŸŸ¢ Low</option>
                        <option value="medium">ðŸŸ¡ Medium</option>
                        <option value="high">ðŸŸ  High</option>
                        <option value="urgent">ðŸ”´ Urgent</option>
                    </select>
                    
                    <div id="ai-triage-card" style="margin-top: 0.75rem; padding: 0.85rem; border-radius: 0.5rem; background: #F9FAFB; border: 1px dashed #D1D5DB; transition: all 0.3s ease;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.4rem;">
                            <span style="font-size: 0.75rem; font-weight: 600; color: var(--primary-600);"><i class="fas fa-robot"></i> Live AI Triage Assistant</span>
                            <span id="ai-confidence" style="font-size: 0.7rem; font-weight: 600; color: #6B7280; display: none;"></span>
                        </div>
                        <div id="ai-status-text" style="font-size: 0.75rem; color: #6B7280; font-style: italic;">
                            Type patient complaint to analyze priority...
                        </div>
                        <div id="ai-result-details" style="display: none; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span id="ai-priority-badge" style="font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.6rem; border-radius: 9999px; text-transform: uppercase;"></span>
                                <span id="ai-triage-reason" style="font-size: 0.7rem; color: #374151; font-weight: 500;"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end; padding-top: 1rem; border-top: 1px solid #E5E7EB;">
                    <a href="/clinic/students/<?= $student['id'] ?>" style="padding: 0.6rem 1.25rem; background: #F3F4F6; color: #374151; border-radius: 0.5rem; font-size: 0.85rem; text-decoration: none;">Cancel</a>
                    <button type="submit" style="padding: 0.6rem 1.5rem; background: var(--primary-600); color: white; border: none; border-radius: 0.5rem; font-family: 'Inter', sans-serif; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-play" style="margin-right: 0.25rem;"></i> Start Consultation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const complaintInput = document.querySelector('textarea[name="chief_complaint"]');
    const studentId = document.querySelector('input[name="student_id"]').value;
    const aiCard = document.getElementById('ai-triage-card');
    const aiStatusText = document.getElementById('ai-status-text');
    const aiResultDetails = document.getElementById('ai-result-details');
    const aiConfidence = document.getElementById('ai-confidence');
    const aiPriorityBadge = document.getElementById('ai-priority-badge');
    const aiTriageReason = document.getElementById('ai-triage-reason');

    let debounceTimeout = null;

    function runTriage() {
        const text = complaintInput.value.trim();
        if (text.length < 5) {
            aiStatusText.style.display = 'block';
            aiStatusText.textContent = 'Type patient complaint to analyze priority...';
            aiResultDetails.style.display = 'none';
            aiConfidence.style.display = 'none';
            aiCard.style.background = '#F9FAFB';
            aiCard.style.borderColor = '#D1D5DB';
            aiCard.style.borderStyle = 'dashed';
            return;
        }

        aiStatusText.textContent = 'Analyzing complaint with AI...';

        const formData = new FormData();
        formData.append('student_id', studentId);
        formData.append('chief_complaint', text);

        fetch('/clinic/consultations/ajax-triage', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data && data.predicted_priority) {
                aiStatusText.style.display = 'none';
                aiResultDetails.style.display = 'flex';
                aiConfidence.style.display = 'inline';
                aiConfidence.textContent = `Confidence: ${(data.confidence_score * 100).toFixed(0)}%`;

                // Badge colors
                let bg, fg, borderCol;
                switch(data.predicted_priority) {
                    case 'urgent':
                        bg = '#FEF2F2'; fg = '#DC2626'; borderCol = '#FCA5A5';
                        break;
                    case 'high':
                        bg = '#FFF7ED'; fg = '#EA580C'; borderCol = '#FDBA74';
                        break;
                    case 'medium':
                        bg = '#FEFCE8'; fg = '#CA8A04'; borderCol = '#FEF08A';
                        break;
                    default:
                        bg = '#F0FDF4'; fg = '#16A34A'; borderCol = '#86EFAC';
                }

                aiCard.style.background = bg;
                aiCard.style.borderColor = borderCol;
                aiCard.style.borderStyle = 'solid';

                aiPriorityBadge.textContent = data.predicted_priority;
                aiPriorityBadge.style.background = 'white';
                aiPriorityBadge.style.color = fg;
                aiPriorityBadge.style.border = `1px solid ${borderCol}`;

                let reasons = [];
                if (data.features_used.allergy_triggered) reasons.push('Allergy warning');
                if (data.features_used.keyword_matched) reasons.push('Keyword match');
                if (data.features_used.vitals_triggered) reasons.push('Vitals triggered');
                aiTriageReason.textContent = reasons.length > 0 ? `(${reasons.join(', ')})` : 'Based on chief complaint patterns';
            }
        })
        .catch(err => {
            console.error('Triage AI error:', err);
            aiStatusText.textContent = 'Failed to load AI recommendation.';
        });
    }

    complaintInput.addEventListener('input', function() {
        clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(runTriage, 500);
    });

    // Run once on load if there's prefilled input
    if (complaintInput.value.trim().length >= 5) {
        runTriage();
    }
});
</script>
<?= $this->endSection() ?>
