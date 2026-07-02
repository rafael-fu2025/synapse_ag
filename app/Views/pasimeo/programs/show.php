<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
    <!-- Program Info -->
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-people-carry-box" style="margin-right: 0.5rem; color: #10B981;"></i> Program Details</span>
            <a href="/pasimeo/programs/edit/<?= $program['id'] ?>" style="padding: 0.2rem 0.5rem; background: #F3F4F6; color: #374151; border-radius: 0.375rem; font-size: 0.65rem; text-decoration: none;">Edit</a>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; font-size: 0.8rem; margin-bottom: 1rem;">
                <div><span style="color: #9CA3AF;">Coordinator:</span> <?= esc($program['coord_first'] . ' ' . $program['coord_last']) ?></div>
                <div><span style="color: #9CA3AF;">Status:</span>
                    <span style="padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; <?php
                        echo match($program['status']) {
                            'active'    => 'background: #ECFDF5; color: #059669;',
                            'planning'  => 'background: #FFFBEB; color: #D97706;',
                            'completed' => 'background: #F3F4F6; color: #6B7280;',
                            default     => 'background: #FEF2F2; color: #DC2626;',
                        };
                    ?>"><?= ucfirst($program['status']) ?></span>
                </div>
                <div><span style="color: #9CA3AF;">Start:</span> <?= $program['start_date'] ? date('M d, Y', strtotime($program['start_date'])) : '—' ?></div>
                <div><span style="color: #9CA3AF;">End:</span> <?= $program['end_date'] ? date('M d, Y', strtotime($program['end_date'])) : '—' ?></div>
            </div>

            <?php if ($program['description']): ?>
                <div style="padding: 0.6rem; background: #F9FAFB; border-radius: 0.375rem;">
                    <p style="font-size: 0.8rem;"><?= esc($program['description']) ?></p>
                </div>
            <?php endif; ?>

            <!-- Quick Stats -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.5rem; margin-top: 1rem;">
                <div style="text-align: center; padding: 0.75rem; background: #ECFDF5; border-radius: 0.375rem;">
                    <p style="font-size: 1.25rem; font-weight: 800; color: #059669;"><?= $program['activity_count'] ?></p>
                    <p style="font-size: 0.65rem; color: #6B7280;">Activities</p>
                </div>
                <div style="text-align: center; padding: 0.75rem; background: #EFF6FF; border-radius: 0.375rem;">
                    <p style="font-size: 1.25rem; font-weight: 800; color: #2563EB;"><?= $program['completed_count'] ?></p>
                    <p style="font-size: 0.65rem; color: #6B7280;">Completed</p>
                </div>
                <div style="text-align: center; padding: 0.75rem; background: #F5F3FF; border-radius: 0.375rem;">
                    <p style="font-size: 1.25rem; font-weight: 800; color: #7C3AED;"><?= number_format($program['total_hours'], 1) ?>h</p>
                    <p style="font-size: 0.65rem; color: #6B7280;">Total Hours</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Activities List -->
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-list-check" style="margin-right: 0.5rem; color: #3B82F6;"></i> Activities</span>
            <a href="/pasimeo/activities/create/<?= $program['id'] ?>"
               data-synapse-form-link
               data-dialog-title="Create Activity"
               data-dialog-icon="fas fa-plus-circle"
               style="padding: 0.3rem 0.6rem; background: #3B82F6; color: white; border-radius: 0.375rem; font-size: 0.7rem; text-decoration: none; font-weight: 500;">+ Add Activity</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($program['activities'])): ?>
                <div style="padding: 2rem; text-align: center; color: #9CA3AF;">No activities yet.</div>
            <?php else: ?>
                <?php foreach ($program['activities'] as $a): ?>
                    <a href="/pasimeo/activities/<?= $a['id'] ?>" style="display: block; padding: 0.75rem 1rem; border-bottom: 1px solid #F3F4F6; text-decoration: none; color: inherit; transition: background 150ms;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <p style="font-size: 0.85rem; font-weight: 600; color: #111827;"><?= esc($a['title']) ?></p>
                                <p style="font-size: 0.7rem; color: #6B7280;">
                                    <?= date('M d, Y', strtotime($a['activity_date'])) ?>
                                    • <?= date('h:i A', strtotime($a['start_time'])) ?> – <?= date('h:i A', strtotime($a['end_time'])) ?>
                                    <?php if ($a['location']): ?> • <i class="fas fa-map-pin"></i> <?= esc($a['location']) ?><?php endif; ?>
                                </p>
                            </div>
                            <span style="padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.6rem; font-weight: 600; <?php
                                echo match($a['status']) {
                                    'upcoming'  => 'background: #EFF6FF; color: #2563EB;',
                                    'ongoing'   => 'background: #ECFDF5; color: #059669;',
                                    'completed' => 'background: #F3F4F6; color: #6B7280;',
                                    'cancelled' => 'background: #FEF2F2; color: #DC2626;',
                                    default     => 'background: #F3F4F6; color: #6B7280;',
                                };
                            ?>"><?= ucfirst($a['status']) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
