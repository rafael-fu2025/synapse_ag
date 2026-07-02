<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php $isEdit = ($mode === 'edit'); ?>
<?php $errors = session()->get('errors') ?? []; ?>

<div class="card" style="max-width: 700px;">
    <div class="card-header"><i class="fas fa-capsules" style="margin-right: 0.5rem; color: #10B981;"></i> <?= $isEdit ? 'Edit Medicine' : 'Add New Medicine' ?></div>
    <div class="card-body">
        <?php if (! empty($errors)): ?>
            <div role="alert" style="padding: 0.75rem 1rem; margin-bottom: 1.25rem; background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; border-radius: 0.375rem; font-size: 0.85rem;">
                <strong><i class="fas fa-triangle-exclamation" style="margin-right: 0.35rem;"></i>Please correct the following:</strong>
                <ul style="margin: 0.5rem 0 0 1.25rem; padding: 0;">
                    <?php foreach ($errors as $field => $msg): ?>
                        <li><?= esc($msg) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $isEdit ? '/inventory/medicines/update/' . $medicine['id'] : '/inventory/medicines/store' ?>" data-synapse-form-dialog>
            <?= csrf_field() ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label for="generic_name" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Generic Name <span aria-hidden="true">*</span><span class="sr-only">required</span></label>
                    <input type="text" id="generic_name" name="generic_name" value="<?= old('generic_name', $medicine['generic_name'] ?? '') ?>" required aria-required="true" <?= isset($errors['generic_name']) ? 'aria-invalid="true" aria-describedby="err-generic_name"' : '' ?> style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid <?= isset($errors['generic_name']) ? '#DC2626' : '#E5E7EB' ?>; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                    <?php if (isset($errors['generic_name'])): ?>
                        <p id="err-generic_name" role="alert" style="margin: 0.3rem 0 0; color: #DC2626; font-size: 0.75rem;"><?= esc($errors['generic_name']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="brand_name" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Brand Name</label>
                    <input type="text" id="brand_name" name="brand_name" value="<?= old('brand_name', $medicine['brand_name'] ?? '') ?>" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label for="category" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Category</label>
                    <select id="category" name="category" data-synapse-dropdown>
                        <option value="">—</option>
                        <?php foreach (['Analgesic', 'Antibiotic', 'Antihistamine', 'Antidiarrheal', 'Antiseptic', 'Antipyretic', 'Supplement', 'First Aid', 'Other'] as $cat): ?>
                            <option value="<?= $cat ?>" <?= old('category', $medicine['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="dosage_form" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Dosage Form</label>
                    <select id="dosage_form" name="dosage_form" data-synapse-dropdown>
                        <option value="">—</option>
                        <?php foreach (['Tablet', 'Capsule', 'Syrup', 'Suspension', 'Cream', 'Ointment', 'Solution', 'Patch', 'Other'] as $form): ?>
                            <option value="<?= $form ?>" <?= old('dosage_form', $medicine['dosage_form'] ?? '') === $form ? 'selected' : '' ?>><?= $form ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="dosage_strength" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Strength</label>
                    <input type="text" id="dosage_strength" name="dosage_strength" value="<?= old('dosage_strength', $medicine['dosage_strength'] ?? '') ?>" placeholder="e.g. 500mg" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label for="unit" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Unit <span aria-hidden="true">*</span><span class="sr-only">required</span></label>
                    <select id="unit" name="unit" required aria-required="true" <?= isset($errors['unit']) ? 'aria-invalid="true" aria-describedby="err-unit"' : '' ?> data-synapse-dropdown>
                        <?php foreach (['tablets', 'capsules', 'ml', 'pcs', 'sachets', 'bottles', 'tubes', 'rolls'] as $u): ?>
                            <option value="<?= $u ?>" <?= old('unit', $medicine['unit'] ?? '') === $u ? 'selected' : '' ?>><?= $u ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['unit'])): ?>
                        <p id="err-unit" role="alert" style="margin: 0.3rem 0 0; color: #DC2626; font-size: 0.75rem;"><?= esc($errors['unit']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="reorder_threshold" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Reorder Threshold</label>
                    <input type="number" id="reorder_threshold" name="reorder_threshold" value="<?= old('reorder_threshold', $medicine['reorder_threshold'] ?? 10) ?>" min="0" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label for="description" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Description</label>
                <textarea id="description" name="description" rows="2" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif; resize: vertical;"><?= old('description', $medicine['description'] ?? '') ?></textarea>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; padding-top: 1rem; border-top: 1px solid #E5E7EB;">
                <button type="submit" style="padding: 0.6rem 1.5rem; background: #10B981; color: white; border: none; border-radius: 0.5rem; font-family: 'Inter', sans-serif; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-save" style="margin-right: 0.25rem;"></i> <?= $isEdit ? 'Update' : 'Add Medicine' ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>