<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.25rem;">
    <!-- Left: Assignment Form -->
    <div class="card">
        <div class="card-header"><i class="fas fa-user-plus" style="margin-right: 0.5rem; color: #F59E0B;"></i> Assign Volunteers to: <?= esc($activity['title']) ?></div>
        <div class="card-body">
            <div style="margin-bottom: 1rem; padding: 0.5rem; background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 0.375rem; font-size: 0.8rem; color: #1E40AF;">
                <strong><?= date('M d, Y', strtotime($activity['activity_date'])) ?></strong>
                â€¢ <?= date('h:i A', strtotime($activity['start_time'])) ?> â€“ <?= date('h:i A', strtotime($activity['end_time'])) ?>
                <?php if ($activity['location']): ?> â€¢ <?= esc($activity['location']) ?><?php endif; ?>
                <?php if ($activity['max_volunteers']): ?> â€¢ Max: <?= $activity['max_volunteers'] ?> volunteers<?php endif; ?>
            </div>

        <?php $errors = session()->get('errors') ?? []; ?>

        <form method="POST" action="/pasimeo/volunteers/store" novalidate
              data-synapse-form-dialog
              data-dialog-title="Assign Volunteers"
              data-dialog-icon="fas fa-user-plus"
              data-dialog-submit-label="Assign Volunteers"
              data-dialog-cancel-label="Cancel"
              data-dialog-width>
            <?= csrf_field() ?>
            <input type="hidden" name="activity_id" value="<?= $activity['id'] ?>">

            <?php if (! empty($errors)): ?>
                <div role="alert" id="volunteer-errors" class="syn-alert syn-alert--danger" style="margin-bottom: 1.25rem;">
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
                <label for="assigned_role" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Assigned Role (optional)</label>
                <input id="assigned_role" type="text" name="assigned_role" value="<?= esc(old('assigned_role')) ?>" placeholder="e.g., Facilitator, Marshal, First Aid" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
            </div>

            <fieldset style="margin-bottom: 1.25rem; padding: 0; border: 0;">
                <legend style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem; padding: 0;">Select Volunteers * <span style="font-weight: 400; color: #9CA3AF;">(<?= count($availableUsers) ?> available)</span></legend>

                <div style="max-height: 350px; overflow-y: auto; border: 1px solid #E5E7EB; border-radius: 0.375rem;">
                    <?php if (empty($availableUsers)): ?>
                        <div style="padding: 2rem; text-align: center; color: #9CA3AF;">No available users to assign.</div>
                    <?php else: ?>
                        <?php foreach ($availableUsers as $u): ?>
                            <label style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.65rem 0.75rem; border-bottom: 1px solid #F3F4F6; cursor: pointer; transition: background 100ms; <?= $u['has_conflict'] ? 'opacity: 0.7; background: #FFF5F5;' : '' ?>" onmouseenter="this.style.background='<?= $u['has_conflict'] ? '#FFF5F5' : '#F9FAFB' ?>'" onmouseleave="this.style.background='<?= $u['has_conflict'] ? '#FFF5F5' : 'white' ?>'">
                                <input type="checkbox" name="user_ids[]" value="<?= $u['id'] ?>" <?= $u['has_conflict'] ? 'disabled' : '' ?> aria-label="Assign <?= esc($u['first_name'] . ' ' . $u['last_name']) ?><?= $u['has_conflict'] ? ' (has conflict)' : '' ?>" style="width: 16px; height: 16px; accent-color: #F59E0B; margin-top: 0.2rem;">
                                <div style="flex: 1;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="font-size: 0.85rem; font-weight: 600; color: <?= $u['has_conflict'] ? '#991B1B' : '#111827' ?>;"><?= esc($u['first_name'] . ' ' . $u['last_name']) ?></span>
                                        <span style="font-size: 0.7rem; font-weight: 500; color: #6B7280;">Workload: <?= $u['workload_score'] ?>% (<?= $u['hours_committed'] ?> hrs)</span>
                                    </div>

                                    <div style="width: 100%; height: 4px; background: #E5E7EB; border-radius: 2px; margin-top: 0.25rem; overflow: hidden;">
                                        <div style="width: <?= $u['workload_score'] ?>%; height: 100%; background: <?php
                                            if ($u['workload_score'] <= 30) echo '#10B981';
                                            elseif ($u['workload_score'] <= 70) echo '#F59E0B';
                                            else echo '#EF4444';
                                        ?>;"></div>
                                    </div>

                                    <?php if ($u['has_conflict']): ?>
                                        <p style="font-size: 0.7rem; color: #DC2626; font-weight: 500; margin-top: 0.25rem;">
                                            <i class="fas fa-triangle-exclamation"></i> <?= esc($u['conflict_reason']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </fieldset>
                        <i class="fas fa-user-plus"></i> Assign Selected
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right: AI Alternatives -->
    <div>
        <div class="card">
            <div class="card-header"><i class="fas fa-robot" style="margin-right: 0.5rem; color: var(--primary-500);"></i> AI Alternatives</div>
            <div class="card-body" style="padding: 0.75rem;">
                <p style="font-size: 0.75rem; color: #6B7280; margin-bottom: 0.75rem;">Least-loaded conflict-free student volunteers:</p>
                <?php if (empty($alternatives)): ?>
                    <p style="font-size: 0.75rem; color: #9CA3AF; text-align: center; padding: 1rem;">No alternatives found.</p>
                <?php else: ?>
                    <div style="display: grid; gap: 0.5rem;">
                        <?php foreach ($alternatives as $alt): ?>
                            <div style="padding: 0.6rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; background: #F9FAFB;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                                    <span style="font-size: 0.8rem; font-weight: 600; color: #374151;"><?= esc($alt['name']) ?></span>
                                    <span style="font-size: 0.65rem; padding: 0.1rem 0.35rem; border-radius: 999px; background: #ECFDF5; color: #059669; font-weight: 600;"><?= $alt['workload_score'] ?>%</span>
                                </div>
                                <div style="font-size: 0.7rem; color: #6B7280; display: flex; justify-content: space-between;">
                                    <span>Committed: <?= $alt['hours_committed'] ?> hrs</span>
                                    <span style="color: var(--primary-600); font-weight: 500;">Avail: <?= number_format($alt['availability_score'] * 100, 0) ?>%</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
