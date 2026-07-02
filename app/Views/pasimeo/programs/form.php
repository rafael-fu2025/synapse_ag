<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card" style="max-width: 650px;">
    <div class="card-header"><i class="fas fa-<?= $program ? 'edit' : 'plus-circle' ?>" style="margin-right: 0.5rem; color: #10B981;"></i> <?= $program ? 'Edit Program' : 'Create Program' ?></div>
    <div class="card-body">
        <?php $errors = session()->get('errors') ?? []; ?>

        <form method="POST" action="<?= $program ? '/pasimeo/programs/update/' . $program['id'] : '/pasimeo/programs/store' ?>" novalidate
              data-synapse-form-dialog
              data-dialog-title="<?= $program ? 'Edit Program' : 'Create Program' ?>"
              data-dialog-icon="fas fa-<?= $program ? 'edit' : 'plus-circle' ?>"
              data-dialog-submit-label="<?= $program ? 'Update Program' : 'Create Program' ?>"
              data-dialog-cancel-label="Cancel">
            <?= csrf_field() ?>

            <?php if (! empty($errors)): ?>
                <div role="alert" id="program-errors" class="syn-alert syn-alert--danger" style="margin-bottom: 1.25rem;">
                    <i class="fas fa-triangle-exclamation"></i>
                    <div>
                        <strong>Please fix the following before saving:</strong>
                        <ul style="margin: 0.5rem 0 0; padding-left: 1.25rem;">
                            <?php foreach ($errors as $field => $msg): ?>
                                <li><?= esc($msg) ?></li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <div style="margin-bottom: 1.25rem;">
                <label for="program_name" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Program Name *</label>
                <input id="program_name" type="text" name="name" required aria-required="true" value="<?= esc($program['name'] ?? old('name')) ?>"
                    <?= isset($errors['name']) ? 'aria-invalid="true" aria-describedby="program_name-err"' : '' ?>
                    placeholder="e.g., Campus Health Awareness Week" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                <?php if (isset($errors['name'])): ?>
                    <p id="program_name-err" style="margin: 0.3rem 0 0; font-size: 0.75rem; color: #DC2626;"><?= esc($errors['name']) ?></p>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label for="program_description" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Description</label>
                <textarea id="program_description" name="description" rows="3" placeholder="Program objectives and scope..." style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif; resize: vertical;"><?= esc($program['description'] ?? old('description')) ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label for="start_date" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Start Date</label>
                    <input id="start_date" type="text" class="syn-datepicker" name="start_date" value="<?= esc($program['start_date'] ?? old('start_date')) ?>" placeholder="YYYY-MM-DD" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
                <div>
                    <label for="end_date" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">End Date</label>
                    <input id="end_date" type="text" class="syn-datepicker" name="end_date" value="<?= esc($program['end_date'] ?? old('end_date')) ?>" placeholder="YYYY-MM-DD" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
            </div>

            <?php if ($program): ?>
            <div style="margin-bottom: 1.25rem;">
                <label for="program_status" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Status</label>
                <select id="program_status" name="status" data-synapse-dropdown>
                    <?php foreach (['planning', 'active', 'completed', 'cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($program['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; padding-top: 1rem; border-top: 1px solid #E5E7EB;">
                <a href="/pasimeo" style="padding: 0.6rem 1.25rem; background: #F3F4F6; color: #374151; border-radius: 0.5rem; font-size: 0.85rem; text-decoration: none;">Cancel</a>
                <button type="submit" style="padding: 0.6rem 1.5rem; background: #10B981; color: white; border: none; border-radius: 0.5rem; font-family: 'Inter', sans-serif; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-<?= $program ? 'save' : 'plus' ?>"></i> <?= $program ? 'Update Program' : 'Create Program' ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
