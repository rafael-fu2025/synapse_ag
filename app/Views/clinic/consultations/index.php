<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Stats Cards -->
<?php if (isset($stats) && $stats): ?>
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-clipboard-list"></i></div>
        <div class="stat-info">
            <h3><?= $stats['total'] ?></h3>
            <p>Total Today</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-info">
            <h3><?= $stats['in_progress'] ?></h3>
            <p>In Progress</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <h3><?= $stats['completed'] ?></h3>
            <p>Completed</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-info">
            <h3><?= $stats['follow_up'] ?></h3>
            <p>Follow-Up</p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Queue Table -->
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <span><i class="fas fa-list-check" style="margin-right: 0.5rem; color: #3B82F6;"></i> Consultation Queue</span>
        <a href="/clinic/students"
           data-synapse-form-link
           data-dialog-title="New Consultation"
           data-dialog-icon="fas fa-stethoscope"
           data-dialog-width
           style="padding: 0.4rem 0.75rem; background: var(--primary-600); color: white; border-radius: 0.375rem; font-size: 0.75rem; text-decoration: none; font-weight: 500;">
            <i class="fas fa-plus"></i> New Consultation
        </a>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($queue)): ?>
            <div style="padding: 3rem; text-align: center; color: #9CA3AF;">
                <i class="fas fa-clipboard-check" style="font-size: 2rem; display: block; margin-bottom: 0.75rem;"></i>
                <p style="font-size: 0.9rem; font-weight: 500;">No consultations today</p>
                <p style="font-size: 0.8rem; margin-top: 0.25rem;">Start by checking in a student from the <a href="/clinic/students" style="color: var(--primary-600);">student list</a>.</p>
            </div>
        <?php else: ?>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                <thead>
                    <tr style="background: #F9FAFB; border-bottom: 1px solid #E5E7EB;">
                        <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Time</th>
                        <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Student</th>
                        <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Chief Complaint</th>
                        <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Priority</th>
                        <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Status</th>
                        <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Attending</th>
                        <th style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #374151;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($queue as $c): ?>
                        <tr style="border-bottom: 1px solid #F3F4F6; transition: background 150ms;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='white'">
                            <td style="padding: 0.6rem 1rem; white-space: nowrap; font-weight: 500;"><?= date('h:i A', strtotime($c['consultation_date'])) ?></td>
                            <td style="padding: 0.6rem 1rem;">
                                <div style="font-weight: 500;"><?= esc($c['student_first'] . ' ' . $c['student_last']) ?></div>
                                <div style="font-size: 0.75rem; color: #6B7280;"><?= esc($c['student_number']) ?></div>
                            </td>
                            <td style="padding: 0.6rem 1rem; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= esc($c['chief_complaint']) ?></td>
                            <td style="padding: 0.6rem 1rem;">
                                <?php if ($c['triage_priority']): ?>
                                    <span style="padding: 0.2rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; <?php
                                        echo match($c['triage_priority']) {
                                            'urgent' => 'background: #FEF2F2; color: #DC2626;',
                                            'high'   => 'background: #FFF7ED; color: #EA580C;',
                                            'medium' => 'background: #FFFBEB; color: #D97706;',
                                            default  => 'background: #ECFDF5; color: #059669;',
                                        };
                                    ?>"><?= ucfirst($c['triage_priority']) ?></span>
                                <?php else: ?>
                                    <span style="color: #D1D5DB;">â€”</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.6rem 1rem;">
                                <span style="padding: 0.2rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; <?php
                                    echo match($c['status']) {
                                        'completed' => 'background: #ECFDF5; color: #059669;',
                                        'follow_up' => 'background: #EFF6FF; color: #2563EB;',
                                        default     => 'background: #FFFBEB; color: #D97706;',
                                    };
                                ?>"><?= ucfirst(str_replace('_', ' ', $c['status'])) ?></span>
                            </td>
                            <td style="padding: 0.6rem 1rem; font-size: 0.8rem; color: #6B7280;"><?= esc($c['staff_first'] . ' ' . $c['staff_last']) ?></td>
                            <td style="padding: 0.6rem 1rem; text-align: center;">
                                <a href="/clinic/consultations/<?= $c['id'] ?>" style="padding: 0.25rem 0.5rem; background: var(--primary-50); color: var(--primary-600); border-radius: 0.375rem; font-size: 0.7rem; text-decoration: none; font-weight: 500;">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
