<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
    <!-- Activity Info -->
    <div>
        <div class="card">
            <div class="card-header"><i class="fas fa-calendar-day" style="margin-right: 0.5rem; color: #3B82F6;"></i> Activity Details</div>
            <div class="card-body">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                    <span style="padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; <?php
                        echo match($activity['status']) {
                            'upcoming'  => 'background: #EFF6FF; color: #2563EB;',
                            'ongoing'   => 'background: #ECFDF5; color: #059669;',
                            'completed' => 'background: #F3F4F6; color: #6B7280;',
                            default     => 'background: #FEF2F2; color: #DC2626;',
                        };
                    ?>"><?= ucfirst($activity['status']) ?></span>
                    <span style="font-size: 0.75rem; color: #6B7280;"><?= esc($activity['program_name']) ?></span>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; font-size: 0.8rem;">
                    <div><span style="color: #9CA3AF;"><i class="fas fa-calendar"></i></span> <?= date('l, M d, Y', strtotime($activity['activity_date'])) ?></div>
                    <div><span style="color: #9CA3AF;"><i class="fas fa-clock"></i></span> <?= date('h:i A', strtotime($activity['start_time'])) ?> – <?= date('h:i A', strtotime($activity['end_time'])) ?></div>
                    <?php if ($activity['location']): ?>
                        <div style="grid-column: 1 / -1;"><span style="color: #9CA3AF;"><i class="fas fa-map-pin"></i></span> <?= esc($activity['location']) ?></div>
                    <?php endif; ?>
                    <div><span style="color: #9CA3AF;">Volunteers:</span> <strong><?= $activity['confirmed_count'] ?></strong> confirmed / <?= $activity['volunteer_count'] ?> assigned<?= $activity['max_volunteers'] ? " (max: {$activity['max_volunteers']})" : '' ?></div>
                </div>

                <?php if ($activity['description']): ?>
                    <div style="margin-top: 0.75rem; padding: 0.6rem; background: #F9FAFB; border-radius: 0.375rem; font-size: 0.8rem;"><?= esc($activity['description']) ?></div>
                <?php endif; ?>

                <!-- Status Actions -->
                <?php if (in_array($activity['status'], ['upcoming', 'ongoing'])): ?>
                <div style="display: flex; gap: 0.5rem; margin-top: 1rem; flex-wrap: wrap;">
                    <?php if ($activity['status'] === 'upcoming'): ?>
                        <form method="POST" action="/pasimeo/activities/status/<?= $activity['id'] ?>" style="display: inline;"><?= csrf_field() ?>
                            <input type="hidden" name="status" value="ongoing">
                            <button type="button"
                                    data-synapse-confirm
                                    data-synapse-confirm-title="Start this activity?"
                                    data-synapse-confirm-body="This will mark the activity as ongoing. You can still mark it as completed later."
                                    data-synapse-confirm-text="Start Activity"
                                    style="padding: 0.4rem 0.75rem; background: #10B981; color: white; border: none; border-radius: 0.375rem; font-size: 0.75rem; cursor: pointer; font-weight: 600;"><i class="fas fa-play"></i> Start Activity</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($activity['status'] === 'ongoing'): ?>
                        <form method="POST" action="/pasimeo/activities/status/<?= $activity['id'] ?>" style="display: inline;"><?= csrf_field() ?>
                            <input type="hidden" name="status" value="completed">
                            <button type="button"
                                    data-synapse-confirm
                                    data-synapse-confirm-title="Mark activity as completed?"
                                    data-synapse-confirm-body="This will close the activity. You'll still be able to view attendance records but cannot reopen the activity."
                                    data-synapse-confirm-text="Mark Completed"
                                    style="padding: 0.4rem 0.75rem; background: #3B82F6; color: white; border: none; border-radius: 0.375rem; font-size: 0.75rem; cursor: pointer; font-weight: 600;"><i class="fas fa-check"></i> Mark Completed</button>
                        </form>
                    <?php endif; ?>
                    <a href="/pasimeo/attendance/<?= $activity['id'] ?>" style="padding: 0.4rem 0.75rem; background: #8B5CF6; color: white; border-radius: 0.375rem; font-size: 0.75rem; text-decoration: none; font-weight: 600;"><i class="fas fa-clipboard-check"></i> Attendance</a>
                    <a href="/pasimeo/volunteers/assign/<?= $activity['id'] ?>"
                       data-synapse-form-link
                       data-dialog-title="Assign Volunteers"
                       data-dialog-icon="fas fa-user-plus"
                       data-dialog-width
                       style="padding: 0.4rem 0.75rem; background: #F59E0B; color: white; border-radius: 0.375rem; font-size: 0.75rem; text-decoration: none; font-weight: 600;"><i class="fas fa-user-plus"></i> Assign Volunteers</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Volunteers List -->
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-users" style="margin-right: 0.5rem; color: #F59E0B;"></i> Volunteers (<?= $activity['volunteer_count'] ?>)</span>
            <a href="/pasimeo/volunteers/assign/<?= $activity['id'] ?>"
               data-synapse-form-link
               data-dialog-title="Assign Volunteers"
               data-dialog-icon="fas fa-user-plus"
               data-dialog-width
               style="padding: 0.2rem 0.5rem; background: #F59E0B; color: white; border-radius: 0.375rem; font-size: 0.6rem; text-decoration: none; font-weight: 500;">+ Assign</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($activity['volunteers'])): ?>
                <div style="padding: 2rem; text-align: center; color: #9CA3AF;">No volunteers assigned yet.</div>
            <?php else: ?>
                <?php foreach ($activity['volunteers'] as $v): ?>
                    <div style="padding: 0.6rem 1rem; border-bottom: 1px solid #F3F4F6; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <p style="font-size: 0.8rem; font-weight: 500;"><?= esc($v['first_name'] . ' ' . $v['last_name']) ?></p>
                            <p style="font-size: 0.65rem; color: #6B7280;"><?= esc($v['assigned_role'] ?? 'Volunteer') ?></p>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.3rem;">
                            <span style="padding: 0.1rem 0.4rem; border-radius: 999px; font-size: 0.6rem; font-weight: 600; <?php
                                echo match($v['status']) {
                                    'confirmed' => 'background: #ECFDF5; color: #059669;',
                                    'declined'  => 'background: #FEF2F2; color: #DC2626;',
                                    'conflict'  => 'background: #FFF7ED; color: #EA580C;',
                                    default     => 'background: #FFFBEB; color: #D97706;',
                                };
                            ?>"><?= ucfirst($v['status']) ?></span>
                            <?php if ($v['status'] === 'conflict' && $v['conflict_reason']): ?>
                                <span title="<?= esc($v['conflict_reason']) ?>" style="cursor: help; font-size: 0.7rem; color: #EA580C;">⚠️</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
