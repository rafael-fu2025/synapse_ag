<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Search Bar -->
<div class="card syn-search-card">
    <div class="card-body">
        <form method="GET" action="/clinic/students"
              data-synapse-search
              class="syn-search-bar"
              autocomplete="off">
            <div class="syn-search-row">
                <div class="syn-search-input-wrap">
                    <i class="fas fa-search syn-search-icon" aria-hidden="true"></i>
                    <label for="studentSearch" class="sr-only">Search students</label>
                    <input type="search" id="studentSearch" name="q" value="<?= esc($search ?? '') ?>"
                           placeholder="Search by name, student number, or email…"
                           autocomplete="off" spellcheck="false"
                           data-synapse-search-trigger>
                </div>
            </div>
            <div class="syn-search-actions">
                <?php if ($search): ?>
                    <a href="/clinic/students" class="syn-search-chip" aria-label="Clear search">
                        <i class="fas fa-xmark"></i> Clear
                    </a>
                <?php endif; ?>
                <a href="/clinic/students/create"
                   class="syn-btn syn-btn--success"
                   data-synapse-form-link
                   data-dialog-title="Register New Student"
                   data-dialog-icon="fas fa-user-plus"
                   data-dialog-width>
                    <i class="fas fa-plus"></i> Register
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Student Table -->
<div class="card">
    <div class="card-body" style="padding: 0;">
        <?php if ($search): ?>
            <div class="syn-search-result-row" style="padding: 0.75rem 1.25rem; background: var(--primary-50); border-bottom: 1px solid var(--primary-100);">
                <span class="syn-search-result-count">
                    <i class="fas fa-search"></i>
                    <strong><?= count($students) ?></strong>
                    <?= count($students) === 1 ? 'student' : 'students' ?>
                    match &ldquo;<?= esc($search) ?>&rdquo;
                </span>
                <a href="/clinic/students" class="syn-search-clear-link">
                    <i class="fas fa-xmark"></i> Clear filters
                </a>
            </div>
        <?php endif; ?>
        <table class="table-mini">
            <thead>
                <tr>
                    <th>Student Number</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th class="syn-cell-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="5" class="empty-state">
                            <i class="fas fa-users" aria-hidden="true" style="font-size: 1.5rem; color: var(--gray-400); display: block; margin-bottom: 0.5rem;"></i>
                            <div style="color: var(--gray-600); font-weight: 500;"><?= $search ? 'No students match your search.' : 'No students registered yet.' ?></div>
                            <?php if ($search): ?>
                                <a href="/clinic/students" class="syn-search-clear-link" style="display: inline-block; margin-top: 0.75rem; font-size: 0.8rem;">
                                    <i class="fas fa-xmark"></i> Clear search
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $s): ?>
                        <tr>
                            <td>
                                <span class="syn-cell-action syn-cell-action--view" style="font-weight: 700;"><?= search_highlight($s['student_number'], $search) ?></span>
                            </td>
                            <td><?= search_highlight(trim($s['first_name'] . ' ' . $s['last_name']), $search) ?></td>
                            <td class="syn-cell-muted"><?= search_highlight($s['email'], $search) ?></td>
                            <td><?= search_highlight($s['course'] ?? '—', $search) ?></td>
                            <td class="syn-cell-center">
                                <a href="/clinic/students/<?= $s['id'] ?>" class="syn-cell-action syn-cell-action--view">View</a>
                                <a href="/clinic/consultations/create/<?= $s['id'] ?>"
                                   class="syn-cell-action syn-cell-action--success"
                                   data-synapse-form-link
                                   data-dialog-title="New Consultation"
                                   data-dialog-icon="fas fa-stethoscope"
                                   data-dialog-width>Consult</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (isset($pager) && $pager): ?>
    <?= pagination_links($pager, '/clinic/students', [], [10, 25, 50]) ?>
<?php endif; ?>
<?= $this->endSection() ?>
