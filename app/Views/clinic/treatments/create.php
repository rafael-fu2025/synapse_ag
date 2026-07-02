<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card" style="max-width: 750px;">
    <div class="card-header"><i class="fas fa-pills" style="margin-right: 0.5rem; color: #10B981;"></i> Add Treatment</div>
    <div class="card-body">
        <?php $errors = session()->get('errors') ?? []; ?>

        <form method="POST" action="/clinic/treatments/store" id="treatmentForm" novalidate
              data-synapse-form-dialog
              data-dialog-title="Add Treatment"
              data-dialog-icon="fas fa-pills"
              data-dialog-submit-label="Add Treatment"
              data-dialog-cancel-label="Cancel"
              data-dialog-width>
            <?= csrf_field() ?>
            <input type="hidden" name="consultation_id" value="<?= $consult['id'] ?>">

            <?php if (! empty($errors)): ?>
                <div role="alert" id="treatment-errors" class="syn-alert syn-alert--danger" style="margin-bottom: 1.25rem;">
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

            <div style="margin-bottom: 1.25rem;">
                <label for="treatment_type" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Treatment Type *</label>
                <select id="treatment_type" name="treatment_type" required aria-required="true"
                    <?= isset($errors['treatment_type']) ? 'aria-invalid="true" aria-describedby="treatment_type-err"' : '' ?>
                    data-synapse-dropdown onchange="toggleMedicineFields()">
                    <option value="">â€” Select â€”</option>
                    <option value="medication">ðŸ’Š Medication</option>
                    <option value="first_aid">ðŸ©¹ First Aid</option>
                    <option value="procedure">ðŸ”§ Procedure</option>
                    <option value="referral">ðŸ”€ Referral</option>
                    <option value="other">ðŸ“ Other</option>
                </select>
                <?php if (isset($errors['treatment_type'])): ?>
                    <p id="treatment_type-err" style="margin: 0.3rem 0 0; font-size: 0.75rem; color: #DC2626;"><?= esc($errors['treatment_type']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Medicine Fields (shown only for medication type) -->
            <div id="medicineFields" style="display: none; margin-bottom: 1.25rem; padding: 1rem; background: #F9FAFB; border-radius: 0.5rem; border: 1px solid #E5E7EB;">
                <div style="margin-bottom: 1rem;">
                    <label for="medicine_id" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Select Medicine *</label>
                    <select id="medicine_id" name="medicine_id" data-synapse-dropdown onchange="updateBatchInfo()">
                        <option value="">â€” Select Medicine â€”</option>
                        <?php foreach ($medicines as $m): ?>
                            <option value="<?= $m['id'] ?>" data-stock="<?= $m['total_stock'] ?? 0 ?>" data-unit="<?= esc($m['unit']) ?>">
                                <?= esc($m['generic_name']) ?> <?= $m['brand_name'] ? '(' . esc($m['brand_name']) . ')' : '' ?> â€” <?= $m['total_stock'] ?? 0 ?> <?= esc($m['unit']) ?> available
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="batchInfo" style="display: none; margin-bottom: 1rem; padding: 0.5rem; background: var(--primary-50); border-radius: 0.375rem; font-size: 0.75rem; color: var(--primary-700);">
                    <i class="fas fa-info-circle"></i> <span id="batchText">FEFO batch will be auto-selected</span>
                </div>

                <div>
                    <label for="quantity" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Quantity to Dispense *</label>
                    <input id="quantity" type="number" name="quantity" min="1" placeholder="Enter quantity" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label for="description" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Description / Instructions *</label>
                <textarea id="description" name="description" rows="3" required aria-required="true"
                    <?= isset($errors['description']) ? 'aria-invalid="true" aria-describedby="description-err"' : '' ?>
                    placeholder="Describe the treatment, dosage instructions, or procedure performed..."
                    style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif; resize: vertical;"></textarea>
                <?php if (isset($errors['description'])): ?>
                    <p id="description-err" style="margin: 0.3rem 0 0; font-size: 0.75rem; color: #DC2626;"><?= esc($errors['description']) ?></p>
                <?php endif; ?>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; padding-top: 1rem; border-top: 1px solid #E5E7EB;">
                <a href="/clinic/consultations/<?= $consult['id'] ?>" style="padding: 0.6rem 1.25rem; background: #F3F4F6; color: #374151; border-radius: 0.5rem; font-size: 0.85rem; text-decoration: none;">Cancel</a>
                <button type="submit" style="padding: 0.6rem 1.5rem; background: #10B981; color: white; border: none; border-radius: 0.5rem; font-family: 'Inter', sans-serif; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-check" style="margin-right: 0.25rem;"></i> Add Treatment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleMedicineFields() {
    const type = document.getElementById('treatmentType').value;
    const fields = document.getElementById('medicineFields');
    fields.style.display = type === 'medication' ? 'block' : 'none';

    if (type === 'medication') {
        document.getElementById('medicineSelect').setAttribute('required', 'required');
        document.getElementById('quantityInput').setAttribute('required', 'required');
    } else {
        document.getElementById('medicineSelect').removeAttribute('required');
        document.getElementById('quantityInput').removeAttribute('required');
    }
}

function updateBatchInfo() {
    const select = document.getElementById('medicineSelect');
    const option = select.options[select.selectedIndex];
    const info = document.getElementById('batchInfo');

    if (option.value) {
        const stock = option.dataset.stock;
        const unit = option.dataset.unit;
        document.getElementById('batchText').textContent =
            `FEFO: Earliest-expiry batch auto-selected. Available: ${stock} ${unit}`;
        info.style.display = 'block';
    } else {
        info.style.display = 'none';
    }
}
</script>
<?= $this->endSection() ?>
