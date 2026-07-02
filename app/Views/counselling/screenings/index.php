<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem;">
    <?php foreach ($templates as $t): ?>
        <div class="card" style="border-top: 3px solid <?php
            echo match($t['type']) {
                'screening' => '#EF4444',
                'survey'    => '#3B82F6',
                'intake'    => '#10B981',
                default     => '#6B7280',
            };
        ?>;">
            <div class="card-body" style="text-align: center; padding: 1.5rem;">
                <div style="width: 48px; height: 48px; border-radius: 50%; margin: 0 auto 0.75rem; display: flex; align-items: center; justify-content: center; <?php
                    echo match($t['type']) {
                        'screening' => 'background: #FEF2F2; color: #EF4444;',
                        'survey'    => 'background: #EFF6FF; color: #3B82F6;',
                        'intake'    => 'background: #ECFDF5; color: #10B981;',
                        default     => 'background: #F3F4F6; color: #6B7280;',
                    };
                ?>">
                    <i class="fas <?php
                        echo match($t['type']) {
                            'screening' => 'fa-heart-pulse',
                            'survey'    => 'fa-poll',
                            'intake'    => 'fa-clipboard-check',
                            default     => 'fa-file-alt',
                        };
                    ?>" style="font-size: 1.25rem;"></i>
                </div>
                <h3 style="font-size: 0.95rem; font-weight: 700; color: #111827;"><?= esc($t['title']) ?></h3>
                <p style="font-size: 0.75rem; color: #6B7280; margin-top: 0.25rem;"><?= esc($t['description'] ?? '') ?></p>
                <span style="display: inline-block; margin-top: 0.5rem; padding: 0.15rem 0.5rem; background: #F3F4F6; color: #374151; border-radius: 999px; font-size: 0.65rem; font-weight: 500; text-transform: capitalize;"><?= $t['type'] ?></span>
                <a href="/counselling/screenings/take/<?= $t['id'] ?>"
                   data-synapse-form-link
                   data-dialog-title="<?= esc($t['title']) ?>"
                   data-dialog-icon="fas fa-clipboard-list"
                   data-dialog-width
                   style="display: block; margin-top: 1rem; padding: 0.5rem 1rem; background: var(--primary-600); color: white; border-radius: 0.375rem; text-decoration: none; font-size: 0.85rem; font-weight: 600;">
                    <i class="fas fa-play"></i> Administer
                </a>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($templates)): ?>
        <div style="grid-column: 1 / -1; padding: 3rem; text-align: center; color: #9CA3AF;">
            <i class="fas fa-clipboard" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
            No screening forms available. Run the AssessmentSeeder to add PHQ-9 and GAD-7.
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
