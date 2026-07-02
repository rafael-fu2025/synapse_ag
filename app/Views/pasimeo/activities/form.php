<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card" style="max-width: 650px;">
    <div class="card-header"><i class="fas fa-plus-circle" style="margin-right: 0.5rem; color: #3B82F6;"></i> Create Activity</div>
    <div class="card-body">
        <?php $errors = session()->get('errors') ?? []; ?>

        <div style="margin-bottom: 1rem; padding: 0.5rem; background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 0.375rem; font-size: 0.8rem; color: #065F46;">
            <strong>Program:</strong> <?= esc($program['name']) ?>
        </div>

        <form method="POST" action="/pasimeo/activities/store" novalidate
              data-synapse-form-dialog
              data-dialog-title="Create Activity"
              data-dialog-icon="fas fa-plus-circle"
              data-dialog-submit-label="Create Activity"
              data-dialog-cancel-label="Cancel">
            <?= csrf_field() ?>
            <input type="hidden" name="program_id" value="<?= $program['id'] ?>">

            <?php if (! empty($errors)): ?>
                <div role="alert" id="activity-errors" class="syn-alert syn-alert--danger" style="margin-bottom: 1.25rem;">
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
                <label for="activity_title" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Activity Title *</label>
                <input id="activity_title" type="text" name="title" required aria-required="true"
                    <?= isset($errors['title']) ? 'aria-invalid="true" aria-describedby="activity_title-err"' : '' ?>
                    value="<?= esc(old('title')) ?>"
                    placeholder="e.g., Blood Donation Drive" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                <?php if (isset($errors['title'])): ?>
                    <p id="activity_title-err" style="margin: 0.3rem 0 0; font-size: 0.75rem; color: #DC2626;"><?= esc($errors['title']) ?></p>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label for="activity_description" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Description</label>
                <textarea id="activity_description" name="description" rows="2" placeholder="Activity details..." style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif; resize: vertical;"><?= esc(old('description')) ?></textarea>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label for="activity_location" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Location</label>
                <input id="activity_location" type="text" name="location" value="<?= esc(old('location')) ?>" placeholder="e.g., Student Center, Room 201" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label for="activity_date" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Date *</label>
                    <input id="activity_date" type="text" class="syn-datepicker" name="activity_date" required aria-required="true" min="<?= date('Y-m-d') ?>" value="<?= esc(old('activity_date')) ?>" placeholder="YYYY-MM-DD" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
                <div>
                    <label for="activity_start_time" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Start Time *</label>
                    <input id="activity_start_time" type="text" class="syn-datepicker syn-datepicker--time-only" name="start_time" required aria-required="true" value="<?= esc(old('start_time', '08:00')) ?>" placeholder="HH:MM" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
                <div>
                    <label for="activity_end_time" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">End Time *</label>
                    <input id="activity_end_time" type="text" class="syn-datepicker syn-datepicker--time-only" name="end_time" required aria-required="true" value="<?= esc(old('end_time', '12:00')) ?>" placeholder="HH:MM" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label for="activity_max_volunteers" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Max Volunteers (optional)</label>
                <input id="activity_max_volunteers" type="number" name="max_volunteers" min="1" value="<?= esc(old('max_volunteers')) ?>" placeholder="Leave blank for unlimited" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; padding-top: 1rem; border-top: 1px solid #E5E7EB;">
                <a href="/pasimeo/programs/<?= $program['id'] ?>" style="padding: 0.6rem 1.25rem; background: #F3F4F6; color: #374151; border-radius: 0.5rem; font-size: 0.85rem; text-decoration: none;">Cancel</a>
                <button type="submit" style="padding: 0.6rem 1.5rem; background: #3B82F6; color: white; border: none; border-radius: 0.5rem; font-family: 'Inter', sans-serif; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-plus"></i> Create Activity
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
