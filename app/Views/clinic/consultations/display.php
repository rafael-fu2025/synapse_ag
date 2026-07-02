<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="15"> <!-- Fallback if JS polling fails -->
    <title><?= esc($title ?? 'Now Serving — SYNAPSE') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-50:  #FDF2F4;
            --primary-100: #FBE4E9;
            --primary-200: #F4C4D0;
            --primary-400: #C8344E;
            --primary-500: #A41E3A;
            --primary-600: #800000;
            --primary-700: #6B0000;
            --primary-800: #4D0000;
            --gray-50:  #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-500: #6B7280;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;
            --success:  #10B981;
            --warning:  #F59E0B;
            --danger:   #EF4444;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background: var(--gray-50);
            color: var(--gray-900);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 2.5rem;
            background: white;
            border-bottom: 2px solid var(--primary-600);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--primary-700);
        }
        .brand .mark {
            width: 36px;
            height: 36px;
            background: var(--primary-600);
            color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        .clock {
            font-variant-numeric: tabular-nums;
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--gray-700);
        }
        .live-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 999px;
            margin-right: 0.4rem;
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%      { opacity: 0.4; }
        }
        .live-pill {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--success);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        main {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            padding: 2rem 2.5rem;
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
        }

        .now-serving {
            background: linear-gradient(135deg, var(--primary-700), var(--primary-500));
            color: white;
            border-radius: 16px;
            padding: 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            box-shadow: 0 10px 30px rgba(128,0,0,0.15);
            min-height: 480px;
        }
        .now-serving .label {
            font-size: 1.1rem;
            font-weight: 600;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            opacity: 0.85;
            margin-bottom: 1.5rem;
        }
        .now-serving .number {
            font-size: 10rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 1rem;
            font-variant-numeric: tabular-nums;
        }
        .now-serving .name {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .now-serving .sub {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        .now-serving .empty {
            font-size: 2.5rem;
            font-weight: 600;
            opacity: 0.85;
        }

        .queue-panel {
            background: white;
            border-radius: 16px;
            border: 1px solid var(--gray-200);
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
            display: flex;
            flex-direction: column;
            min-height: 480px;
        }
        .queue-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .queue-header h2 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--gray-800);
        }
        .queue-header .count {
            font-size: 0.85rem;
            color: var(--gray-500);
            font-weight: 500;
        }
        .queue-list {
            flex: 1;
            overflow-y: auto;
            padding: 0.5rem 0;
        }
        .queue-row {
            display: grid;
            grid-template-columns: 56px 1fr auto;
            align-items: center;
            gap: 1rem;
            padding: 0.875rem 1.5rem;
            border-bottom: 1px solid var(--gray-100);
        }
        .queue-row:last-child { border-bottom: none; }
        .queue-row .pos {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-700);
            font-variant-numeric: tabular-nums;
            text-align: center;
        }
        .queue-row .student {
            font-weight: 500;
            color: var(--gray-800);
        }
        .queue-row .student .num {
            font-size: 0.8rem;
            color: var(--gray-500);
            margin-top: 0.125rem;
        }
        .queue-row .badge {
            padding: 0.25rem 0.625rem;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .badge.prio-urgent { background: var(--danger); color: white; }
        .badge.prio-high   { background: var(--warning); color: white; }
        .badge.prio-medium { background: #FEF3C7; color: #92400E; }
        .badge.prio-low    { background: #D1FAE5; color: #065F46; }
        .badge.called      { background: var(--primary-100); color: var(--primary-700); }
        .queue-empty {
            padding: 4rem 2rem;
            text-align: center;
            color: var(--gray-500);
        }
        .queue-empty i { font-size: 2.5rem; color: var(--gray-300); display: block; margin-bottom: 0.75rem; }

        footer {
            text-align: center;
            padding: 1rem;
            color: var(--gray-500);
            font-size: 0.8rem;
            background: white;
            border-top: 1px solid var(--gray-200);
        }

        @media (max-width: 900px) {
            main { grid-template-columns: 1fr; padding: 1rem; gap: 1rem; }
            .now-serving { padding: 1.5rem; min-height: 320px; }
            .now-serving .number { font-size: 6rem; }
            .now-serving .name { font-size: 1.5rem; }
            .queue-panel { min-height: auto; }
        }
    </style>
</head>
<body>

<header class="topbar">
    <div class="brand">
        <div class="mark"><i class="fas fa-hand-holding-medical"></i></div>
        <span>SYNAPSE — Clinic Queue</span>
    </div>
    <div style="display: flex; align-items: center; gap: 1.25rem;">
        <span class="live-pill"><span class="live-dot"></span>Live</span>
        <span class="clock" id="clock">--:--:--</span>
    </div>
</header>

<main>
    <!-- Now Serving -->
    <section class="now-serving">
        <div class="label">Now Serving</div>
        <?php if (! empty($nowServing)): ?>
            <div class="number"><?= (int) ($nowServing['queue_position'] ?? 0) ?></div>
            <div class="name"><?= esc(trim(($nowServing['student_first'] ?? '') . ' ' . ($nowServing['student_last'] ?? ''))) ?></div>
            <div class="sub">Please proceed to the consultation room</div>
        <?php else: ?>
            <div class="empty"><i class="fas fa-moon" style="display: block; margin-bottom: 1rem; font-size: 3rem; opacity: 0.7;"></i>No consultation in progress</div>
        <?php endif; ?>
    </section>

    <!-- Queue -->
    <section class="queue-panel">
        <div class="queue-header">
            <h2><i class="fas fa-list-ol" style="margin-right: 0.5rem; color: var(--primary-700);"></i> Waiting List</h2>
            <span class="count"><?= count($queue) ?> patient<?= count($queue) === 1 ? '' : 's' ?></span>
        </div>
        <div class="queue-list">
            <?php if (empty($queue)): ?>
                <div class="queue-empty">
                    <i class="fas fa-users"></i>
                    <p style="font-weight: 500;">No patients waiting</p>
                </div>
            <?php else: ?>
                <?php foreach ($queue as $c): ?>
                    <div class="queue-row">
                        <div class="pos"><?= (int) ($c['queue_position'] ?? 0) ?></div>
                        <div class="student">
                            <div><?= esc(trim(($c['student_first'] ?? '') . ' ' . ($c['student_last'] ?? ''))) ?></div>
                            <div class="num"><?= esc($c['student_number'] ?? '') ?></div>
                        </div>
                        <div>
                            <?php if (($c['status'] ?? '') === 'called'): ?>
                                <span class="badge called">Called</span>
                            <?php else: ?>
                                <span class="badge prio-<?= esc($c['triage_priority'] ?? 'medium') ?>"><?= esc(ucfirst($c['triage_priority'] ?? 'medium')) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<footer>
    SYNAPSE Campus Health System &middot; Clinic Waiting Room Display &middot; Auto-refreshes every 10 seconds
</footer>

<script>
(function () {
    'use strict';

    /* Clock */
    const clockEl = document.getElementById('clock');
    function tick() {
        const d = new Date();
        const pad = (n) => String(n).padStart(2, '0');
        clockEl.textContent = pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
    }
    tick();
    setInterval(tick, 1000);

    /* Soft polling — reload every 10s via fetch so we don't lose scroll,
       though this page is fullscreen so it doesn't matter. */
    setInterval(async function () {
        try {
            const res = await fetch('/clinic/consultations/queue/state.json', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) return;
            /* If anything changed, full reload to re-render server side */
            window.location.reload();
        } catch (e) {
            /* keep the meta-refresh as fallback */
        }
    }, 10000);
})();
</script>

</body>
</html>