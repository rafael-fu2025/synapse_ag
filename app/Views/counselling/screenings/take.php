<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card" style="max-width: 750px;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <span><i class="fas fa-clipboard-list" style="margin-right: 0.5rem; color: #EF4444;"></i> <?= esc($template['title']) ?></span>
        <span style="font-size: 0.7rem; color: #6B7280; text-transform: capitalize;"><?= $template['type'] ?> â€¢ <?= count($template['questions']) ?> items</span>
    </div>
    <div class="card-body">
        <?php if ($template['description']): ?>
            <p style="font-size: 0.8rem; color: #6B7280; margin-bottom: 1.25rem; padding: 0.6rem; background: #F9FAFB; border-radius: 0.375rem;"><?= esc($template['description']) ?></p>
        <?php endif; ?>

        <!-- Student Selector (if not pre-selected) -->
        <?php if (! $student): ?>
            <div style="margin-bottom: 1.25rem; padding: 1rem; background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 0.5rem;">
                <p style="font-size: 0.8rem; color: #92400E; font-weight: 600;">âš  No student selected. Please enter a student ID.</p>
            </div>
        <?php endif; ?>

        <?php $errors = session()->get('errors') ?? []; ?>

        <form method="POST" action="/counselling/screenings/submit" novalidate
              data-synapse-form-dialog
              data-dialog-title="<?= esc($template['title']) ?>"
              data-dialog-icon="fas fa-clipboard-list"
              data-dialog-submit-label="Submit Screening"
              data-dialog-cancel-label="Cancel"
              data-dialog-width>
            <?= csrf_field() ?>
            <input type="hidden" name="template_id" value="<?= $template['id'] ?>">
            <input type="hidden" name="appointment_id" value="<?= esc($appointmentId ?? '') ?>">

            <?php if (! empty($errors)): ?>
                <div role="alert" id="screening-errors" class="syn-alert syn-alert--danger" style="margin-bottom: 1.25rem;">
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

            <?php if ($student): ?>
                <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                <div style="margin-bottom: 1.25rem; padding: 0.6rem; background: #F5F3FF; border: 1px solid #DDD6FE; border-radius: 0.375rem; display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #8B5CF6; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.75rem; flex-shrink: 0;"><?= strtoupper(substr($student['first_name'], 0, 1)) ?><?= strtoupper(substr($student['last_name'], 0, 1)) ?></div>
                    <div>
                        <p style="font-size: 0.85rem; font-weight: 600;"><?= esc($student['full_name']) ?></p>
                        <p style="font-size: 0.7rem; color: #6D28D9;"><?= esc($student['student_number']) ?></p>
                    </div>
                </div>
            <?php else: ?>
                <div style="margin-bottom: 1.25rem;">
                    <label for="student_id" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Student ID *</label>
                    <input id="student_id" type="number" name="student_id" required aria-required="true" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
            <?php endif; ?>

            <!-- Questions -->
            <?php foreach ($template['questions'] as $i => $q): ?>
                <div style="margin-bottom: 1.5rem; padding: 1rem; background: white; border: 1px solid #E5E7EB; border-radius: 0.5rem;">
                    <p style="font-size: 0.85rem; font-weight: 600; color: #111827; margin-bottom: 0.75rem;">
                        <span style="color: #8B5CF6; font-weight: 700;"><?= $i + 1 ?>.</span> <?= esc($q['question_text']) ?>
                        <?php if ($q['is_required']): ?><span style="color: #EF4444;">*</span><?php endif; ?>
                    </p>

                    <?php if ($q['question_type'] === 'likert'): ?>
                        <fieldset style="margin: 0; padding: 0; border: 0;">
                            <legend class="sr-only" style="position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden;"><?= esc($q['question_text']) ?></legend>
                            <div role="radiogroup" aria-label="<?= esc($q['question_text']) ?>" <?= $q['is_required'] ? 'aria-required="true"' : '' ?> style="display: flex; gap: 0; border-radius: 0.375rem; overflow: hidden; border: 1px solid #E5E7EB;">
                                <?php
                                $options = $q['options'] ?? [
                                    ['value' => 0, 'label' => 'Not at all'],
                                    ['value' => 1, 'label' => 'Several days'],
                                    ['value' => 2, 'label' => 'More than half'],
                                    ['value' => 3, 'label' => 'Nearly every day'],
                                ];
                                foreach ($options as $opt): ?>
                                    <label style="flex: 1; padding: 0.5rem 0.25rem; text-align: center; cursor: pointer; transition: all 150ms; border-right: 1px solid #E5E7EB; background: white;">
                                        <input type="radio" name="q_<?= $q['id'] ?>" value="<?= $opt['value'] ?>" aria-label="<?= esc($opt['label']) ?>" <?= $q['is_required'] ? 'required' : '' ?> style="display: none;" onchange="highlightLikert(this)">
                                        <div style="font-size: 1.1rem; font-weight: 700;"><?= $opt['value'] ?></div>
                                        <div style="font-size: 0.6rem; color: #6B7280; line-height: 1.2;"><?= esc($opt['label']) ?></div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>

                    <?php elseif ($q['question_type'] === 'text'): ?>
                        <label for="q_<?= $q['id'] ?>" class="sr-only" style="position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden;"><?= esc($q['question_text']) ?></label>
                        <textarea id="q_<?= $q['id'] ?>" name="q_<?= $q['id'] ?>" rows="2" <?= $q['is_required'] ? 'required aria-required="true"' : '' ?> style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif; resize: vertical;"></textarea>

                    <?php elseif ($q['question_type'] === 'yes_no'): ?>
                        <fieldset style="margin: 0; padding: 0; border: 0;">
                            <legend class="sr-only" style="position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden;"><?= esc($q['question_text']) ?></legend>
                            <div role="radiogroup" aria-label="<?= esc($q['question_text']) ?>" <?= $q['is_required'] ? 'aria-required="true"' : '' ?> style="display: flex; gap: 0.5rem;">
                                <label style="flex: 1; padding: 0.5rem; border: 2px solid #E5E7EB; border-radius: 0.375rem; text-align: center; cursor: pointer;">
                                    <input type="radio" name="q_<?= $q['id'] ?>" value="1" aria-label="Yes" <?= $q['is_required'] ? 'required' : '' ?> style="display: none;" onchange="highlightLikert(this)">
                                    <span style="font-size: 0.85rem; font-weight: 600;">Yes</span>
                                </label>
                                <label style="flex: 1; padding: 0.5rem; border: 2px solid #E5E7EB; border-radius: 0.375rem; text-align: center; cursor: pointer;">
                                    <input type="radio" name="q_<?= $q['id'] ?>" value="0" aria-label="No" style="display: none;" onchange="highlightLikert(this)">
                                    <span style="font-size: 0.85rem; font-weight: 600;">No</span>
                                </label>
                            </div>
                        </fieldset>

                    <?php elseif ($q['question_type'] === 'scale'): ?>
                        <label for="q_<?= $q['id'] ?>" class="sr-only" style="position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden;"><?= esc($q['question_text']) ?></label>
                        <input id="q_<?= $q['id'] ?>" type="range" name="q_<?= $q['id'] ?>" min="1" max="5" value="3" style="width: 100%;" oninput="this.nextElementSibling.textContent = this.value">
                        <span style="font-size: 0.85rem; font-weight: 600; color: var(--primary-600);">3</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; padding-top: 1rem; border-top: 1px solid #E5E7EB;">
                <a href="/counselling/screenings" style="padding: 0.6rem 1.25rem; background: #F3F4F6; color: #374151; border-radius: 0.5rem; font-size: 0.85rem; text-decoration: none;">Cancel</a>
                <button type="submit" style="padding: 0.6rem 1.5rem; background: #EF4444; color: white; border: none; border-radius: 0.5rem; font-family: 'Inter', sans-serif; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-paper-plane"></i> Submit Screening
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function highlightLikert(radio) {
    const parent = radio.closest('div[style*="display: flex"]') || radio.closest('div');
    parent.querySelectorAll('label').forEach(l => {
        l.style.background = 'white';
        l.style.borderColor = '#E5E7EB';
    });
    radio.parentElement.style.background = '#F5F3FF';
    radio.parentElement.style.borderColor = '#8B5CF6';
}
</script>
<?= $this->endSection() ?>
