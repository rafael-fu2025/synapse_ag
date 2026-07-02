<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card" style="max-width: 700px;">
    <div class="card-header"><i class="fas fa-heart-pulse" style="margin-right: 0.5rem; color: #EF4444;"></i> Record Vital Signs</div>
    <div class="card-body">
        <form method="POST" action="/clinic/consultations/vitals/<?= $consult['id'] ?>">
            <?= csrf_field() ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Temperature (°C)</label>
                    <input type="number" name="temperature" step="0.1" min="30" max="45" placeholder="e.g. 36.5" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Heart Rate (bpm)</label>
                    <input type="number" name="heart_rate" min="30" max="250" placeholder="e.g. 72" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Blood Pressure (mmHg)</label>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <input type="number" name="blood_pressure_sys" min="60" max="300" placeholder="Systolic" style="flex: 1; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                    <span style="font-weight: 700; color: #9CA3AF;">/</span>
                    <input type="number" name="blood_pressure_dia" min="30" max="200" placeholder="Diastolic" style="flex: 1; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Respiratory Rate</label>
                    <input type="number" name="respiratory_rate" min="5" max="60" placeholder="e.g. 16" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Weight (kg)</label>
                    <input type="number" name="weight_kg" step="0.01" min="1" max="500" placeholder="e.g. 65.5" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Height (cm)</label>
                    <input type="number" name="height_cm" step="0.01" min="30" max="300" placeholder="e.g. 170" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
            </div>

            <input type="hidden" id="student-id-input" value="<?= $consult['student_id'] ?>">
            <input type="hidden" id="chief-complaint-input" value="<?= esc($consult['chief_complaint']) ?>">

            <div id="ai-triage-card" style="margin-top: 1rem; margin-bottom: 1.25rem; padding: 0.85rem; border-radius: 0.5rem; background: #F9FAFB; border: 1px dashed #D1D5DB; transition: all 0.3s ease;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.4rem;">
                    <span style="font-size: 0.75rem; font-weight: 600; color: #EF4444;"><i class="fas fa-robot"></i> Live AI Triage Assistant (With Vitals)</span>
                    <span id="ai-confidence" style="font-size: 0.7rem; font-weight: 600; color: #6B7280; display: none;"></span>
                </div>
                <div id="ai-status-text" style="font-size: 0.75rem; color: #6B7280; font-style: italic;">
                    Enter vitals above to see updated triage priority...
                </div>
                <div id="ai-result-details" style="display: none; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span id="ai-priority-badge" style="font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.6rem; border-radius: 9999px; text-transform: uppercase;"></span>
                        <span id="ai-triage-reason" style="font-size: 0.7rem; color: #374151; font-weight: 500;"></span>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; padding-top: 1rem; border-top: 1px solid #E5E7EB;">
                <a href="/clinic/consultations/<?= $consult['id'] ?>" style="padding: 0.6rem 1.25rem; background: #F3F4F6; color: #374151; border-radius: 0.5rem; font-size: 0.85rem; text-decoration: none;">Cancel</a>
                <button type="submit" style="padding: 0.6rem 1.5rem; background: #EF4444; color: white; border: none; border-radius: 0.5rem; font-family: 'Inter', sans-serif; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-heart-pulse" style="margin-right: 0.25rem;"></i> Save Vitals
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tempInput = document.querySelector('input[name="temperature"]');
    const hrInput = document.querySelector('input[name="heart_rate"]');
    const sysInput = document.querySelector('input[name="blood_pressure_sys"]');
    const diaInput = document.querySelector('input[name="blood_pressure_dia"]');
    
    const studentId = document.getElementById('student-id-input').value;
    const chiefComplaint = document.getElementById('chief-complaint-input').value;

    const aiCard = document.getElementById('ai-triage-card');
    const aiStatusText = document.getElementById('ai-status-text');
    const aiResultDetails = document.getElementById('ai-result-details');
    const aiConfidence = document.getElementById('ai-confidence');
    const aiPriorityBadge = document.getElementById('ai-priority-badge');
    const aiTriageReason = document.getElementById('ai-triage-reason');

    let debounceTimeout = null;

    function runTriage() {
        const formData = new FormData();
        formData.append('student_id', studentId);
        formData.append('chief_complaint', chiefComplaint);
        
        if (tempInput.value) formData.append('temperature', tempInput.value);
        if (hrInput.value) formData.append('heart_rate', hrInput.value);
        if (sysInput.value) formData.append('blood_pressure_sys', sysInput.value);
        if (diaInput.value) formData.append('blood_pressure_dia', diaInput.value);

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
                if (data.features_used.vitals_triggered) reasons.push('Abnormal Vitals');
                if (data.features_used.allergy_triggered) reasons.push('Allergy warning');
                if (data.features_used.keyword_matched) reasons.push('Complaint keywords');
                aiTriageReason.textContent = reasons.length > 0 ? `(${reasons.join(', ')})` : 'Based on chief complaint & vitals';
            }
        })
        .catch(err => {
            console.error('Triage AI error:', err);
        });
    }

    [tempInput, hrInput, sysInput, diaInput].forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(debounceTimeout);
            debounceTimeout = setTimeout(runTriage, 500);
        });
    });

    // Run once on load to get baseline
    runTriage();
});
</script>
<?= $this->endSection() ?>
