<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card" style="border-left: 3px solid #10B981;">
        <div class="stat-icon green"><i class="fas fa-rocket"></i></div>
        <div class="stat-info"><h3><?= $stats['active'] ?></h3><p>Active Programs</p></div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #F59E0B;">
        <div class="stat-icon orange"><i class="fas fa-drafting-compass"></i></div>
        <div class="stat-info"><h3><?= $stats['planning'] ?></h3><p>In Planning</p></div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #3B82F6;">
        <div class="stat-icon blue"><i class="fas fa-calendar-day"></i></div>
        <div class="stat-info"><h3><?= $stats['upcoming'] ?></h3><p>Upcoming Activities</p></div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #6B7280;">
        <div class="stat-icon" style="background: #F3F4F6; color: #6B7280;"><i class="fas fa-check-double"></i></div>
        <div class="stat-info"><h3><?= $stats['completed'] ?></h3><p>Completed</p></div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.25rem;">
    <!-- Programs Table -->
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-people-carry-box" style="margin-right: 0.5rem; color: #10B981;"></i> Programs</span>
            <a href="/pasimeo/programs/create"
               data-synapse-form-link
               data-dialog-title="Create Program"
               data-dialog-icon="fas fa-plus-circle"
               style="padding: 0.3rem 0.6rem; background: #10B981; color: white; border-radius: 0.375rem; font-size: 0.7rem; text-decoration: none; font-weight: 500;">+ New Program</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($programs)): ?>
                <div style="padding: 2.5rem; text-align: center; color: #9CA3AF;">
                    <i class="fas fa-folder-open" style="font-size: 1.5rem; display: block; margin-bottom: 0.5rem;"></i>
                    No programs created yet.
                </div>
            <?php else: ?>
                <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                    <thead>
                        <tr style="background: #F9FAFB; border-bottom: 1px solid #E5E7EB;">
                            <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Program</th>
                            <th style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #374151;">Activities</th>
                            <th style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #374151;">Hours</th>
                            <th style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #374151;">Status</th>
                            <th style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #374151;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($programs as $p): ?>
                            <tr style="border-bottom: 1px solid #F3F4F6;">
                                <td style="padding: 0.6rem 1rem;">
                                    <div style="font-weight: 600;"><?= esc($p['name']) ?></div>
                                    <div style="font-size: 0.7rem; color: #6B7280;">Coord: <?= esc($p['coord_first'] . ' ' . $p['coord_last']) ?></div>
                                </td>
                                <td style="padding: 0.6rem 1rem; text-align: center;">
                                    <span style="font-weight: 600;"><?= $p['completed_count'] ?></span><span style="color: #9CA3AF;">/<?= $p['activity_count'] ?></span>
                                </td>
                                <td style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #10B981;"><?= number_format($p['total_hours'], 1) ?>h</td>
                                <td style="padding: 0.6rem 1rem; text-align: center;">
                                    <span style="padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; <?php
                                        echo match($p['status']) {
                                            'active'    => 'background: #ECFDF5; color: #059669;',
                                            'planning'  => 'background: #FFFBEB; color: #D97706;',
                                            'completed' => 'background: #F3F4F6; color: #6B7280;',
                                            'cancelled' => 'background: #FEF2F2; color: #DC2626;',
                                            default     => 'background: #F3F4F6; color: #6B7280;',
                                        };
                                    ?>"><?= ucfirst($p['status']) ?></span>
                                </td>
                                <td style="padding: 0.6rem 1rem; text-align: center;">
                                    <a href="/pasimeo/programs/<?= $p['id'] ?>" style="padding: 0.2rem 0.5rem; background: var(--primary-50); color: var(--primary-600); border-radius: 0.375rem; font-size: 0.65rem; text-decoration: none; font-weight: 500;">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Upcoming Activities Sidebar -->
    <div class="card">
        <div class="card-header"><i class="fas fa-calendar-alt" style="margin-right: 0.5rem; color: #3B82F6;"></i> Upcoming Activities</div>
        <div class="card-body">
            <?php if (empty($upcoming)): ?>
                <p style="font-size: 0.8rem; color: #9CA3AF; text-align: center;">No upcoming activities.</p>
            <?php else: ?>
                <?php foreach ($upcoming as $a): ?>
                    <a href="/pasimeo/activities/<?= $a['id'] ?>" style="display: block; padding: 0.5rem 0; border-bottom: 1px solid #F3F4F6; text-decoration: none; color: inherit;">
                        <p style="font-size: 0.8rem; font-weight: 600; color: #111827;"><?= esc($a['title']) ?></p>
                        <div style="display: flex; justify-content: space-between; font-size: 0.7rem; color: #6B7280; margin-top: 0.15rem;">
                            <span><?= date('M d', strtotime($a['activity_date'])) ?> â€¢ <?= date('h:i A', strtotime($a['start_time'])) ?></span>
                            <span><?= esc($a['program_name']) ?></span>
                        </div>
                        <?php if ($a['location']): ?>
                            <p style="font-size: 0.65rem; color: #9CA3AF; margin-top: 0.1rem;"><i class="fas fa-map-pin"></i> <?= esc($a['location']) ?></p>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
