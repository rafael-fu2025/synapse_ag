<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--
        CSRF token exposed via <meta> for the kiosk's AJAX calls. CI4's CSRF
        filter is global (it intercepts POST /iot/scan and /iot/sync), so the
        kiosk's fetch() calls must echo the token back via the X-CSRF-TOKEN
        header.
    -->
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <title><?= esc($title ?? 'Self-Service Kiosk') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ============================================================================
           SYNAPSE Kiosk — light, friendly, clinical reception-desk aesthetic.
           --------------------------------------------------------------------------
           Design principles:
             - Light surface (white + soft gray). No dark mode. No
               `prefers-color-scheme` media queries — the kiosk is fixed in place
               and the lighting in the lobby is stable.
             - SYNAPSE design tokens for color (maroon primary, neutrals).
             - Touch targets ≥ 64px so even a hurried student or someone in
               a wheelchair can hit them.
             - One intentional motion only: a subtle scan-line. No pulse,
               no glow, no "AI" gradient text.
             - Three visit purposes (Clinic / Counselling / PASIMEO) chosen
               up front — the scan step then adapts to the chosen purpose.
             - Live "Now Serving" panel on the right so the student sees
               context (how many ahead, who's being seen).
           ============================================================================ */

        :root {
            --primary-50:  #FDF2F4;
            --primary-100: #FBE0E5;
            --primary-200: #F4C4D0;
            --primary-400: #C8344E;
            --primary-500: #A41E3A;
            --primary-600: #800000;
            --primary-700: #6B0000;
            --primary-800: #4D0000;

            --gray-50:  #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;

            --success: #10B981;
            --warning: #F59E0B;
            --danger:  #DC2626;
            --info:    #2563EB;

            --radius-sm:  8px;
            --radius:     12px;
            --radius-lg:  20px;
            --shadow-sm:  0 1px 2px rgba(15, 23, 42, 0.06);
            --shadow:     0 4px 16px rgba(15, 23, 42, 0.08);
            --shadow-lg:  0 12px 40px rgba(15, 23, 42, 0.10);
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            height: 100%;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(180deg, var(--gray-50) 0%, #FFFFFF 100%);
            color: var(--gray-900);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-feature-settings: "cv11", "ss01";
        }

        /* ---- Header ---- */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background: white;
            border-bottom: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-700);
        }
        .brand__mark {
            width: 40px;
            height: 40px;
            background: var(--primary-600);
            color: white;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        .brand__sub {
            font-size: 0.65rem;
            color: var(--gray-500);
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-top: -0.15rem;
        }

        .kiosk-status {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.75rem;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            border: 1px solid var(--gray-200);
            background: var(--gray-50);
            color: var(--gray-700);
        }
        .badge--online {
            border-color: #A7F3D0;
            background: #ECFDF5;
            color: #047857;
        }
        .badge--offline {
            border-color: #FED7AA;
            background: #FFF7ED;
            color: #C2410C;
        }
        .badge i { font-size: 0.65rem; }

        .exit-link {
            color: var(--gray-700);
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 500;
            padding: 0.5rem 0.75rem;
            border-radius: var(--radius-sm);
            border: 1px solid var(--gray-200);
            background: white;
            transition: background 150ms, border-color 150ms;
        }
        .exit-link:hover {
            background: var(--gray-50);
            border-color: var(--gray-300);
        }

        /* ---- Page intro strip ---- */
        .intro {
            padding: 1.5rem 2rem 1rem;
            text-align: center;
        }
        .intro h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }
        .intro p {
            color: var(--gray-500);
            font-size: 0.9rem;
        }

        /* ---- Main grid: purpose / scan / queue ---- */
        .layout {
            flex: 1;
            display: grid;
            grid-template-columns: 320px 1fr 320px;
            gap: 1.25rem;
            padding: 1rem 2rem 1.5rem;
            max-width: 1500px;
            margin: 0 auto;
            width: 100%;
        }

        /* ---- Purpose selector (left column) ---- */
        .panel {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            padding: 1.25rem;
        }
        .panel h2 {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--gray-500);
            margin-bottom: 1rem;
        }

        .purpose-tile {
            display: flex;
            align-items: center;
            gap: 0.875rem;
            width: 100%;
            padding: 1rem;
            background: white;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            cursor: pointer;
            margin-bottom: 0.625rem;
            text-align: left;
            transition: border-color 150ms, background 150ms, transform 100ms;
            font-family: inherit;
            color: inherit;
        }
        .purpose-tile:last-child { margin-bottom: 0; }
        .purpose-tile:hover {
            border-color: var(--gray-300);
            background: var(--gray-50);
        }
        .purpose-tile:active { transform: scale(0.99); }
        .purpose-tile.is-selected {
            border-color: var(--primary-600);
            background: var(--primary-50);
        }
        .purpose-tile__icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        .purpose-tile[data-purpose="clinic"] .purpose-tile__icon {
            background: var(--primary-50);
            color: var(--primary-700);
        }
        .purpose-tile[data-purpose="counselling"] .purpose-tile__icon {
            background: #DBEAFE;
            color: #1E40AF;
        }
        .purpose-tile[data-purpose="pasimeo"] .purpose-tile__icon {
            background: #FEF3C7;
            color: #92400E;
        }
        .purpose-tile.is-selected[data-purpose="clinic"] .purpose-tile__icon {
            background: var(--primary-600);
            color: white;
        }
        .purpose-tile__title {
            font-weight: 700;
            font-size: 1rem;
            color: var(--gray-900);
            line-height: 1.2;
        }
        .purpose-tile__desc {
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-top: 0.125rem;
            line-height: 1.3;
        }
        .purpose-tile__check {
            margin-left: auto;
            width: 22px;
            height: 22px;
            border-radius: 999px;
            border: 2px solid var(--gray-300);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.65rem;
            flex-shrink: 0;
        }
        .purpose-tile.is-selected .purpose-tile__check {
            background: var(--primary-600);
            border-color: var(--primary-600);
        }
        .purpose-tile.is-selected .purpose-tile__check::after {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
        }

        /* ---- Scan area (center) ---- */
        .scan-area { display: flex; flex-direction: column; gap: 1.25rem; }
        .scan-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            text-align: center;
        }
        .scan-card h2 {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: var(--gray-900);
        }
        .scan-card .scan-help {
            color: var(--gray-500);
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
        }

        .scanner-container {
            position: relative;
            width: 280px;
            height: 280px;
            margin: 0 auto;
            border-radius: var(--radius-lg);
            overflow: hidden;
            background: var(--gray-900);
        }
        .scan-line {
            position: absolute;
            left: 0;
            right: 0;
            top: 50%;
            height: 2px;
            background: var(--success);
            box-shadow: 0 0 8px var(--success);
            animation: scanMove 2.5s ease-in-out infinite;
            display: none;
            z-index: 5;
        }
        @keyframes scanMove {
            0%, 100% { transform: translateY(-130px); }
            50%      { transform: translateY(130px); }
        }
        .scanner-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            background: linear-gradient(180deg, rgba(0,0,0,0.7), rgba(0,0,0,0.4));
            transition: opacity 200ms;
            z-index: 10;
        }
        .scanner-overlay i { font-size: 2.5rem; margin-bottom: 0.5rem; }
        .scanner-overlay span { font-size: 0.9rem; font-weight: 500; }
        #reader {
            width: 100%;
            height: 100%;
        }
        #reader img { display: block; }

        /* Manual entry row */
        .manual-row {
            display: flex;
            gap: 0.5rem;
            max-width: 420px;
            margin: 1.25rem auto 0;
        }
        .manual-input {
            flex: 1;
            padding: 0.875rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-align: center;
            color: var(--gray-900);
            background: var(--gray-50);
            font-family: inherit;
            transition: border-color 150ms;
        }
        .manual-input:focus {
            outline: none;
            border-color: var(--primary-500);
            background: white;
        }
        .manual-submit {
            padding: 0 1.5rem;
            background: var(--primary-600);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: background 150ms;
        }
        .manual-submit:hover { background: var(--primary-700); }
        .manual-submit:disabled { background: var(--gray-300); cursor: not-allowed; }

        /* ---- Triage / complaint step ---- */
        .triage {
            display: none;
        }
        .triage.is-active { display: block; }
        .triage h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }
        .triage .triage-student {
            font-size: 0.85rem;
            color: var(--gray-500);
            margin-bottom: 1.25rem;
        }
        .triage .triage-student strong { color: var(--gray-900); }

        .complaint-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.625rem;
            margin-bottom: 1.25rem;
        }
        .complaint-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.375rem;
            padding: 1rem 0.5rem;
            background: white;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            font-family: inherit;
            color: var(--gray-700);
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            min-height: 88px;
            transition: border-color 150ms, background 150ms, transform 100ms;
        }
        .complaint-btn:hover {
            border-color: var(--gray-300);
            background: var(--gray-50);
        }
        .complaint-btn:active { transform: scale(0.98); }
        .complaint-btn.is-selected {
            border-color: var(--primary-600);
            background: var(--primary-50);
            color: var(--primary-700);
        }
        .complaint-btn i {
            font-size: 1.25rem;
            color: var(--primary-600);
        }

        .priority-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.625rem;
            margin-bottom: 1.25rem;
        }
        .priority-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            padding: 0.875rem 0.5rem;
            background: white;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            font-family: inherit;
            color: var(--gray-700);
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            min-height: 64px;
            transition: border-color 150ms, background 150ms;
        }
        .priority-btn i { font-size: 1rem; }
        .priority-btn.is-selected[data-priority="low"] {
            border-color: var(--success);
            background: #ECFDF5;
            color: #047857;
        }
        .priority-btn.is-selected[data-priority="medium"] {
            border-color: var(--warning);
            background: #FFFBEB;
            color: #B45309;
        }
        .priority-btn.is-selected[data-priority="high"] {
            border-color: var(--danger);
            background: #FEF2F2;
            color: #B91C1C;
        }

        .triage-actions {
            display: flex;
            gap: 0.625rem;
        }
        .triage-back {
            flex: 1;
            padding: 0.875rem;
            background: white;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            color: var(--gray-700);
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
        }
        .triage-submit {
            flex: 2;
            padding: 0.875rem;
            background: var(--primary-600);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            font-size: 0.95rem;
            transition: background 150ms;
        }
        .triage-submit:hover { background: var(--primary-700); }
        .triage-submit:disabled {
            background: var(--gray-300);
            color: var(--gray-500);
            cursor: not-allowed;
        }

        /* ---- Queue slip ---- */
        .slip {
            display: none;
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            padding: 2rem;
            text-align: center;
        }
        .slip.is-active { display: block; }
        .slip__heading {
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 1rem;
        }
        .slip__number {
            font-size: 7rem;
            font-weight: 800;
            color: var(--primary-700);
            line-height: 1;
            margin-bottom: 0.5rem;
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.02em;
        }
        .slip__number-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--gray-500);
            margin-bottom: 1.5rem;
        }
        .slip__details {
            background: var(--gray-50);
            border-radius: var(--radius);
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            text-align: left;
        }
        .slip__details > div {
            display: flex;
            justify-content: space-between;
            padding: 0.4rem 0;
            border-bottom: 1px solid var(--gray-200);
            font-size: 0.85rem;
        }
        .slip__details > div:last-child { border-bottom: none; }
        .slip__details > div span:first-child {
            color: var(--gray-500);
            font-weight: 500;
        }
        .slip__details > div span:last-child {
            color: var(--gray-900);
            font-weight: 600;
        }
        .slip__hint {
            font-size: 0.8rem;
            color: var(--gray-500);
            margin-bottom: 1rem;
        }
        .slip__actions {
            display: flex;
            gap: 0.625rem;
        }
        .slip__done {
            flex: 2;
            padding: 0.875rem;
            background: var(--primary-600);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
        }
        .slip__done:hover { background: var(--primary-700); }
        .slip__print {
            flex: 1;
            padding: 0.875rem;
            background: white;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            color: var(--gray-700);
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
        }
        .slip__print:hover { background: var(--gray-50); }

        /* ---- Live queue panel (right column) ---- */
        .live-panel {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
        }
        .live-panel__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        .live-panel__head h2 {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--gray-500);
        }
        .live-panel__refresh {
            background: none;
            border: none;
            color: var(--gray-500);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        .live-panel__refresh:hover { background: var(--gray-100); color: var(--primary-700); }

        .live-now {
            background: linear-gradient(135deg, var(--primary-700), var(--primary-500));
            color: white;
            border-radius: var(--radius);
            padding: 1.25rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        .live-now__label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            opacity: 0.85;
            margin-bottom: 0.5rem;
        }
        .live-now__number {
            font-size: 3rem;
            font-weight: 800;
            line-height: 1;
            font-variant-numeric: tabular-nums;
        }
        .live-now__name {
            font-size: 0.85rem;
            margin-top: 0.5rem;
            opacity: 0.9;
        }
        .live-now--empty {
            background: var(--gray-100);
            color: var(--gray-500);
        }

        .live-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.625rem;
            margin-bottom: 1rem;
        }
        .live-stat {
            background: var(--gray-50);
            border-radius: var(--radius);
            padding: 0.75rem;
            text-align: center;
        }
        .live-stat__value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            line-height: 1;
        }
        .live-stat__label {
            font-size: 0.7rem;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 0.25rem;
        }

        .live-list {
            flex: 1;
            overflow-y: auto;
            max-height: 280px;
            border-top: 1px solid var(--gray-200);
            margin-top: 0.25rem;
            padding-top: 0.75rem;
        }
        .live-list__head {
            display: grid;
            grid-template-columns: 32px 1fr auto;
            gap: 0.5rem;
            font-size: 0.7rem;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0 0.5rem 0.5rem;
            font-weight: 600;
        }
        .live-list__row {
            display: grid;
            grid-template-columns: 32px 1fr auto;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            border-radius: var(--radius-sm);
            font-size: 0.8rem;
        }
        .live-list__row + .live-list__row { margin-top: 0.125rem; }
        .live-list__row:hover { background: var(--gray-50); }
        .live-list__pos {
            font-weight: 700;
            color: var(--primary-700);
            text-align: center;
            font-variant-numeric: tabular-nums;
        }
        .live-list__name {
            color: var(--gray-700);
            font-weight: 500;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .live-list__badge {
            font-size: 0.65rem;
            padding: 0.125rem 0.4rem;
            border-radius: 999px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .live-list__badge--low    { background: #D1FAE5; color: #065F46; }
        .live-list__badge--medium { background: #FEF3C7; color: #92400E; }
        .live-list__badge--high   { background: #FEE2E2; color: #B91C1C; }
        .live-list__empty {
            padding: 2rem 0.5rem;
            text-align: center;
            color: var(--gray-500);
            font-size: 0.85rem;
        }
        .live-list__empty i {
            display: block;
            font-size: 1.5rem;
            color: var(--gray-300);
            margin-bottom: 0.5rem;
        }

        /* ---- Status messages (toast) ---- */
        .toast-host {
            position: fixed;
            top: 1rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 100;
            pointer-events: none;
        }
        .toast {
            background: var(--gray-900);
            color: white;
            padding: 0.75rem 1.25rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            font-size: 0.875rem;
            font-weight: 500;
            opacity: 0;
            transform: translateY(-10px);
            transition: opacity 200ms, transform 200ms;
            max-width: 90vw;
        }
        .toast.is-shown {
            opacity: 1;
            transform: translateY(0);
        }
        .toast--error  { background: var(--danger); }
        .toast--info   { background: var(--info); }
        .toast--ok     { background: var(--success); }

        /* ---- ARIA announcer (off-screen) ---- */
        .sr-only {
            position: absolute;
            left: -9999px;
            height: 1px;
            width: 1px;
            overflow: hidden;
        }

        /* ---- Responsive: stack on tablets ---- */
        @media (max-width: 1100px) {
            .layout {
                grid-template-columns: 1fr;
            }
            .live-panel {
                order: 3;
            }
        }

        /* ---- Print: just the slip ---- */
        @media print {
            body * { visibility: hidden; }
            .slip, .slip * { visibility: visible; }
            .slip {
                position: absolute;
                inset: 0;
                border: none;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="brand">
        <div class="brand__mark"><i class="fas fa-hand-holding-medical"></i></div>
        <div>
            <div>SYNAPSE</div>
            <div class="brand__sub">Foundation University · Self-Service Kiosk</div>
        </div>
    </div>
    <div class="kiosk-status">
        <div id="connectionBadge" class="badge badge--online">
            <i class="fas fa-wifi"></i> Online
        </div>
        <a href="/dashboard" class="exit-link" title="Exit Kiosk">
            <i class="fas fa-right-from-bracket"></i> Exit
        </a>
    </div>
</header>

<div class="intro">
    <h1>Welcome — how can we help you today?</h1>
    <p>Choose what you need, then scan your student ID or enter your number.</p>
</div>

<div class="layout">
    <!-- LEFT: Purpose selector -->
    <aside class="panel" aria-labelledby="purposeHeading">
        <h2 id="purposeHeading">1 · Choose a Service</h2>
        <button type="button" class="purpose-tile is-selected" data-purpose="clinic" aria-pressed="true">
            <div class="purpose-tile__icon"><i class="fas fa-stethoscope"></i></div>
            <div>
                <div class="purpose-tile__title">Clinic Visit</div>
                <div class="purpose-tile__desc">Medical concerns, injuries, vitals</div>
            </div>
            <div class="purpose-tile__check" aria-hidden="true"></div>
        </button>
        <button type="button" class="purpose-tile" data-purpose="counselling" aria-pressed="false">
            <div class="purpose-tile__icon"><i class="fas fa-comments"></i></div>
            <div>
                <div class="purpose-tile__title">Counselling</div>
                <div class="purpose-tile__desc">Mental health, emotional support</div>
            </div>
            <div class="purpose-tile__check" aria-hidden="true"></div>
        </button>
        <button type="button" class="purpose-tile" data-purpose="pasimeo" aria-pressed="false">
            <div class="purpose-tile__icon"><i class="fas fa-hand-holding-heart"></i></div>
            <div>
                <div class="purpose-tile__title">PASIMEO</div>
                <div class="purpose-tile__desc">Outreach, volunteer check-in</div>
            </div>
            <div class="purpose-tile__check" aria-hidden="true"></div>
        </button>
    </aside>

    <!-- CENTER: Scan + triage + slip -->
    <section class="scan-area">
        <div class="scan-card">
            <h2>2 · Scan or Enter Your ID</h2>
            <p class="scan-help">Place your student ID QR in front of the camera, or type your student number below.</p>

            <div class="scanner-container">
                <div class="scan-line" id="scanLine"></div>
                <div class="scanner-overlay" id="scannerOverlay" onclick="startScanner()">
                    <i class="fas fa-camera"></i>
                    <span>Tap to activate camera</span>
                </div>
                <div id="reader"></div>
            </div>

            <div class="manual-row">
                <input type="text"
                       id="manualInput"
                       class="manual-input"
                       placeholder="Student number or RFID"
                       autocomplete="off"
                       aria-label="Student number input">
                <button type="button" class="manual-submit" id="manualSubmitBtn">Look Up</button>
            </div>
        </div>

        <!-- Triage (only shown for clinic visits) -->
        <div class="scan-card triage" id="triageCard" aria-labelledby="triageHeading">
            <h2 id="triageHeading">3 · A Few Quick Questions</h2>
            <p class="triage-student">Student: <strong id="triageStudentName">—</strong></p>

            <p style="font-size: 0.85rem; color: var(--gray-700); margin-bottom: 0.625rem; font-weight: 600;">
                What brings you in today?
            </p>
            <div class="complaint-grid" role="group" aria-label="Chief complaint">
                <button type="button" class="complaint-btn" data-complaint="Fever">
                    <i class="fas fa-thermometer-half"></i>Fever
                </button>
                <button type="button" class="complaint-btn" data-complaint="Cough/Colds">
                    <i class="fas fa-head-side-cough"></i>Cough / Colds
                </button>
                <button type="button" class="complaint-btn" data-complaint="Headache">
                    <i class="fas fa-head-side-virus"></i>Headache
                </button>
                <button type="button" class="complaint-btn" data-complaint="Stomachache">
                    <i class="fas fa-stomach"></i>Stomachache
                </button>
                <button type="button" class="complaint-btn" data-complaint="Injury/Wound">
                    <i class="fas fa-band-aid"></i>Injury / Wound
                </button>
                <button type="button" class="complaint-btn" data-complaint="Other">
                    <i class="fas fa-notes-medical"></i>Other
                </button>
            </div>

            <p style="font-size: 0.85rem; color: var(--gray-700); margin-bottom: 0.625rem; font-weight: 600;">
                How urgent is it?
            </p>
            <div class="priority-row" role="group" aria-label="Triage priority">
                <button type="button" class="priority-btn" data-priority="low">
                    <i class="fas fa-circle-check"></i>Routine
                </button>
                <button type="button" class="priority-btn" data-priority="medium">
                    <i class="fas fa-clock"></i>Soon
                </button>
                <button type="button" class="priority-btn" data-priority="high">
                    <i class="fas fa-triangle-exclamation"></i>Urgent
                </button>
            </div>

            <div class="triage-actions">
                <button type="button" class="triage-back" id="triageBackBtn">Back</button>
                <button type="button" class="triage-submit" id="triageSubmitBtn" disabled>Submit Check-In</button>
            </div>
        </div>

        <!-- Queue slip (success state) -->
        <div class="slip" id="slip" aria-labelledby="slipHeading">
            <div class="slip__heading" id="slipHeading">Please proceed to the waiting area</div>
            <div class="slip__number" id="slipNumber">--</div>
            <div class="slip__number-label">Your queue number</div>
            <div class="slip__details">
                <div><span>Name</span><span id="slipName">—</span></div>
                <div><span>Destination</span><span id="slipDestination">—</span></div>
                <div><span>Priority</span><span id="slipPriority">—</span></div>
                <div><span>Reason</span><span id="slipReason">—</span></div>
                <div><span>Time</span><span id="slipTime">—</span></div>
            </div>
            <p class="slip__hint">
                Keep this number — staff will call it when it's your turn. You can also check the waiting-area TV or log in to your student portal.
            </p>
            <div class="slip__actions">
                <button type="button" class="slip__done" id="slipDoneBtn">Done</button>
                <button type="button" class="slip__print" id="slipPrintBtn"><i class="fas fa-print"></i> Print</button>
            </div>
        </div>
    </section>

    <!-- RIGHT: Live queue -->
    <aside class="live-panel" aria-labelledby="liveHeading">
        <div class="live-panel__head">
            <h2 id="liveHeading"><i class="fas fa-tv" style="margin-right: 0.4rem; color: var(--primary-600);"></i>Now Serving</h2>
            <button type="button" class="live-panel__refresh" id="liveRefreshBtn" title="Refresh">
                <i class="fas fa-rotate"></i>
            </button>
        </div>

        <div class="live-now live-now--empty" id="liveNow">
            <div class="live-now__label">Now being seen</div>
            <div class="live-now__number" id="liveNowNumber">—</div>
            <div class="live-now__name" id="liveNowName">No consultation in progress</div>
        </div>

        <div class="live-stats">
            <div class="live-stat">
                <div class="live-stat__value" id="liveWaiting">0</div>
                <div class="live-stat__label">Waiting</div>
            </div>
            <div class="live-stat">
                <div class="live-stat__value" id="liveCalled">0</div>
                <div class="live-stat__label">Called</div>
            </div>
        </div>

        <div class="live-list" id="liveList" aria-label="Live waiting list">
            <div class="live-list__head">
                <span>Pos</span>
                <span>Student</span>
                <span>Priority</span>
            </div>
            <div class="live-list__empty" id="liveListEmpty">
                <i class="fas fa-users"></i>
                No one is waiting right now.
            </div>
        </div>
    </aside>
</div>

<div class="toast-host">
    <div class="toast" id="toast" role="alert"></div>
</div>

<div id="scanAnnouncer" class="sr-only" role="status" aria-live="polite" aria-atomic="true"></div>

<!-- html5-qrcode Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    'use strict';

    /* ---------- State ---------- */
    const state = {
        purpose: 'clinic',                  // currently selected service
        student: null,                      // { id, name, number }
        complaint: '',
        priority: '',
        scanner: null,
    };

    /* ---------- DOM refs ---------- */
    const $ = (id) => document.getElementById(id);
    const els = {
        connectionBadge: $('connectionBadge'),
        purposeTiles:     document.querySelectorAll('.purpose-tile'),
        manualInput:      $('manualInput'),
        manualSubmitBtn:  $('manualSubmitBtn'),
        triageCard:       $('triageCard'),
        triageStudentName:$('triageStudentName'),
        complaintBtns:    document.querySelectorAll('.complaint-btn'),
        priorityBtns:     document.querySelectorAll('.priority-btn'),
        triageBackBtn:    $('triageBackBtn'),
        triageSubmitBtn:  $('triageSubmitBtn'),
        slip:             $('slip'),
        slipNumber:       $('slipNumber'),
        slipName:         $('slipName'),
        slipDestination:  $('slipDestination'),
        slipPriority:     $('slipPriority'),
        slipReason:       $('slipReason'),
        slipTime:         $('slipTime'),
        slipDoneBtn:      $('slipDoneBtn'),
        slipPrintBtn:     $('slipPrintBtn'),
        liveNow:          $('liveNow'),
        liveNowNumber:    $('liveNowNumber'),
        liveNowName:      $('liveNowName'),
        liveWaiting:      $('liveWaiting'),
        liveCalled:       $('liveCalled'),
        liveList:         $('liveList'),
        liveListEmpty:    $('liveListEmpty'),
        liveRefreshBtn:   $('liveRefreshBtn'),
        scanAnnouncer:    $('scanAnnouncer'),
        toast:            $('toast'),
        scannerOverlay:   $('scannerOverlay'),
        scanLine:         $('scanLine'),
    };

    /* ---------- Toast ---------- */
    let toastTimer;
    function toast(message, type, durationMs) {
        type = type || 'info';
        durationMs = durationMs || 3000;
        if (!els.toast) return;
        clearTimeout(toastTimer);
        els.toast.textContent = message;
        els.toast.className = 'toast is-shown toast--' + type;
        toastTimer = setTimeout(() => {
            els.toast.classList.remove('is-shown');
        }, durationMs);
    }

    /* ---------- ARIA announce ---------- */
    function announce(text) {
        if (!els.scanAnnouncer) return;
        els.scanAnnouncer.textContent = '';
        setTimeout(() => { els.scanAnnouncer.textContent = text; }, 50);
    }

    /* ---------- CSRF ---------- */
    function csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    /* ---------- Purpose selector ---------- */
    els.purposeTiles.forEach((tile) => {
        tile.addEventListener('click', () => {
            els.purposeTiles.forEach((t) => {
                t.classList.remove('is-selected');
                t.setAttribute('aria-pressed', 'false');
            });
            tile.classList.add('is-selected');
            tile.setAttribute('aria-pressed', 'true');
            state.purpose = tile.dataset.purpose;
            announce('Selected ' + tile.querySelector('.purpose-tile__title').textContent);
        });
    });

    /* ---------- Manual input ---------- */
    els.manualSubmitBtn.addEventListener('click', () => onSubmit());
    els.manualInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); onSubmit(); }
    });
    /* Allow RFID reader input that arrives in a burst */
    let rfidTimer;
    els.manualInput.addEventListener('input', () => {
        if (els.manualInput.value.length >= 7) {
            clearTimeout(rfidTimer);
            rfidTimer = setTimeout(() => onSubmit(), 200);
        }
    });

    /* ---------- Triage interactions ---------- */
    els.complaintBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            els.complaintBtns.forEach((b) => b.classList.remove('is-selected'));
            btn.classList.add('is-selected');
            state.complaint = btn.dataset.complaint;
            updateTriageSubmit();
        });
    });
    els.priorityBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            els.priorityBtns.forEach((b) => b.classList.remove('is-selected'));
            btn.classList.add('is-selected');
            state.priority = btn.dataset.priority;
            updateTriageSubmit();
        });
    });
    function updateTriageSubmit() {
        const ok = state.purpose !== 'clinic'
                || (state.complaint && state.priority);
        els.triageSubmitBtn.disabled = !ok;
    }
    els.triageBackBtn.addEventListener('click', () => {
        els.triageCard.classList.remove('is-active');
        state.student = null;
        els.manualInput.value = '';
        els.manualInput.focus();
    });

    els.triageSubmitBtn.addEventListener('click', submitFinal);

    /* ---------- Submit (look up student) ---------- */
    async function onSubmit() {
        const id = els.manualInput.value.trim();
        if (!id) {
            toast('Please enter your student number or scan your ID.', 'info');
            els.manualInput.focus();
            return;
        }

        els.manualSubmitBtn.disabled = true;
        try {
            if (state.purpose === 'clinic') {
                /* Show the triage step first. The student name will be
                   looked up when they hit "Submit Check-In" (the existing
                   controller already returns it in the response). */
                els.triageStudentName.textContent = id;
                state.complaint = '';
                state.priority = '';
                els.complaintBtns.forEach((b) => b.classList.remove('is-selected'));
                els.priorityBtns.forEach((b) => b.classList.remove('is-selected'));
                updateTriageSubmit();
                els.triageCard.classList.add('is-active');
                els.triageCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                /* Counselling / PASIMEO: skip triage, go straight to submit. */
                await submitFinal();
            }
        } catch (err) {
            toast(err.message || 'Something went wrong. Please try again.', 'error');
            console.error(err);
        } finally {
            els.manualSubmitBtn.disabled = false;
        }
    }

    /* ---------- Final submit ---------- */
    async function submitFinal(opts) {
        opts = opts || {};
        const id = els.manualInput.value.trim();
        if (!id) return;

        /* Pick a sensible scan_method. The kiosk can't actually tell
           QR from manual text reliably, so default to 'manual' and only
           use 'qr' if the camera callback set state.lastScanMethod. */
        const method = opts.scanMethod || state.lastScanMethod || 'manual';

        /* The /iot/scan endpoint expects:
             - student_identifier  (the QR / RFID / student number)
             - scan_method         ('qr' | 'rfid' | 'manual')
             - chief_complaint     (clinic only)
             - triage_priority     (clinic only)
             - purpose             (clinic | counselling | pasimeo)
        */
        const body = new URLSearchParams();
        body.set('student_identifier', id);
        body.set('scan_method', method);
        if (state.purpose === 'clinic') {
            body.set('chief_complaint', state.complaint || 'Walk-in check-in via Kiosk');
            body.set('triage_priority', state.priority || 'medium');
        } else if (state.purpose === 'counselling') {
            body.set('purpose', 'counselling');
        } else if (state.purpose === 'pasimeo') {
            body.set('purpose', 'pasimeo');
        }

        els.triageSubmitBtn.disabled = true;
        try {
            const res = await fetch('/iot/scan', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf(),
                },
                credentials: 'same-origin',
                body,
            });
            /* If the server returns HTML (e.g. an error page) instead of
               JSON, swallow the parse error so the user sees a clean toast
               rather than "Unexpected token '<'". */
            const text = await res.text();
            let data;
            try { data = JSON.parse(text); } catch (e) {
                throw new Error(res.ok ? 'Unexpected server response.' : ('Server error: HTTP ' + res.status));
            }
            if (!data || !data.success) {
                throw new Error((data && (data.message || data.title)) || 'Check-in failed.');
            }
            /* Update the meta CSRF token from the response if the server
               included one (CI4 sometimes echoes csrf_hash in JSON). */
            showSlip(data);
            refreshLive();
        } catch (err) {
            toast(err.message || 'Network error. Please try again.', 'error');
            console.error(err);
        } finally {
            els.triageSubmitBtn.disabled = false;
        }
    }

    /* ---------- Slip (success) ---------- */
    function showSlip(data) {
        /* Controller response shape:
             { success, title, destination, message, queue_number,
               already_queued, student: { name, number, avatar, allergy_alert } }
           The chosen complaint + priority only live in client state, since
           the controller doesn't echo them back. */
        const prioMap = { low: 'Routine', medium: 'Soon', high: 'Urgent', urgent: 'Urgent' };
        const prioLabel = prioMap[state.priority] || (state.priority || 'Routine');
        els.slipNumber.textContent       = data.queue_number ?? '--';
        els.slipName.textContent         = (data.student && (data.student.name || '')) || '—';
        els.slipDestination.textContent  = data.destination || '—';
        els.slipPriority.textContent     = prioLabel;
        els.slipReason.textContent       = state.complaint || 'Walk-in';
        els.slipTime.textContent         = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        els.slip.classList.add('is-active');
        els.slip.scrollIntoView({ behavior: 'smooth', block: 'start' });
        announce('Checked in successfully. Your queue number is ' + (data.queue_number || ''));
    }

    els.slipDoneBtn.addEventListener('click', resetKiosk);
    els.slipPrintBtn.addEventListener('click', () => window.print());

    function resetKiosk() {
        els.slip.classList.remove('is-active');
        els.triageCard.classList.remove('is-active');
        els.manualInput.value = '';
        state.student = null;
        state.complaint = '';
        state.priority = '';
        els.manualInput.focus();
        refreshLive();
    }

    /* ---------- Camera scanner ---------- */
    window.startScanner = function startScanner() {
        if (!window.Html5Qrcode) {
            toast('Camera library not loaded. Please use manual entry.', 'error');
            return;
        }
        els.scannerOverlay.style.opacity = '0';
        setTimeout(() => {
            els.scannerOverlay.style.display = 'none';
            els.scanLine.style.display = 'block';
        }, 250);

        try {
            state.scanner = new Html5Qrcode('reader');
            state.scanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 250, height: 250 } },
                (decodedText) => {
                    els.manualInput.value = decodedText;
                    /* Camera-scan entry point: same as keypad but tag as 'qr' */
                    state.lastScanMethod = 'qr';
                    if (state.scanner) {
                        try { state.scanner.pause(true); } catch (e) {}
                        setTimeout(() => {
                            try { state.scanner.resume(); } catch (e) {}
                        }, 4000);
                    }
                    onSubmit();
                },
                () => { /* per-frame failures — ignore */ }
            ).catch((err) => {
                console.error(err);
                toast('Camera unavailable. Please use the keypad.', 'error');
                els.scannerOverlay.style.display = 'flex';
                els.scannerOverlay.style.opacity = '1';
                els.scanLine.style.display = 'none';
            });
        } catch (err) {
            console.error(err);
            toast('Camera could not start.', 'error');
        }
    };

    /* ---------- Live queue panel ---------- */
    els.liveRefreshBtn.addEventListener('click', refreshLive);

    async function refreshLive() {
        try {
            const res = await fetch('/consultations/queue/state.json', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) return;
            const data = await res.json();
            renderLive(data);
        } catch (e) {
            /* network blip — try again on next tick */
        }
    }

    function renderLive(data) {
        const now = data.now_serving;
        if (now) {
            els.liveNow.classList.remove('live-now--empty');
            els.liveNowNumber.textContent = now.queue_no || '--';
            els.liveNowName.textContent = now.name || '';
        } else {
            els.liveNow.classList.add('live-now--empty');
            els.liveNowNumber.textContent = '—';
            els.liveNowName.textContent = 'No consultation in progress';
        }
        els.liveWaiting.textContent = (data.queue || []).length;
        els.liveCalled.textContent  = (data.called || []).length;

        const waiting = (data.queue || []).slice(0, 6);
        Array.from(els.liveList.querySelectorAll('.live-list__row')).forEach(n => n.remove());
        if (waiting.length === 0) {
            els.liveListEmpty.style.display = 'block';
        } else {
            els.liveListEmpty.style.display = 'none';
            waiting.forEach((row) => {
                const el = document.createElement('div');
                el.className = 'live-list__row';
                const name = (row.student_first || '') + ' ' + (row.student_last || '');
                el.innerHTML =
                    '<div class="live-list__pos">#' + (row.queue_position || 0) + '</div>' +
                    '<div class="live-list__name">' + escapeHtml(name.trim()) + '</div>' +
                    '<div class="live-list__badge live-list__badge--' + (row.triage_priority || 'medium') + '">' +
                        (row.triage_priority || 'medium') +
                    '</div>';
                els.liveList.appendChild(el);
            });
        }
    }

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = String(s == null ? '' : s);
        return d.innerHTML;
    }

    /* ---------- Boot ---------- */
    els.manualInput.focus();
    refreshLive();
    setInterval(refreshLive, 15000);

})();
</script>

</body>
</html>