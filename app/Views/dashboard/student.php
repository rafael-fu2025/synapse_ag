<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php if (! empty($activeQueue)): ?>
    <?php
        /* Live queue banner — shown only when the student has an
           active consultation today. Tells them their current number
           and how many people are ahead, so they don't have to keep
           looking at the lobby TV. The card refreshes every 15s. */
        $queueStatus = $activeQueue['status'];
        $queueNumber = (int) ($activeQueue['queue_position'] ?? 0);

        if ($queueStatus === 'in_session') {
            $bannerTitle   = 'It\'s your turn!';
            $bannerSub     = 'A clinician is currently seeing you. Please proceed to the consultation room.';
            $bannerTone    = 'live';
            $bannerIcon    = 'fa-stethoscope';
            $badgeLabel    = 'NOW BEING SEEN';
        } elseif ($queueStatus === 'called') {
            $bannerTitle   = 'Your name has been called!';
            $bannerSub     = 'Please proceed to the consultation room now.';
            $bannerTone    = 'urgent';
            $bannerIcon    = 'fa-bullhorn';
            $badgeLabel    = 'CALLED';
        } else {
            $ahead = (int) ($queueAhead ?? 0);
            $bannerTitle = $ahead === 0
                ? 'You\'re next!'
                : "You're #$queueNumber · $ahead " . ($ahead === 1 ? 'person' : 'people') . ' ahead of you';
            $bannerSub = 'The clinic staff will call your number soon. You can close this page — your spot is saved.';
            $bannerTone = 'waiting';
            $bannerIcon = 'fa-hourglass-half';
            $badgeLabel = "QUEUE #$queueNumber";
        }
    ?>
    <div class="card queue-banner queue-banner--<?= $bannerTone ?>" style="margin-bottom: 1.25rem; border-left: 4px solid var(--primary-600);">
        <div class="card-body" style="display: flex; align-items: center; gap: 1.25rem;">
            <div class="queue-banner__icon" style="width: 56px; height: 56px; border-radius: 50%; background: var(--primary-100); color: var(--primary-700); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
                <i class="fas <?= $bannerIcon ?>"></i>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="display: flex; align-items: center; gap: 0.625rem; margin-bottom: 0.25rem;">
                    <span style="font-size: 0.7rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 999px; background: var(--primary-600); color: white; letter-spacing: 0.05em;"><?= esc($badgeLabel) ?></span>
                </div>
                <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0 0 0.25rem;"><?= esc($bannerTitle) ?></h2>
                <p style="font-size: 0.85rem; color: var(--gray-500); margin: 0;"><?= esc($bannerSub) ?></p>
            </div>
            <?php if ($queueStatus === 'in_progress' && $queueNumber > 0): ?>
                <div style="text-align: center; padding: 0.5rem 1rem; background: white; border-radius: 0.5rem; border: 1px solid var(--gray-200);">
                    <div style="font-size: 2.5rem; font-weight: 800; color: var(--primary-700); line-height: 1; font-variant-numeric: tabular-nums;">#<?= $queueNumber ?></div>
                    <div style="font-size: 0.65rem; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.25rem;">Your number</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-info">
            <h3><?= esc($stats['appointments']) ?></h3>
            <p>Appointments Booked</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-file-medical"></i></div>
        <div class="stat-info">
            <h3><?= esc($stats['consultations']) ?></h3>
            <p>Clinic Consultations</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-hand-holding-heart"></i></div>
        <div class="stat-info">
            <h3><?= esc($stats['hours']) ?> h</h3>
            <p>Volunteer Hours Credited</p>
        </div>
    </div>
</div>

<div class="section-grid">
    <div class="card">
        <div class="card-header">Student Profile</div>
        <div class="card-body">
            <?php if ($student): ?>
                <div class="profile-card id-card">
                    <div class="id-card-decor"></div>
                    <div class="id-card-body">
                        <div class="profile-code">
                            <?php
                                // qr_code / student_number come from the DB and end up
                                // inside an <img src="...?data=...">. A malicious value
                                // (e.g. `" onerror="alert(1)`) would break out of the
                                // attribute. CI's esc() does not url-encode, so we
                                // explicitly urlencode() AND esc() the attribute value
                                // to neutralise both attribute-injection and URL-injection
                                // vectors.
                                $qrPayload = $student['qr_code'] ?: ($student['student_number'] ?? '');
                                $qrAttr    = esc(urlencode($qrPayload), 'attr');
                            ?>
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=<?= $qrAttr ?>" alt="Student QR Code">
                        </div>
                        <div class="profile-details">
                            <p class="profile-label">Student Identity Card</p>
                            <h2 class="profile-name"><?= esc(session()->get('full_name')) ?></h2>
                            <p class="profile-secondary"><?= esc($student['student_number']) ?></p>
                            <div class="profile-meta">
                                <div>
                                    <p class="profile-meta-label">Course</p>
                                    <p class="profile-meta-value"><?= esc($student['course'] ?: 'N/A') ?></p>
                                </div>
                                <div>
                                    <p class="profile-meta-label">Year &amp; Section</p>
                                    <p class="profile-meta-value">Year <?= esc($student['year_level'] ?: 'N/A') ?><?= $student['section'] ? ' - ' . esc($student['section']) : '' ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="id-card-footer">
                        <span>RFID Tag: <?= esc($student['rfid_tag'] ?: 'Unassigned') ?></span>
                        <span><i class="fas fa-signal"></i> Active System Member</span>
                    </div>
                </div>
            <?php else: ?>
                <p class="muted-text">Student profile not available.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card" id="upcoming">
        <div class="card-header">Upcoming Appointments</div>
        <div class="card-body">
            <?php if (empty($upcoming)): ?>
                <div class="placeholder-box">
                    <i class="far fa-calendar-times placeholder-icon"></i>
                    <p class="placeholder-text">No scheduled counselling or clinic appointments found.</p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Counsellor</th>
                            <th scope="col">Date</th>
                            <th scope="col">Time</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming as $appt): ?>
                            <tr>
                                <td style="font-weight: 600;">Dr. <?= esc($appt['first_name']) ?> <?= esc($appt['last_name']) ?></td>
                                <td><?= date('M d, Y', strtotime($appt['appointment_date'])) ?></td>
                                <td><?= date('h:i A', strtotime($appt['start_time'])) ?> - <?= date('h:i A', strtotime($appt['end_time'])) ?></td>
                                <td>
                                    <?php if ($appt['status'] === 'confirmed'): ?>
                                        <span class="badge badge-success"><i class="fas fa-check"></i> Checked-In</span>
                                    <?php else: ?>
                                        <span class="badge badge-info"><i class="fas fa-clock"></i> Scheduled</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card" id="screenings">
    <div class="card-header">Mental Health Screenings</div>
    <div class="card-body">
        <p class="muted-text">Complete regular screening questionnaires to help your counselling team monitor your well-being.</p>
        <?php if (empty($templates)): ?>
            <div class="placeholder-box">
                <p class="placeholder-text">No active assessment templates available.</p>
            </div>
        <?php else: ?>
            <div class="screening-grid">
                <?php foreach ($templates as $t): ?>
                    <div class="screening-card">
                        <div>
                            <h4 class="screening-title"><?= esc($t['title']) ?></h4>
                            <p class="screening-description"><?= esc($t['description']) ?></p>
                        </div>
                        <a href="/counselling/screenings/take/<?= $t['id'] ?>" class="btn btn-primary">
                            <i class="fas fa-pen-fancy"></i> Take
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .section-grid {
        display: grid;
        grid-template-columns: 1.1fr 1fr;
        gap: 1.25rem;
        margin-top: 1.25rem;
    }
    .profile-card {
        position: relative;
        overflow: hidden;
        min-height: 260px;
        border-radius: 1rem;
        background: linear-gradient(135deg, #0f766e, #0f4f59);
        color: white;
        box-shadow: var(--shadow-md);
    }
    .id-card-decor {
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at top right, rgba(255,255,255,0.18), transparent 42%),
                    radial-gradient(circle at bottom left, rgba(255,255,255,0.12), transparent 30%);
        pointer-events: none;
    }
    .id-card-body {
        position: relative;
        display: grid;
        grid-template-columns: 140px 1fr;
        gap: 1rem;
        align-items: center;
        padding: 1.5rem;
        z-index: 1;
    }
    .profile-code img {
        width: 140px;
        height: 140px;
        display: block;
        border-radius: 1rem;
        background: white;
        padding: 0.5rem;
    }
    .profile-details {
        color: white;
    }
    .profile-label {
        margin: 0 0 0.5rem;
        font-size: 0.75rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(255,255,255,0.75);
    }
    .id-card-footer {
        position: relative;
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem;
        margin: 0 1.5rem 1.25rem;
        border-radius: 0.9rem;
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.14);
        color: rgba(255,255,255,0.9);
        font-size: 0.9rem;
        z-index: 1;
    }
    .profile-name {
        margin: 0;
        font-size: 1.5rem;
        color: var(--gray-900);
    }
    .profile-secondary {
        margin: 0.5rem 0 0;
        color: var(--primary-700);
        font-weight: 600;
    }
    .profile-meta {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-top: 1rem;
    }
    .profile-meta-label {
        margin: 0 0 0.25rem;
        font-size: 0.75rem;
        color: var(--gray-400);
    }
    .profile-meta-value {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--gray-700);
    }
    .profile-card-footer {
        position: relative;
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem;
        margin: 0 1.5rem 1.25rem;
        border-radius: 0.9rem;
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.14);
        color: rgba(255,255,255,0.9);
        font-size: 0.9rem;
        z-index: 1;
    }
    .placeholder-box {
        margin-top: 1rem;
        padding: 2rem;
        border-radius: 0.75rem;
        background: var(--gray-50);
        border: 1px dashed var(--gray-200);
        text-align: center;
    }
    .placeholder-icon {
        font-size: 2.25rem;
        color: var(--gray-300);
        margin-bottom: 0.75rem;
    }
    .placeholder-text {
        margin: 0;
        color: var(--gray-500);
        font-size: 0.95rem;
    }
    .screening-grid {
        display: grid;
        gap: 1rem;
        margin-top: 1rem;
    }
    .screening-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        border: 1px solid var(--gray-200);
        border-radius: 0.75rem;
        background: white;
    }
    .screening-card:hover {
        box-shadow: var(--shadow-sm);
        border-color: var(--gray-300);
    }
    .screening-title {
        margin: 0 0 0.35rem;
        font-size: 1rem;
        font-weight: 700;
        color: var(--gray-900);
    }
    .screening-description {
        margin: 0;
        font-size: 0.85rem;
        color: var(--gray-500);
        line-height: 1.5;
    }
    @media (max-width: 900px) {
        .section-grid { grid-template-columns: 1fr; }
    }
</style>
<?= $this->endSection() ?>
