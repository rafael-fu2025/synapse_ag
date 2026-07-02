<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.25rem;">
    <!-- Left: Profile Card -->
    <div>
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 2rem;">
                <div style="width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-500), #8B5CF6); display: inline-flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem;">
                    <?= strtoupper(substr($student['first_name'], 0, 1)) ?><?= strtoupper(substr($student['last_name'], 0, 1)) ?>
                </div>
                <h2 style="font-size: 1.15rem; font-weight: 700; color: #111827;"><?= esc($student['full_name']) ?></h2>
                <p style="font-size: 0.8rem; color: var(--primary-500); font-weight: 600;"><?= esc($student['student_number']) ?></p>
                <p style="font-size: 0.8rem; color: #6B7280; margin-top: 0.25rem;"><?= esc($student['email']) ?></p>

                <div style="margin-top: 1.25rem; display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: center;">
                    <?php if ($student['course']): ?>
                        <span style="padding: 0.25rem 0.6rem; background: var(--primary-50); color: var(--primary-700); border-radius: 999px; font-size: 0.7rem; font-weight: 500;"><?= esc($student['course']) ?> <?= $student['year_level'] ? '- Year ' . $student['year_level'] : '' ?></span>
                    <?php endif; ?>
                    <?php if ($student['blood_type']): ?>
                        <span style="padding: 0.25rem 0.6rem; background: #FEF2F2; color: #DC2626; border-radius: 999px; font-size: 0.7rem; font-weight: 500;"><i class="fas fa-droplet"></i> <?= esc($student['blood_type']) ?></span>
                    <?php endif; ?>
                    <?php if ($student['gender']): ?>
                        <span style="padding: 0.25rem 0.6rem; background: #F3F4F6; color: #374151; border-radius: 999px; font-size: 0.7rem; font-weight: 500; text-transform: capitalize;"><?= esc($student['gender']) ?></span>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 1.25rem; display: flex; gap: 0.5rem; justify-content: center;">
                    <a href="/clinic/consultations/create/<?= $student['id'] ?>"
                       data-synapse-form-link
                       data-dialog-title="New Consultation"
                       data-dialog-icon="fas fa-stethoscope"
                       data-dialog-width
                       style="padding: 0.5rem 1rem; background: var(--primary-600); color: white; border-radius: 0.5rem; font-size: 0.8rem; font-weight: 500; text-decoration: none;">
                        <i class="fas fa-stethoscope"></i> New Consultation
                    </a>
                    <a href="/clinic/students/edit/<?= $student['id'] ?>"
                       data-synapse-form-link
                       data-dialog-title="Edit Student"
                       data-dialog-icon="fas fa-edit"
                       data-dialog-width
                       style="padding: 0.5rem 1rem; background: #F3F4F6; color: #374151; border-radius: 0.5rem; font-size: 0.8rem; font-weight: 500; text-decoration: none;">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                </div>
            </div>
        </div>

        <!-- Allergies -->
        <div class="card" style="margin-top: 1.25rem;">
            <div class="card-header">
                <i class="fas fa-triangle-exclamation" style="margin-right: 0.5rem; color: #EF4444;"></i> Allergies
            </div>
            <div class="card-body">
                <?php if (empty($student['allergies'])): ?>
                    <p style="font-size: 0.8rem; color: #9CA3AF;">No known allergies.</p>
                <?php else: ?>
                    <?php foreach ($student['allergies'] as $allergy): ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #F3F4F6;">
                            <span style="font-size: 0.85rem; font-weight: 500;"><?= esc($allergy['allergen']) ?></span>
                            <span style="padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; <?php
                                echo match($allergy['severity']) {
                                    'severe'   => 'background: #FEF2F2; color: #DC2626;',
                                    'moderate' => 'background: #FFFBEB; color: #D97706;',
                                    default    => 'background: #F3F4F6; color: #6B7280;',
                                };
                            ?>"><?= ucfirst($allergy['severity']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Emergency Contacts -->
        <div class="card" style="margin-top: 1.25rem;">
            <div class="card-header">
                <i class="fas fa-phone" style="margin-right: 0.5rem; color: #10B981;"></i> Emergency Contacts
            </div>
            <div class="card-body">
                <?php if (empty($student['emergency_contacts'])): ?>
                    <p style="font-size: 0.8rem; color: #9CA3AF;">No contacts on file.</p>
                <?php else: ?>
                    <?php foreach ($student['emergency_contacts'] as $ec): ?>
                        <div style="padding: 0.5rem 0; border-bottom: 1px solid #F3F4F6;">
                            <p style="font-size: 0.85rem; font-weight: 600;"><?= esc($ec['contact_name']) ?></p>
                            <p style="font-size: 0.75rem; color: #6B7280;"><?= esc($ec['relationship']) ?> â€¢ <?= esc($ec['phone']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right: Consultation History -->
    <div>
        <div class="card">
            <div class="card-header">
                <i class="fas fa-clipboard-list" style="margin-right: 0.5rem; color: #3B82F6;"></i> Recent Consultations
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($student['recent_consultations'])): ?>
                    <div style="padding: 2rem; text-align: center; color: #9CA3AF;">
                        <i class="fas fa-file-medical" style="font-size: 1.5rem; display: block; margin-bottom: 0.5rem;"></i>
                        No consultation history.
                    </div>
                <?php else: ?>
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                        <thead>
                            <tr style="background: #F9FAFB; border-bottom: 1px solid #E5E7EB;">
                                <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Date</th>
                                <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Chief Complaint</th>
                                <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Priority</th>
                                <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Status</th>
                                <th style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #374151;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($student['recent_consultations'] as $c): ?>
                                <tr style="border-bottom: 1px solid #F3F4F6;">
                                    <td style="padding: 0.6rem 1rem; white-space: nowrap;"><?= date('M d, Y', strtotime($c['consultation_date'])) ?></td>
                                    <td style="padding: 0.6rem 1rem; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= esc($c['chief_complaint']) ?></td>
                                    <td style="padding: 0.6rem 1rem;">
                                        <?php if ($c['triage_priority']): ?>
                                            <span style="padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; <?php
                                                echo match($c['triage_priority']) {
                                                    'urgent' => 'background: #FEF2F2; color: #DC2626;',
                                                    'high'   => 'background: #FFF7ED; color: #EA580C;',
                                                    'medium' => 'background: #FFFBEB; color: #D97706;',
                                                    default  => 'background: #ECFDF5; color: #059669;',
                                                };
                                            ?>"><?= ucfirst($c['triage_priority']) ?></span>
                                        <?php else: ?>
                                            <span style="color: #9CA3AF;">â€”</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 0.6rem 1rem;">
                                        <span style="padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; <?php
                                            echo match($c['status']) {
                                                'completed'   => 'background: #ECFDF5; color: #059669;',
                                                'follow_up'   => 'background: #EFF6FF; color: #2563EB;',
                                                default       => 'background: #FFFBEB; color: #D97706;',
                                            };
                                        ?>"><?= ucfirst(str_replace('_', ' ', $c['status'])) ?></span>
                                    </td>
                                    <td style="padding: 0.6rem 1rem; text-align: center;">
                                        <a href="/clinic/consultations/<?= $c['id'] ?>" style="color: var(--primary-600); font-size: 0.8rem; text-decoration: none; font-weight: 500;">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
