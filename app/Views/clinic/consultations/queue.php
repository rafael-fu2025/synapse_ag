<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
    /* Helpers shared across this view */
    $priorityStyle = static function (?string $p): string {
        return match ($p) {
            'urgent' => 'background: var(--danger); color: white;',
            'high'   => 'background: var(--warning); color: white;',
            'medium' => 'background: #FEF3C7; color: #92400E;',
            default  => 'background: #D1FAE5; color: #065F46;',
        };
    };
    $statusStyle = static function (string $s): string {
        return match ($s) {
            'in_session' => 'background: var(--primary-600); color: white;',
            'called'     => 'background: var(--primary-100); color: var(--primary-700);',
            'completed'  => 'background: #D1FAE5; color: #065F46;',
            'follow_up'  => 'background: #DBEAFE; color: #1E40AF;',
            default      => 'background: #FEF3C7; color: #92400E;',
        };
    };
?>

<!-- Page header -->
<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.25rem; gap: 1rem; flex-wrap: wrap;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 600; margin: 0;"><?= esc($heading) ?></h1>
        <p style="margin: 0.25rem 0 0; color: var(--gray-500); font-size: 0.875rem;">Live clinic queue — updated in real time.</p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="/clinic/consultations/queue/display" target="_blank"
           class="syn-btn syn-btn--secondary"
           style="text-decoration: none;">
            <i class="fas fa-tv"></i> Open Lobby Display
        </a>
        <a href="/clinic/consultations" data-spa-link
           style="text-decoration: none; padding: 0.5rem 0.875rem; font-size: 0.8rem; color: var(--gray-700); background: white; border: 1px solid var(--gray-300); border-radius: 0.375rem; font-weight: 500;">
            <i class="fas fa-list"></i> All Consultations
        </a>
    </div>
</div>

<!-- Stats row -->
<?php if (isset($stats) && $stats): ?>
<div class="stats-grid" style="margin-bottom: 1.25rem;">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-clipboard-list"></i></div>
        <div class="stat-info">
            <h3><?= (int) ($stats['total'] ?? 0) ?></h3>
            <p>Total Today</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-info">
            <h3><?= (int) ($stats['waiting'] ?? 0) ?></h3>
            <p>Waiting</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-bullhorn"></i></div>
        <div class="stat-info">
            <h3><?= (int) ($stats['called'] ?? 0) ?></h3>
            <p>Called</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <h3><?= (int) ($stats['completed'] ?? 0) ?></h3>
            <p>Completed</p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Now serving + Call Next -->
<div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; margin-bottom: 1.25rem; align-items: stretch;">

    <!-- Now serving card -->
    <div class="card" style="border-left: 4px solid var(--primary-600);">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-stethoscope" style="margin-right: 0.5rem; color: var(--primary-600);"></i> Now Serving</span>
            <?php if ($nowServing): ?>
                <span style="font-size: 0.7rem; color: var(--gray-500);">Started <?= date('h:i A', strtotime($nowServing['started_at'] ?? 'now')) ?></span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if ($nowServing): ?>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 64px; height: 64px; border-radius: 999px; background: var(--primary-100); color: var(--primary-700); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.5rem;">
                        <?= (int) ($nowServing['queue_position'] ?? 0) ?>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 600; font-size: 1.05rem;"><?= esc(trim(($nowServing['student_first'] ?? '') . ' ' . ($nowServing['student_last'] ?? ''))) ?></div>
                        <div style="font-size: 0.8rem; color: var(--gray-500);"><?= esc($nowServing['student_number'] ?? '') ?> · <?= esc($nowServing['chief_complaint'] ?? '') ?></div>
                    </div>
                    <a href="/clinic/consultations/<?= (int) $nowServing['id'] ?>" data-spa-link
                       style="padding: 0.5rem 0.875rem; background: var(--primary-600); color: white; border-radius: 0.375rem; font-size: 0.8rem; text-decoration: none; font-weight: 500;">
                        <i class="fas fa-notes-medical"></i> Open
                    </a>
                </div>
            <?php else: ?>
                <div style="color: var(--gray-500); font-size: 0.875rem; padding: 0.5rem 0;">
                    <i class="fas fa-moon" style="margin-right: 0.5rem;"></i> No consultation in progress.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Call Next big button -->
    <div style="display: flex; align-items: center;">
        <button type="button" id="callNextBtn"
                style="padding: 1rem 1.5rem; background: var(--primary-600); color: white; border: none; border-radius: 0.5rem; font-size: 1rem; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(128, 0, 0, 0.15); display: flex; align-items: center; gap: 0.625rem; transition: transform 100ms, background 150ms;"
                onmouseover="this.style.background='var(--primary-700)'"
                onmouseout="this.style.background='var(--primary-600)'"
                onmousedown="this.style.transform='scale(0.97)'"
                onmouseup="this.style.transform='scale(1)'">
            <i class="fas fa-bullhorn" style="font-size: 1.1rem;"></i>
            <span>Call Next Patient</span>
        </button>
    </div>
</div>

<!-- Queue table -->
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <span><i class="fas fa-list-ol" style="margin-right: 0.5rem; color: var(--primary-600);"></i> Waiting List</span>
        <span style="font-size: 0.7rem; color: var(--gray-500);"><?= count($queue) ?> patient<?= count($queue) === 1 ? '' : 's' ?></span>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($queue)): ?>
            <div style="padding: 3rem; text-align: center; color: var(--gray-500);">
                <i class="fas fa-users" style="font-size: 2rem; display: block; margin-bottom: 0.75rem; color: var(--gray-400);"></i>
                <p style="font-size: 0.9rem; font-weight: 500;">No patients waiting</p>
                <p style="font-size: 0.8rem; margin-top: 0.25rem;">When a student checks in via the kiosk, they'll appear here.</p>
            </div>
        <?php else: ?>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                <thead>
                    <tr style="background: var(--gray-100); border-bottom: 1px solid var(--gray-200);">
                        <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: var(--gray-700); width: 60px;">#</th>
                        <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: var(--gray-700);">Student</th>
                        <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: var(--gray-700);">Chief Complaint</th>
                        <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: var(--gray-700); width: 110px;">Priority</th>
                        <th style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: var(--gray-700); width: 120px;">Status</th>
                        <th style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: var(--gray-700); width: 240px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($queue as $c): ?>
                        <tr style="border-bottom: 1px solid var(--gray-200);">
                            <td style="padding: 0.6rem 1rem; font-weight: 700; color: var(--primary-700);"><?= (int) ($c['queue_position'] ?? 0) ?></td>
                            <td style="padding: 0.6rem 1rem;">
                                <div style="font-weight: 500;"><?= esc(trim(($c['student_first'] ?? '') . ' ' . ($c['student_last'] ?? ''))) ?></div>
                                <div style="font-size: 0.75rem; color: var(--gray-500);"><?= esc($c['student_number'] ?? '') ?></div>
                            </td>
                            <td style="padding: 0.6rem 1rem; max-width: 240px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--gray-700);">
                                <?= esc($c['chief_complaint'] ?? '—') ?>
                            </td>
                            <td style="padding: 0.6rem 1rem;">
                                <span style="padding: 0.2rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; <?= $priorityStyle($c['triage_priority'] ?? null) ?>">
                                    <?= esc(ucfirst($c['triage_priority'] ?? 'medium')) ?>
                                </span>
                            </td>
                            <td style="padding: 0.6rem 1rem;">
                                <span style="padding: 0.2rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; <?= $statusStyle($c['status']) ?>">
                                    <?= esc(ucfirst(str_replace('_', ' ', $c['status']))) ?>
                                </span>
                            </td>
                            <td style="padding: 0.6rem 1rem; text-align: center;">
                                <div style="display: inline-flex; gap: 0.375rem;">
                                    <?php if ($c['status'] === 'called'): ?>
                                        <form method="post" action="/clinic/consultations/start/<?= (int) $c['id'] ?>" style="display: inline;">
                                            <button type="submit" style="padding: 0.3rem 0.625rem; background: var(--primary-600); color: white; border: none; border-radius: 0.375rem; font-size: 0.7rem; font-weight: 500; cursor: pointer;">
                                                <i class="fas fa-play"></i> Start
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="/clinic/consultations/<?= (int) $c['id'] ?>" data-spa-link
                                       style="padding: 0.3rem 0.625rem; background: var(--primary-50); color: var(--primary-700); border-radius: 0.375rem; font-size: 0.7rem; text-decoration: none; font-weight: 500;">
                                        <i class="fas fa-notes-medical"></i> Open
                                    </a>
                                    <?php if (in_array($c['status'], ['in_progress', 'called'], true)): ?>
                                        <form method="post" action="/clinic/consultations/skip/<?= (int) $c['id'] ?>" style="display: inline;"
                                              onsubmit="return confirm('Skip this patient? They will be marked as no-show.');">
                                            <button type="submit" style="padding: 0.3rem 0.625rem; background: white; color: var(--gray-700); border: 1px solid var(--gray-300); border-radius: 0.375rem; font-size: 0.7rem; font-weight: 500; cursor: pointer;">
                                                <i class="fas fa-forward"></i> Skip
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    'use strict';

    const btn = document.getElementById('callNextBtn');
    if (!btn) return;

    /* Read the CSRF token from the page meta tag so the POST isn't
       rejected by CI4's CSRF filter. */
    function csrf() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    btn.addEventListener('click', async function () {
        if (btn.disabled) return;
        btn.disabled = true;
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Calling...</span>';

        try {
            const res = await fetch('/clinic/consultations/call-next', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf(),
                },
                credentials: 'same-origin',
            });
            const data = await res.json();

            if (!data.success) {
                window.synapse?.toast?.(data.message || 'No patients waiting.', 'info');
            } else {
                const p = data.patient;
                window.synapse?.toast?.(
                    `Called #${p.queue_no} — ${p.name}`,
                    'success',
                    4000
                );
                /* Reload after a short pause so the user sees the toast */
                setTimeout(() => window.location.reload(), 800);
            }
        } catch (err) {
            window.synapse?.toast?.('Network error — please try again.', 'error');
            console.error(err);
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }
    });

    /* Live refresh: every 10s, re-fetch the queue state and update the
       page if anything changed. Cheap because state.json is just a
       few hundred bytes. */
    let lastSignature = '';
    async function pollQueue() {
        try {
            const res = await fetch('/clinic/consultations/queue/state.json', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) return;
            const data = await res.json();
            const sig = JSON.stringify({
                called: data.called.length,
                queue: data.queue.length,
                now: data.now_serving?.id ?? null,
                stats: data.stats,
            });
            if (sig !== lastSignature) {
                lastSignature = sig;
                /* Soft reload — full page refresh so the table stays
                   in sync with the server-side render. */
                window.location.reload();
            }
        } catch (err) {
            /* Network blip — try again next tick */
        }
    }
    setInterval(pollQueue, 10000);
})();
</script>

<?= $this->endSection() ?>