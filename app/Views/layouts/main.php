<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SYNAPSE — A Unified Web-Based Campus Health and Counseling Management System">
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <title><?= esc($title ?? 'SYNAPSE') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/synapse-ui.css?v=' . filemtime(FCPATH . 'assets/css/synapse-ui.css')) ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="<?= base_url('assets/js/synapse-ui.js?v=' . filemtime(FCPATH . 'assets/js/synapse-ui.js')) ?>"></script>
    <style>
        /* ============================================================
           SYNAPSE Design System — CSS Variables
           Phase 1: indigo → blue, shadows → hairlines, CJK fallback
           Phase 3: typography stack with display + monospace tokens
           ============================================================ */
        :root {
            /* Phase 3 — Typography tokens */
            --font-sans:
                'Inter', 'PingFang SC', 'PingFang HK', 'Hiragino Sans GB',
                'Microsoft YaHei', 'Noto Sans SC', 'Noto Sans',
                system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --font-display:
                'Outfit', 'Inter', 'PingFang SC', 'PingFang HK',
                'Hiragino Sans GB', 'Microsoft YaHei', 'Noto Sans SC',
                system-ui, sans-serif;
            --font-mono:
                'JetBrains Mono', ui-monospace, SFMono-Regular,
                'SF Mono', Menlo, Consolas, 'Liberation Mono', monospace;

            /* Primary palette — Foundation University Maroon (Pantone 202 C family) */
            --primary-50:  #FDF2F4;
            --primary-100: #FBE0E5;
            --primary-200: #F5BCC6;
            --primary-300: #EA8E9F;
            --primary-400: #D45D78;
            --primary-500: #B8304A;
            --primary-600: #9D2235;
            --primary-700: #7B1F2C;
            --primary-800: #5A1722;
            --primary-900: #3D0F18;

            /* Neutral palette — warm-leaning Tailwind slate/zinc */
            --gray-50:  #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --gray-600: #4B5563;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;

            /* Semantic colors */
            --success: #10B981;
            --warning: #F59E0B;
            --danger:  #EF4444;
            --info:    #9D2235;

            /* Sidebar */
            --sidebar-width: 260px;
            --sidebar-width-collapsed: 72px;
            --sidebar-bg: #FFFFFF;
            --sidebar-text: var(--gray-700);
            --sidebar-hover: var(--gray-100);
            --sidebar-active: var(--primary-600);
            --sidebar-border: var(--gray-200);

            /* Hairline border (Phase 1: replaces heavy box-shadows) */
            --hairline: 1px solid var(--gray-200);

            /* Transitions */
            --transition-fast: 150ms ease;
            --transition-base: 250ms ease;

            /* Shadows — softened; reserved for floating UI (popovers, modals) */
            --shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.04);
            --shadow-md: 0 2px 8px -2px rgba(15, 23, 42, 0.06), 0 1px 2px rgba(15, 23, 42, 0.04);
            --shadow-lg: 0 12px 24px -8px rgba(15, 23, 42, 0.12), 0 4px 8px -4px rgba(15, 23, 42, 0.06);

            /* Phase 1 design tokens */
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;
            --radius-xl: 20px;
            --radius-pill: 9999px;

            --control-height-sm: 32px;
            --control-height-md: 40px;
            --control-height-lg: 48px;
            --syn-field-height:  38px;
        }

        /* ============================================================
           COMPACT UI SCALE (90%)
           ============================================================
           Toggling the `syn-compact-ui` class on <html> (via the user
           Settings dialog, default: ON) scales the entire interface to
           90% of normal:

             • html font-size 16px → 14.4px
             • control heights   32/40/48 → 28.8/36/43.2
             • field height      38       → 34.2
             • border radii      preserved (chrome stays stable)

           Because 100% of layouts in this app use `rem` for fonts,
           padding, margin, and gap, this single switch cascades to
           every container, card, table, form, button, dialog, badge,
           and label without needing per-component overrides.   */
        html.syn-compact-ui {
            font-size: 14.4px;                 /* 16px × 0.9  →  drives ALL rem-based sizes */
            --control-height-sm: 28.8px;       /* 32 × 0.9    →  buttons, inputs, chips   */
            --control-height-md: 36px;         /* 40 × 0.9    →  default buttons, inputs   */
            --control-height-lg: 43.2px;       /* 48 × 0.9    →  large buttons, hero CTAs  */
            --syn-field-height:  34.2px;       /* 38 × 0.9    →  form fields              */
        }

        /* ============================================================
           Reset & Base
           ============================================================ */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-sans);
            font-size: 1rem;                   /* Inherit from <html> so scale propagates   */
            font-feature-settings: 'cv02', 'cv03', 'cv04', 'cv11';
            /* Soft radial page background — pulls the eye toward content */
            background:
                radial-gradient(1200px 600px at 100% 0%,   rgba(59, 130, 246, 0.05) 0%, transparent 60%),
                radial-gradient(900px 500px at 0% 100%,  rgba(59, 130, 246, 0.04) 0%, transparent 60%),
                var(--gray-50);
            color: var(--gray-800);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* ============================================================
           Phase 1 — Shared button system
           Variants: primary | secondary | ghost | destructive
           Sizes:    sm | md (default) | lg
           Shapes:   default rounded-md, add .btn-pill for pill
           ============================================================ */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0 0.875rem;
            height: var(--control-height-md);
            border-radius: var(--radius-md);
            border: 1px solid transparent;
            font-family: inherit;
            font-size: 0.875rem;
            font-weight: 600;
            line-height: 1.2;
            text-decoration: none;
            cursor: pointer;
            transition: background var(--transition-fast),
                        border-color var(--transition-fast),
                        color      var(--transition-fast),
                        box-shadow var(--transition-fast),
                        transform  var(--transition-fast);
            white-space: nowrap;
            user-select: none;
        }
        .btn:focus-visible {
            outline: 2px solid var(--primary-500);
            outline-offset: 2px;
        }
        .btn:disabled,
        .btn[aria-disabled="true"] {
            opacity: 0.55;
            cursor: not-allowed;
            pointer-events: none;
        }
        .btn-sm { height: var(--control-height-sm); padding: 0 0.625rem; font-size: 0.8rem; }
        .btn-lg { height: var(--control-height-lg); padding: 0 1.25rem;   font-size: 0.95rem; }
        .btn-pill { border-radius: var(--radius-pill); }

        .btn-primary {
            background: var(--primary-600);
            color: #fff;
        }
        .btn-primary:hover {
            background: var(--primary-700);
            color: #fff;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        .btn-primary:active { transform: translateY(0); }

        .btn-secondary {
            background: #fff;
            color: var(--gray-700);
            border-color: var(--gray-300);
        }
        .btn-secondary:hover {
            background: var(--gray-50);
            color: var(--gray-900);
            border-color: var(--gray-400);
            text-decoration: none;
        }

        .btn-ghost {
            background: transparent;
            color: var(--gray-600);
        }
        .btn-ghost:hover {
            background: var(--gray-100);
            color: var(--gray-800);
            text-decoration: none;
        }

        .btn-destructive {
            background: var(--danger);
            color: #fff;
        }
        .btn-destructive:hover {
            background: #DC2626;
            color: #fff;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-link {
            background: transparent;
            color: var(--primary-600);
            padding: 0;
            height: auto;
            border: none;
        }
        .btn-link:hover { color: var(--primary-700); text-decoration: underline; }

        /* ============================================================
           Phase 1 — Card surface defaults
           Prefer 1px hairline over box-shadow for resting state.
           Add .card-elevated only when content needs to feel "lifted".
           ============================================================ */
        .card {
            background: #fff;
            border: var(--hairline);
            border-radius: var(--radius-lg);
            box-shadow: none;
            transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
        }
        .card:hover {
            border-color: var(--gray-300);
        }
        .card-elevated {
            box-shadow: var(--shadow-md);
            border-color: transparent;
        }
        .card-elevated:hover { box-shadow: var(--shadow-lg); }

        /* ============================================================
           Layout
           ============================================================ */
        .app-layout {
            display: flex;
            min-height: 100vh;
        }

        /* ============================================================
           Sidebar
           ============================================================ */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            position: fixed;
            top: 0.5rem;
            left: 0.5rem;
            bottom: 0.5rem;
            display: flex;
            flex-direction: column;
            z-index: 100;
            border-top-left-radius: var(--radius-lg);
            border-top-right-radius: var(--radius-lg);
            border-bottom-left-radius: var(--radius-lg);
            border-bottom-right-radius: var(--radius-lg);
            border-right: 1px solid var(--sidebar-border);
            outline: 1px solid var(--sidebar-border);
            outline-offset: -1px;
            transition: width var(--transition-base), transform var(--transition-base);
            overflow: hidden;
        }

        /* ============================================================
           Sidebar — Collapsed (icon-only rail)
           ============================================================ */
        .sidebar.collapsed {
            width: var(--sidebar-width-collapsed);
        }

        .sidebar.collapsed .sidebar-brand-text,
        .sidebar.collapsed .sidebar-wordmark,
        .sidebar.collapsed .sidebar-brand-sub,
        .sidebar.collapsed .nav-section-title {
            opacity: 0;
            pointer-events: none;
            width: 0;
            overflow: hidden;
        }

        .sidebar.collapsed .sidebar-brand {
            justify-content: center;
            padding: 1rem 0.5rem;
            margin: 0.75rem 0.4rem 0.5rem;
        }

        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding: 0.6rem 0;
            gap: 0;
            font-size: 0;
            margin: 0.15rem 0.25rem;
        }

        .sidebar.collapsed .nav-link i {
            font-size: 0.95rem;
            display: inline-block;
        }

        .sidebar.collapsed .sidebar-footer {
            padding: 0.65rem 0.5rem;
            margin: 0.5rem 0.4rem 0.75rem;
        }

        /* Floating tooltip labels when hovering nav links in collapsed mode */
        .sidebar.collapsed .nav-link {
            position: relative;
        }

        .sidebar.collapsed .nav-link::after {
            content: attr(data-label);
            position: absolute;
            left: calc(100% + 12px);
            top: 50%;
            transform: translateY(-50%) translateX(-6px);
            background: var(--gray-900);
            color: #fff;
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.4rem 0.7rem;
            border-radius: 6px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 150ms ease, transform 150ms ease;
            z-index: 110;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .sidebar.collapsed .nav-link:hover::after {
            opacity: 1;
            transform: translateY(-50%) translateX(0);
        }

        .sidebar-brand {
            padding: 1.25rem 1.25rem;
            margin: 0.75rem 0.75rem 0.5rem;
            border-bottom: 1px solid var(--sidebar-border);
            border-radius: var(--radius-md);
            background: var(--sidebar-bg);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-shrink: 0;
        }

        .sidebar-brand-icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* Logo and wordmark both use the dedicated *-outline.svg
           copies under public/assets/img/. Those files have the
           maroon stroke colour baked in (fill="none",
           stroke="var(--primary-700)"), so we don't need a CSS
           mask — a plain <img> tag renders the silhouette directly.
           The brand block is a small visual unit on the side of the
           nav, so we keep the icon compact. */
        .sidebar-brand-icon .sidebar-logo {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: contain;
        }

        .sidebar-brand-text {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
            letter-spacing: -0.025em;
        }

        /* Wordmark shrunk from 22px → 16px tall so the brand block
           doesn't compete with the nav links for vertical space. */
        .sidebar-brand .sidebar-wordmark {
            display: block;
            height: 16px;
            width: auto;
            object-fit: contain;
        }

        .sidebar-brand-sub {
            font-size: 6px;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.01em;
            /* Match the wordmark's natural width above so the two
               read as a single anchored unit. */
            width: 84px;
            overflow: hidden;
            text-overflow: clip;
            white-space: nowrap;
        }

        .sidebar-nav {
            padding: 0.5rem 0.75rem;
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
        }

        /* Subtle scrollbar for the nav area */
        .sidebar-nav::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar-nav::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            background: var(--gray-300);
            border-radius: 3px;
        }

        .sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: var(--gray-400);
        }

        /* Firefox */
        .sidebar-nav {
            scrollbar-width: thin;
            scrollbar-color: var(--gray-300) transparent;
        }

        .nav-section-title {
            padding: 0.75rem 1.25rem 0.4rem;
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--gray-400);
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.55rem 0.85rem;
            margin: 0.15rem 0;
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 400;
            /* Smooth transition matches the card/toast hairline
               aesthetic — color and background fade together so the
               selection doesn't pop. The active state moves to a
               solid primary fill (not a soft tint), so weight and
               color both shift together. */
            transition: background-color var(--transition-fast),
                        color var(--transition-fast),
                        font-weight var(--transition-fast);
            border-radius: var(--radius-md);
            white-space: nowrap;
            overflow: hidden;
        }

        .nav-link i {
            flex-shrink: 0;
            width: 20px;
            text-align: center;
            font-size: 0.9rem;
            opacity: 0.7;
            transition: opacity var(--transition-fast), color var(--transition-fast);
        }

        .nav-link span {
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-link:hover {
            background: var(--sidebar-hover);
            color: var(--gray-900);
        }

        .nav-link:hover i {
            opacity: 1;
        }

        /* Active module indicator. The visual follows the same
           convention used elsewhere in the system for "currently
           selected" elements — a solid fill at full contrast, the
           same way .syn-pagination-link.is-active, the primary
           button, and the focused dialog tab render. Pairing a
           solid active block with a soft hover background keeps
           hover and active at opposite ends of the contrast
           scale so the selection stays unambiguous even at a
           glance. The 2px solid left border is removed — the
           solid background fills that role. */
        .nav-link.active {
            background: var(--primary-600);
            color: #ffffff;
            font-weight: 600;
        }

        .nav-link.active i {
            opacity: 1;
            color: #ffffff;
        }

        /* Active state locks hover — clicking the current page
           should not change its visual identity. */
        .nav-link.active:hover {
            background: var(--primary-600);
            color: #ffffff;
        }
        .nav-link.active:hover i {
            color: #ffffff;
        }

        /* Sidebar footer now hosts a small clock — moves the date/time
           chip from the header so the header can stay focused on actions. */
        .sidebar-footer {
            padding: 0.7rem 0.95rem;
            margin: 0.5rem 0.75rem 0.75rem;
            border-top: 1px solid var(--sidebar-border);
            border-radius: var(--radius-md);
            flex-shrink: 0;
            background: var(--sidebar-bg);
            display: flex;
            align-items: center;
            gap: 0.6rem;
            color: var(--gray-500);
            font-size: 0.78rem;
            line-height: 1.25;
        }

        .sidebar-footer-clock {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            min-width: 0;
        }

        .sidebar-footer-clock i {
            color: var(--primary-600);
            flex-shrink: 0;
            font-size: 0.85rem;
        }

        .sidebar-footer-clock-time {
            font-weight: 600;
            color: var(--gray-800);
            font-variant-numeric: tabular-nums;
        }

        .sidebar-footer-clock-date {
            color: var(--gray-500);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* When the sidebar is collapsed, only the time stays visible
           (the date is too long for the narrow rail). */
        .sidebar.collapsed .sidebar-footer-clock-date {
            display: none;
        }

        /* ============================================================
           Settings dialog + per-user preference hooks
           ============================================================
           The Settings dialog (opened from the user menu) shows a few
           toggles. The two CSS classes below are toggled on <html> by
           JS as soon as the user clicks Save, so the effects are
           instant. The reduced-motion class wins over the @media
           (prefers-reduced-motion) check, so it's also reachable from
           the keyboard. */
        html.syn-reduced-motion,
        html.syn-reduced-motion *,
        html.syn-reduced-motion *::before,
        html.syn-reduced-motion *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
        html.syn-compact-tables .syn-table th,
        html.syn-compact-tables .syn-table td,
        html.syn-compact-tables table.table th,
        html.syn-compact-tables table.table td {
            padding: 0.4rem 0.6rem;
            font-size: 0.82rem;
        }

        /* Toggle switch used in the Settings dialog */
        .syn-switch {
            position: relative;
            display: inline-block;
            width: 38px;
            height: 22px;
            flex-shrink: 0;
            cursor: pointer;
        }
        .syn-switch input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            margin: 0;
            cursor: pointer;
        }
        .syn-switch-slider {
            position: absolute;
            inset: 0;
            background: var(--gray-300);
            border-radius: 999px;
            transition: background var(--transition-fast);
        }
        .syn-switch-slider::before {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 18px;
            height: 18px;
            background: white;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
            transition: transform var(--transition-fast);
        }
        .syn-switch input:checked + .syn-switch-slider {
            background: var(--primary-600);
        }
        .syn-switch input:checked + .syn-switch-slider::before {
            transform: translateX(16px);
        }
        .syn-switch input:focus-visible + .syn-switch-slider {
            box-shadow: 0 0 0 3px rgba(157, 34, 53, 0.18);
        }

        /* Settings dialog body layout */
        .settings-form {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .settings-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.85rem 0;
            border-bottom: 1px solid var(--gray-100);
        }
        .settings-row:last-child {
            border-bottom: none;
        }
        .settings-row-label {
            flex: 1;
            min-width: 0;
        }
        .settings-row-label label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--gray-900);
            cursor: pointer;
        }
        .settings-row-help {
            margin: 0.15rem 0 0;
            font-size: 0.78rem;
            color: var(--gray-500);
        }

        /* Skip-link for keyboard users — visible on focus */
        .syn-skip-link {
            position: absolute;
            top: -100px;
            left: 8px;
            background: var(--primary-700);
            color: white;
            padding: 0.5rem 0.85rem;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
            z-index: 1000;
            transition: top 150ms ease;
        }
        .syn-skip-link:focus,
        .syn-skip-link:focus-visible {
            top: 8px;
            outline: 2px solid white;
        }

        /* ============================================================
           Page-load skeleton
           Shown on initial paint, hidden by JS after DOM ready.
           ============================================================ */
        .syn-page-loading {
            position: fixed;
            inset: 0;
            background: var(--paper, #FAFAF8);
            z-index: 2000;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 2.5rem 1.5rem;
            overflow: hidden;
            transition: opacity 200ms ease;
        }
        .syn-page-loading.is-hidden {
            opacity: 0;
            pointer-events: none;
        }
        .syn-page-loading-inner {
            width: 100%;
            max-width: 1100px;
        }

        /* ============================================================
           SPA NAVIGATION OVERLAY
           ============================================================
           Subtle top-of-page progress bar that activates the moment a
           SPA navigation begins. We use a thin bar (rather than a full
           overlay) so the user keeps seeing the sidebar and last page
           while the new one streams in.
           -------------------------------------------------------- */
        .syn-spa-overlay {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 3px;
            z-index: 2001;
            background: transparent;
            overflow: hidden;
            pointer-events: none;
        }
        .syn-spa-overlay::after {
            content: '';
            position: absolute;
            top: 0; left: -30%;
            width: 30%; height: 100%;
            background: linear-gradient(90deg,
                transparent 0%,
                var(--maroon-500, #B8304A) 50%,
                transparent 100%);
            animation: syn-spa-progress 1.2s ease-in-out infinite;
        }
        .syn-spa-overlay.is-hidden::after { animation-play-state: paused; opacity: 0; }
        @keyframes syn-spa-progress {
            0%   { left: -30%; }
            100% { left: 100%; }
        }
        /* Subtle dim over <main> while swapping so the user knows the
           old content is being replaced. */
        main#mainContent.is-spa-swapping {
            opacity: 0.6;
            transition: opacity 120ms ease;
        }
        main#mainContent { transition: opacity 120ms ease; }

        /* Topbar user profile chip (now styled as a dropdown trigger) */
        .syn-dropdown--user-menu .topbar-user {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.3rem 0.85rem 0.3rem 0.3rem;
            border-radius: 9999px;
            background: var(--gray-100);
            border: 1px solid var(--gray-200);
            transition: all var(--transition-fast);
            cursor: pointer;
            font-family: inherit;
            color: inherit;
        }

        .syn-dropdown--user-menu .topbar-user:hover {
            background: white;
            border-color: var(--gray-300);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
        }

        .syn-dropdown--user-menu.is-open .topbar-user {
            background: white;
            border-color: var(--primary-600, #9D2235);
            box-shadow: 0 0 0 3px rgba(157, 34, 53, 0.12);
        }

        .syn-dropdown--user-menu.is-open .topbar-user .fa-chevron-down {
            transform: rotate(180deg);
            color: var(--primary-600, #9D2235);
        }

        .topbar-user .fa-chevron-down {
            transition: transform 200ms ease, color 150ms ease;
        }

        .topbar-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-500), var(--primary-700));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.7rem;
            flex-shrink: 0;
        }

        .topbar-user-info {
            line-height: 1.2;
        }

        .topbar-user-name {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--gray-900);
            white-space: nowrap;
        }

        .topbar-user-role {
            font-size: 0.65rem;
            color: var(--gray-500);
            text-transform: capitalize;
        }

        /* ============================================================
           Main Content
           ============================================================
           margin-left uses --sidebar-current (set inline by JS based on
           .sidebar-collapsed state). Using a CSS variable instead of
           switching between two fixed-width selectors means the browser
           can interpolate the transition smoothly AND a stray class state
           (e.g. from a browser zoom that interrupts the transition mid-way)
           cannot leave the content offset in a wrong state. */
        .main-content {
            margin-left: calc(0.5rem + var(--sidebar-current, var(--sidebar-width)) + 0.5rem);
            margin-right: 0.5rem;
            flex: 1;
            display: grid;
            grid-template-rows: auto 1fr;
            min-height: 100vh;
            height: 100vh;
            background-color: var(--gray-50);
            overflow: hidden;
            transition: margin-left var(--transition-base);
        }

        /* Fallback: if JS hasn't set --sidebar-current yet, the default
           value above (sidebar-width) applies. The collapsed state is then
           handled by setting --sidebar-current via JS, with the CSS
           selector below as a redundant override. */
        .app-layout.sidebar-collapsed .main-content {
            margin-left: calc(0.5rem + var(--sidebar-width-collapsed) + 0.5rem);
        }

        /* Respect reduced-motion preference: kill the transition so users
           with vestibular sensitivity aren't subjected to layout animations. */
        @media (prefers-reduced-motion: reduce) {
            .sidebar,
            .main-content {
                transition: none !important;
            }
        }

        /* On narrow viewports the sidebar overlays content rather than
           pushing it. Override the margin so content uses full width. */
        @media (max-width: 768px) {
            .main-content,
            .app-layout.sidebar-collapsed .main-content {
                margin-left: 0;
                margin-right: 0;
            }
        }

        /* Top Header */
        .top-header {
            background: white;
            padding: 1rem 1.5rem;
            margin: 0.5rem 0.5rem 0 0;
            border: 1px solid var(--sidebar-border);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        }

        .top-header h1 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
        }

        .top-header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* Page Content — scrolls inside its own grid row, so the header
           stays put without any sticky positioning. */
        .page-content {
            padding: 1.5rem 2rem;
            overflow-y: auto;
            overflow-x: hidden;
            min-height: 0;
            /* Anchor for absolute-positioned dropdowns (flatpickr calendar,
               etc.) so they scroll along with their input rather than
               floating outside the scroll context. */
            position: relative;
        }

        /* ============================================================
           Notifications
           ============================================================ */
        .notification-dropdown {
            position: relative;
        }
        .notification-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            color: var(--gray-500);
            cursor: pointer;
            position: relative;
            padding: 0.5rem;
            transition: color var(--transition-fast);
        }
        .notification-btn:hover {
            color: var(--gray-900);
        }
        .notification-badge {
            position: absolute;
            top: 2px;
            right: 2px;
            background: var(--danger);
            color: white;
            font-size: 0.6rem;
            font-weight: 700;
            padding: 0.15rem 0.35rem;
            border-radius: 99px;
            display: none; /* hidden by default */
        }
        .notification-panel {
            position: absolute;
            top: calc(100% + 4px);
            right: 0;
            width: 340px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1),
                        0 8px 10px -6px rgba(0, 0, 0, 0.05),
                        0 0 0 1px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--gray-200);
            z-index: 100;
            display: none; /* hidden by default */
            flex-direction: column;
            overflow: hidden;
        }
        .notification-panel.open {
            display: flex;
        }
        .notification-panel-header {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--gray-50);
        }
        .notification-panel-header h3 {
            font-size: 0.9rem;
            font-weight: 600;
            margin: 0;
        }
        .notification-panel-header button {
            background: none;
            border: none;
            font-size: 0.75rem;
            color: var(--primary-600);
            cursor: pointer;
        }
        .notification-list {
            max-height: 300px;
            overflow-y: auto;
        }
        .notification-item {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-100);
            font-size: 0.8rem;
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
            width: 100%;
            background: transparent;
            border-left: 0;
            border-right: 0;
            border-top: 0;
            text-align: left;
            font-family: inherit;
            color: inherit;
            cursor: pointer;
            transition: background-color var(--transition-fast);
        }
        .notification-item:last-child {
            border-bottom: none;
        }
        .notification-item.unread {
            background: var(--primary-50);
        }
        .notification-item:hover,
        .notification-item:focus-visible {
            background: var(--gray-100);
            outline: none;
        }
        .notification-item.unread:hover,
        .notification-item.unread:focus-visible {
            background: var(--primary-100);
        }
        .notification-item:focus-visible {
            box-shadow: inset 0 0 0 2px var(--primary-500);
        }
        .notification-icon {
            font-size: 1.25rem;
            color: var(--primary-500);
            flex-shrink: 0;
            line-height: 1;
        }
        .notification-content {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
            min-width: 0;
            flex: 1;
        }
        .notification-content p {
            margin: 0;
            color: var(--gray-800);
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }
        .notification-content .notification-title {
            font-weight: 600;
            color: var(--gray-900);
        }
        .notification-content .notification-message {
            color: var(--gray-700);
        }
        .notification-time {
            font-size: 0.7rem;
            color: var(--gray-500);
            margin-top: 0.15rem;
        }
        .notif-mark-all {
            background: none;
            border: none;
            font-size: 0.75rem;
            color: var(--primary-600);
            cursor: pointer;
            font-family: inherit;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
        }
        .notif-mark-all:hover,
        .notif-mark-all:focus-visible {
            background: var(--primary-50);
            outline: none;
        }
        .notification-empty {
            padding: 2rem 1rem;
            text-align: center;
            color: var(--gray-500);
            font-size: 0.85rem;
        }

        /* ============================================================
           Alert / Flash Messages
           ============================================================ */
        .alert {
            padding: 0.875rem 1.25rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: alertSlideIn 0.3s ease;
        }

        @keyframes alertSlideIn {
            from { opacity: 0; transform: translateY(-0.5rem); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background: #ECFDF5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }

        .alert-danger {
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }

        .alert-warning {
            background: #FFFBEB;
            color: #92400E;
            border: 1px solid #FDE68A;
        }

        .alert-info {
            background: #EFF6FF;
            color: #1E40AF;
            border: 1px solid #BFDBFE;
        }

        /* SYNAPSE alert variant — used by inline form errors and JS-generated
           dialog banners. Same visual treatment as .alert.alert-danger. */
        .syn-alert {
            padding: 0.875rem 1.25rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            animation: alertSlideIn 0.3s ease;
        }
        .syn-alert i:first-child { line-height: 1.4; }
        .syn-alert--danger {
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }
        .syn-alert--success {
            background: #ECFDF5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }
        .syn-field-error {
            font-weight: 500;
        }
        input[aria-invalid="true"], select[aria-invalid="true"], textarea[aria-invalid="true"] {
            border-color: var(--danger);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
        }

        /* ============================================================
           Cards
           ============================================================ */
        /* .card is defined in the design system block above (Phase 1). */
        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--gray-100);
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--gray-800);
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Data Tables
           ------------------------------------------------------------
           The full project-wide table styles (`.table`, `.table-mini`,
           `.kv-table`) live in public/assets/css/synapse-ui.css. Here
           we only override what differs when these tables are rendered
           in the auth-protected layouts (vs the standalone login page):

             - Use `table-layout: fixed` on .table for predictable column
               widths (synapse-ui.css's default is `auto`).
             - Make the header row sticky inside <main>'s scroll
               container so users don't lose column context while
               scrolling long lists.
             - Inherit the rounded-corner clipping already defined in
               synapse-ui.css (no need to redeclare). */
        .table {
            table-layout: fixed;
        }
        .table-auto-layout {
            table-layout: auto;
        }
        .table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--radius-lg);
            border: var(--hairline);
            padding: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            transition: border-color var(--transition-fast), transform var(--transition-fast);
        }

        .stat-card:hover {
            border-color: var(--gray-300);
            transform: translateY(-1px);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .stat-icon.blue    { background: #EFF6FF; color: #3B82F6; }
        .stat-icon.green   { background: #ECFDF5; color: #10B981; }
        .stat-icon.purple  { background: #F5F3FF; color: #8B5CF6; }
        .stat-icon.orange  { background: #FFF7ED; color: #F97316; }
        .stat-icon.red     { background: #FEF2F2; color: #EF4444; }
        .stat-icon.teal    { background: #F0FDFA; color: #14B8A6; }

        .stat-info h3 {
            font-family: var(--font-display);
            font-size: 1.85rem;
            font-weight: 700;
            color: var(--gray-900);
            line-height: 1.15;
            letter-spacing: -0.02em;
            font-variant-numeric: tabular-nums;
        }

        .stat-info p {
            font-size: 0.8rem;
            color: var(--gray-500);
            margin-top: 0.15rem;
        }

        /* ============================================================
           Phase 2 — Stat-card micro-interactions
           Subtle lift + accent ring on hover; click affordance.
           ============================================================ */
        .stat-card { position: relative; cursor: default; }
        .stat-card.is-clickable { cursor: pointer; }
        .stat-card.is-clickable::after {
            content: '\f061';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 0.75rem;
            color: var(--gray-300);
            transition: transform var(--transition-fast), color var(--transition-fast);
        }
        .stat-card.is-clickable:hover::after {
            transform: translateX(3px);
            color: var(--primary-600);
        }
        .stat-card.is-clickable:hover .stat-icon {
            transform: scale(1.05);
        }
        .stat-icon { transition: transform var(--transition-base); }

        /* ============================================================
           Phase 2 — Page Header
           Compact title block used at the top of major pages.
           Add data attribute support via .page-header { eyebrow, title, meta }
           Optional: <div class="page-header-eyebrow"> · <h1 class="page-header-title"> · <div class="page-header-meta">
           ============================================================ */
        .page-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-200);
        }
        .page-header-text { min-width: 0; }
        .page-header-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--primary-600);
            margin-bottom: 0.35rem;
        }
        .page-header-eyebrow::before {
            content: '';
            display: inline-block;
            width: 18px;
            height: 2px;
            background: var(--primary-500);
            border-radius: 1px;
        }
        .page-header-title {
            font-family: var(--font-display);
            font-size: 1.7rem;
            font-weight: 700;
            color: var(--gray-900);
            letter-spacing: -0.025em;
            margin: 0;
            line-height: 1.2;
        }
        .page-header-meta {
            color: var(--gray-500);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        /* ============================================================
           Phase 2 — Empty states
           ============================================================ */
        .empty-state-lg {
            padding: 3rem 1.5rem;
            text-align: center;
            color: var(--gray-500);
            background:
                radial-gradient(400px 200px at 50% 100%, rgba(59,130,246,0.04) 0%, transparent 70%);
            border-radius: var(--radius-lg);
        }
        .empty-state-lg i {
            font-size: 2rem;
            color: var(--gray-300);
            margin-bottom: 0.75rem;
            display: block;
        }
        .empty-state-lg h4 {
            font-size: 1rem;
            color: var(--gray-700);
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        /* ============================================================
           Phase 2 — Pill badge variants (for status pills / counts)
           ============================================================ */
        .pill-soft {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.6rem;
            border-radius: var(--radius-pill);
            font-size: 0.7rem;
            font-weight: 600;
            line-height: 1;
            border: 1px solid transparent;
        }
        .pill-soft.is-blue   { background: var(--primary-50); color: var(--primary-700); border-color: var(--primary-100); }
        .pill-soft.is-green  { background: rgba(16,185,129,.08); color: #047857; border-color: rgba(16,185,129,.2); }
        .pill-soft.is-amber  { background: rgba(245,158,11,.08); color: #B45309; border-color: rgba(245,158,11,.2); }
        .pill-soft.is-red    { background: rgba(239,68,68,.08); color: #B91C1C; border-color: rgba(239,68,68,.2); }
        .pill-soft.is-gray   { background: var(--gray-100); color: var(--gray-700); border-color: var(--gray-200); }
        .pill-soft.is-purple { background: #F5F3FF; color: #6D28D9; border-color: #DDD6FE; }

        /* ============================================================
           Phase 2 — Animated alert entry (subtle slide)
           ============================================================ */
        .alert { animation: alertIn 280ms cubic-bezier(.2,.7,.3,1) both; }
        @keyframes alertIn {
            from { opacity: 0; transform: translateY(-4px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ============================================================
           Phase 3 — Typography utility classes
           Use sparingly: prefer the implicit stack on body / headings.
           ============================================================ */
        .font-display {
            font-family: var(--font-display);
            letter-spacing: -0.015em;
        }
        .font-mono {
            font-family: var(--font-mono);
            font-feature-settings: 'liga' 0, 'calt' 0;
        }
        .text-num {
            font-family: var(--font-display);
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.02em;
        }
        .text-mono-sm {
            font-family: var(--font-mono);
            font-size: 0.78rem;
        }

        /* ============================================================
           Responsive — Mobile
           ============================================================ */
        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.25rem;
            color: var(--gray-700);
            cursor: pointer;
            padding: 0.5rem;
        }

        /* ============================================================
           Sidebar Collapse Toggle (desktop)
           ============================================================ */
        .sidebar-collapse-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: none;
            border: 1px solid var(--gray-200);
            border-radius: 6px;
            font-size: 0.9rem;
            color: var(--gray-600);
            cursor: pointer;
            padding: 0.4rem 0.55rem;
            transition: all var(--transition-fast);
        }

        .sidebar-collapse-toggle:hover {
            background: var(--gray-100);
            color: var(--gray-900);
            border-color: var(--gray-300);
        }

        /* Flip the chevron icon when sidebar is collapsed */
        .app-layout.sidebar-collapsed .sidebar-collapse-toggle i {
            transform: scaleX(-1);
        }

        @media (max-width: 900px) {
            .topbar-user-info {
                display: none;
            }

            .topbar-user {
                padding: 0;
                background: transparent;
                border-color: transparent;
            }

            .topbar-avatar {
                width: 36px;
                height: 36px;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(calc(-100% - 0.5rem));
                width: var(--sidebar-width);
            }

            .sidebar.collapsed {
                width: var(--sidebar-width);
            }

            .sidebar.open {
                transform: translateX(0);
                border-right: 1px solid var(--sidebar-border);
                box-shadow: 4px 0 16px rgba(0, 0, 0, 0.08);
            }

            .app-layout.sidebar-collapsed .main-content {
                margin-left: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-toggle {
                display: block;
            }

            .sidebar-collapse-toggle {
                display: none;
            }

            .page-content {
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <a href="#mainContent" class="syn-skip-link">Skip to main content</a>

    <!-- ============================================================
         Page-load skeleton
         Shown by the browser on initial paint and hidden once JS has
         hydrated the page. This avoids the "flash of empty content"
         when the server is slow to render the first byte.
         ============================================================ -->
    <div id="synPageLoading" class="syn-page-loading" aria-hidden="true">
        <div class="syn-page-loading-inner">
            <div class="syn-skel syn-skel--title"></div>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin: 1.5rem 0;">
                <div class="syn-skel syn-skel--card"></div>
                <div class="syn-skel syn-skel--card"></div>
                <div class="syn-skel syn-skel--card"></div>
                <div class="syn-skel syn-skel--card"></div>
            </div>
            <div class="syn-skel syn-skel--block"></div>
            <div class="syn-skel syn-skel--block" style="width: 70%;"></div>
        </div>
    </div>

    <div class="app-layout">
        <!-- ============================================================
             SIDEBAR
             ============================================================ -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="sidebar-brand-icon">
                    <img src="<?= base_url('assets/img/logo-outline.svg') ?>" alt="SYNAPSE mark" class="sidebar-logo" aria-hidden="true">
                </div>
                <div>
                    <img src="<?= base_url('assets/img/text-outline.svg') ?>" alt="SYNAPSE" class="sidebar-wordmark" aria-hidden="true">
                    <div class="sidebar-brand-sub">Foundation University</div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section-title">Main</div>
                <a href="<?= base_url('dashboard') ?>" class="nav-link <?= uri_string() === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>

                <?php
                $roles = session()->get('roles') ?? [];
                $isAdmin = in_array('admin', $roles);
                $isClinic = in_array('clinic_staff', $roles) || $isAdmin;
                $isCounsellor = in_array('counsellor', $roles) || $isAdmin;
                $isStudent = in_array('student', $roles);
                ?>

                <?php if ($isClinic): ?>
                <div class="nav-section-title">Clinic</div>
                <a href="<?= base_url('clinic/consultations') ?>" class="nav-link <?= str_starts_with(uri_string(), 'clinic/consultation') ? 'active' : '' ?>">
                    <i class="fas fa-clipboard-list"></i> Consultations
                </a>
                <a href="<?= base_url('clinic/students') ?>" class="nav-link <?= str_starts_with(uri_string(), 'clinic/student') ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> Students
                </a>
                <a href="<?= base_url('clinic/referrals') ?>" class="nav-link <?= str_starts_with(uri_string(), 'clinic/referral') ? 'active' : '' ?>">
                    <i class="fas fa-arrow-right-arrow-left"></i> Referrals
                </a>
                <a href="<?= base_url('iot/kiosk') ?>" target="_blank" class="nav-link">
                    <i class="fas fa-desktop"></i> Check-In Kiosk
                </a>

                <div class="nav-section-title">Inventory</div>
                <a href="<?= base_url('inventory') ?>" class="nav-link <?= uri_string() === 'inventory' || str_starts_with(uri_string(), 'inventory/medicines') ? 'active' : '' ?>">
                    <i class="fas fa-pills"></i> Medicine Catalog
                </a>
                <a href="<?= base_url('inventory/low-stock') ?>" class="nav-link <?= uri_string() === 'inventory/low-stock' ? 'active' : '' ?>">
                    <i class="fas fa-triangle-exclamation"></i> Low Stock
                </a>
                <a href="<?= base_url('inventory/expiring') ?>" class="nav-link <?= uri_string() === 'inventory/expiring' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-xmark"></i> Expiring Batches
                </a>
                <?php endif; ?>

                <?php if ($isCounsellor): ?>
                <div class="nav-section-title">Counselling</div>
                <a href="<?= base_url('counselling') ?>" class="nav-link <?= uri_string() === 'counselling' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-check"></i> Appointments
                </a>
                <a href="<?= base_url('counselling/screenings') ?>" class="nav-link <?= str_starts_with(uri_string(), 'counselling/screening') ? 'active' : '' ?>">
                    <i class="fas fa-clipboard-list"></i> Screenings
                </a>
                <a href="<?= base_url('counselling/crisis') ?>" class="nav-link <?= str_starts_with(uri_string(), 'counselling/crisis') ? 'active' : '' ?>">
                    <i class="fas fa-bell"></i> Crisis Alerts
                </a>
                <a href="<?= base_url('counselling/availability') ?>" class="nav-link <?= str_starts_with(uri_string(), 'counselling/availability') ? 'active' : '' ?>">
                    <i class="fas fa-clock"></i> My Availability
                </a>
                <a href="<?= base_url('counselling/referrals') ?>" class="nav-link <?= str_starts_with(uri_string(), 'counselling/referral') ? 'active' : '' ?>">
                    <i class="fas fa-arrow-right-arrow-left"></i> Referrals
                </a>
                <?php endif; ?>

                <?php if ($isStudent): ?>
                <div class="nav-section-title">Student</div>
                <a href="<?= base_url('dashboard/student#upcoming') ?>" class="nav-link">
                    <i class="fas fa-calendar-check"></i> My Appointments
                </a>
                <a href="<?= base_url('dashboard/student#screenings') ?>" class="nav-link">
                    <i class="fas fa-clipboard-list"></i> Screenings
                </a>
                <a href="<?= base_url('profile') ?>" class="nav-link">
                    <i class="fas fa-id-card"></i> My Profile
                </a>
                <?php endif; ?>

                <?php if ($isAdmin): ?>
                <div class="nav-section-title">Administration</div>
                <a href="<?= base_url('admin') ?>" class="nav-link <?= uri_string() === 'admin' ? 'active' : '' ?>">
                    <i class="fas fa-gauge-high"></i> Admin Console
                </a>
                <a href="<?= base_url('admin/users') ?>" class="nav-link <?= str_starts_with(uri_string(), 'admin/users') ? 'active' : '' ?>">
                    <i class="fas fa-users-cog"></i> User Management
                </a>
                <a href="<?= base_url('admin/roles') ?>" class="nav-link <?= str_starts_with(uri_string(), 'admin/roles') ? 'active' : '' ?>">
                    <i class="fas fa-user-shield"></i> Roles & Permissions
                </a>
                <a href="<?= base_url('admin/audit') ?>" class="nav-link <?= str_starts_with(uri_string(), 'admin/audit') ? 'active' : '' ?>">
                    <i class="fas fa-shield-halved"></i> Audit Logs
                </a>
                <a href="<?= base_url('reports') ?>" class="nav-link <?= str_starts_with(uri_string(), 'reports') ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i> Reports & Analytics
                </a>
                <a href="<?= base_url('ui') ?>" class="nav-link <?= uri_string() === 'ui' ? 'active' : '' ?>" target="_blank" data-label="UI Components">
                    <i class="fas fa-puzzle-piece"></i> UI Components
                </a>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer" role="contentinfo" aria-label="Current date and time">
                <div class="sidebar-footer-clock">
                    <i class="fas fa-clock" aria-hidden="true"></i>
                    <span>
                        <span class="sidebar-footer-clock-date" id="sidebarFooterDate"><?= esc(date('M d, Y')) ?></span>
                        <span class="sidebar-footer-clock-time" id="sidebarFooterTime"><?= esc(date('h:i A')) ?></span>
                    </span>
                </div>
            </div>
        </aside>

        <!-- ============================================================
             MAIN CONTENT
             ============================================================ -->
        <div class="main-content">
            <header class="top-header">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button class="mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')" aria-label="Open sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <button class="sidebar-collapse-toggle" id="sidebarCollapseToggle" onclick="toggleSidebarCollapse()" aria-label="Toggle sidebar" title="Collapse sidebar">
                        <i class="fas fa-angles-left"></i>
                    </button>
                    <h1><?= esc($heading ?? 'Dashboard') ?></h1>
                </div>
                <div class="top-header-actions">
                    <?php if (session()->get('logged_in') || session()->get('isLoggedIn')): ?>
                    <div class="syn-dropdown syn-dropdown--user-menu" id="userMenu">
                        <button type="button" class="topbar-user" onclick="document.getElementById('userMenu').classList.toggle('is-open')" title="<?= esc(session()->get('full_name') ?? 'User') ?>">
                            <div class="topbar-avatar">
                                <?= strtoupper(substr(session()->get('first_name') ?? 'U', 0, 1)) ?><?= strtoupper(substr(session()->get('last_name') ?? '', 0, 1)) ?>
                            </div>
                            <div class="topbar-user-info">
                                <div class="topbar-user-name"><?= esc(session()->get('full_name') ?? 'User') ?></div>
                                <div class="topbar-user-role"><?= esc(str_replace('_', ' ', session()->get('primary_role') ?? 'guest')) ?></div>
                            </div>
                            <i class="fas fa-chevron-down" style="font-size: 0.65rem; color: var(--gray-400); margin-left: 0.25rem;"></i>
                        </button>
                        <div class="syn-dropdown-menu" style="width: 220px; right: 0; left: auto;">
                            <div style="padding: 0.65rem 0.85rem; border-bottom: 1px solid var(--gray-100);">
                                <div style="font-size: 0.85rem; font-weight: 600; color: var(--gray-900);"><?= esc(session()->get('full_name') ?? 'User') ?></div>
                                <div style="font-size: 0.72rem; color: var(--gray-500); margin-top: 0.15rem;"><?= esc(session()->get('email') ?? '') ?></div>
                            </div>
                            <ul class="syn-dropdown-options">
                                <li class="syn-dropdown-option" id="userMenuProfile" role="button" tabindex="0" aria-label="Open my profile dialog" data-synapse-form-link="<?= base_url('profile') ?>" data-synapse-form-link-mode="dialog" data-synapse-form-link-title="My Profile">
                                    <i class="fas fa-user syn-dropdown-option-icon"></i>
                                    <span>My Profile</span>
                                </li>
                                <li class="syn-dropdown-option" id="userMenuSettings" role="button" tabindex="0" aria-label="Open settings dialog">
                                    <i class="fas fa-gear syn-dropdown-option-icon"></i>
                                    <span>Settings</span>
                                </li>
                                <?php if (in_array('admin', session()->get('roles') ?? [])): ?>
                                <li class="syn-dropdown-option" role="button" tabindex="0" aria-label="Open audit log" onclick="window.location='<?= base_url('admin/audit') ?>'" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.click();}">
                                    <i class="fas fa-shield-halved syn-dropdown-option-icon"></i>
                                    <span>Audit Log</span>
                                </li>
                                <?php endif; ?>
                            </ul>
                            <div class="syn-dropdown-divider"></div>
                            <ul class="syn-dropdown-options">
                                <li class="syn-dropdown-option" id="userMenuLogout" role="button" tabindex="0" style="color: var(--danger);">
                                    <i class="fas fa-right-from-bracket syn-dropdown-option-icon" style="color: var(--danger);"></i>
                                    <span>Sign out</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (session()->get('logged_in') || session()->get('isLoggedIn')): ?>
                    <div class="notification-dropdown">
                        <button class="notification-btn"
                                id="notifBtn"
                                type="button"
                                aria-label="Notifications"
                                aria-haspopup="true"
                                aria-expanded="false"
                                aria-controls="notifPanel"
                                onclick="toggleNotifications()">
                            <i class="fas fa-bell" aria-hidden="true"></i>
                            <span class="notification-badge" id="notifBadge" hidden>0</span>
                        </button>
                        <div class="notification-panel"
                             id="notifPanel"
                             role="region"
                             aria-label="Notifications panel">
                            <div class="notification-panel-header">
                                <h3 id="notifPanelTitle">Notifications</h3>
                                <button type="button"
                                        class="notif-mark-all"
                                        onclick="markAllRead()">Mark all as read</button>
                            </div>
                            <div class="notification-list"
                                 id="notifList"
                                 role="list"
                                 aria-labelledby="notifPanelTitle"
                                 aria-live="polite">
                                <!-- Dynamic notifications load here -->
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </header>

            <main class="page-content" id="mainContent" tabindex="-1">
                <!-- Flash Messages -->
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= esc(session()->getFlashdata('success')) ?>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= esc(session()->getFlashdata('error')) ?>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('warning')): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= esc(session()->getFlashdata('warning')) ?>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('errors')): ?>
                    <div class="alert alert-danger">
                        <div>
                            <i class="fas fa-exclamation-circle"></i>
                            <ul style="margin: 0.25rem 0 0 1rem; padding: 0;">
                                <?php foreach (session()->getFlashdata('errors') as $err): ?>
                                    <li><?= esc($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Page Content -->
                <?= $this->renderSection('content') ?>
            </main>
        </div>
    </div>

    <?php
        // Auto-fire toasts for any flash messages set by the controller.
        // Renders nothing if no flash data exists; otherwise emits a single
        // <script> block that calls synapse.toast() on page load.
        $flashToasts = [];
        if (session()->getFlashdata('success')) {
            $flashToasts[] = ['type' => 'success', 'title' => 'Success', 'message' => session()->getFlashdata('success')];
        }
        if (session()->getFlashdata('error')) {
            $flashToasts[] = ['type' => 'error', 'title' => 'Error', 'message' => session()->getFlashdata('error')];
        }
        if (session()->getFlashdata('warning')) {
            $flashToasts[] = ['type' => 'warning', 'title' => 'Warning', 'message' => session()->getFlashdata('warning')];
        }
        if (session()->getFlashdata('info')) {
            $flashToasts[] = ['type' => 'info', 'title' => 'Notice', 'message' => session()->getFlashdata('info')];
        }
        if (session()->getFlashdata('errors')) {
            $errList = session()->getFlashdata('errors');
            $first   = is_array($errList) ? implode(' · ', array_slice($errList, 0, 3)) : (string) $errList;
            $extra   = is_array($errList) && count($errList) > 3 ? ' (+' . (count($errList) - 3) . ' more)' : '';
            $flashToasts[] = ['type' => 'error', 'title' => 'Validation failed', 'message' => $first . $extra, 'duration' => 6000];
        }
        if (! empty($flashToasts)):
    ?>
    <script>
    (function () {
        var toasts = <?= json_encode($flashToasts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        if (window.synapse && typeof synapse.toast === 'function') {
            toasts.forEach(function (t) { synapse.toast(t); });
        }
    })();
    </script>
    <?php endif; ?>

    <script>
        // Auto-dismiss alerts after 5 seconds
        // Auto-dismiss non-error alerts after 5 seconds. DANGER alerts are
        // never auto-dismissed — the user must read and acknowledge them.
        // They get a close button so they can be removed once understood.
        //
        // The dismiss logic is wrapped in a helper so SPA navigation can
        // re-run it for newly inserted alerts inside <main>.
        function bindAlertDismiss(alert) {
            if (!alert || alert._synAlertBound) return;
            alert._synAlertBound = true;
            const isError = alert.classList.contains('alert-danger')
                         || alert.classList.contains('alert-error');
            if (isError) {
                if (!alert.querySelector('.alert-close')) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'alert-close';
                    btn.setAttribute('aria-label', 'Dismiss');
                    btn.innerHTML = '<i class="fas fa-xmark" aria-hidden="true"></i>';
                    btn.style.cssText = 'margin-left:auto;background:transparent;border:0;cursor:pointer;color:inherit;font-size:0.9rem;padding:0 0.25rem;line-height:1;';
                    btn.addEventListener('click', () => {
                        alert.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        alert.style.opacity = '0';
                        alert.style.transform = 'translateY(-0.5rem)';
                        setTimeout(() => alert.remove(), 300);
                    });
                    alert.style.display = 'flex';
                    alert.style.alignItems = 'flex-start';
                    alert.style.gap = '0.5rem';
                    alert.appendChild(btn);
                }
                return;
            }
            setTimeout(() => {
                if (!alert.parentNode) return;
                alert.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-0.5rem)';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        }
        /* Initial pass — alerts present on first page load. */
        document.querySelectorAll('.alert').forEach(bindAlertDismiss);
        /* Expose so SPA navigation can re-bind alerts in swapped-in content. */
        window.__synapseDismissAlerts = function (root) {
            root = root || document;
            (root.querySelectorAll ? root.querySelectorAll('.alert') : []).forEach(bindAlertDismiss);
        };

        // Close topbar user menu on outside click
        document.addEventListener('click', function (e) {
            var userMenu = document.getElementById('userMenu');
            if (userMenu && userMenu.classList.contains('is-open') && !userMenu.contains(e.target)) {
                userMenu.classList.remove('is-open');
            }
        });

        // ============================================================
        // Logout confirmation dialog
        // Clicking "Sign out" in the user menu (or pressing Enter/Space
        // when it's focused) opens a confirmation dialog. This prevents
        // accidental logouts and gives the user a chance to cancel.
        // ============================================================
        (function () {
            var logoutBtn = document.getElementById('userMenuLogout');
            if (!logoutBtn) return;

            function requestLogout() {
                // Close the user menu first
                var userMenu = document.getElementById('userMenu');
                if (userMenu) userMenu.classList.remove('is-open');

                if (!window.synapse || typeof synapse.dialog !== 'object' || typeof synapse.dialog.confirm !== 'function') {
                    // Fallback: native confirm if dialog API is missing
                    if (window.confirm('Are you sure you want to sign out?')) {
                        window.location.href = '<?= base_url('logout') ?>';
                    }
                    return;
                }

                synapse.dialog.confirm({
                    title: 'Sign out of SYNAPSE?',
                    subtitle: 'You will need to enter your credentials again to access the system.',
                    body: '<p style="margin: 0;">Any unsaved changes on the current page will be lost.</p>',
                    danger: true,
                    confirmText: 'Sign out',
                    cancelText: 'Stay signed in',
                    onConfirm: function () {
                        window.location.href = '<?= base_url('logout') ?>';
                    }
                });
            }

            logoutBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                requestLogout();
            });
            logoutBtn.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    requestLogout();
                }
            });
        })();

        // ============================================================
        // Settings dialog
        // The user-menu "Settings" link opens an inline dialog with
        // appearance + notification toggles. Preferences persist to
        // localStorage so they survive reloads. Real backend persistence
        // is out of scope; this is a quick frontend surface for the
        // most common per-user controls.
        // ============================================================
        (function () {
            var settingsBtn = document.getElementById('userMenuSettings');
            if (!settingsBtn) return;

            var STORAGE_KEY = 'synapse.user.preferences';
            function loadPrefs() {
                try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}'); }
                catch (e) { return {}; }
            }
            function savePrefs(p) {
                try { localStorage.setItem(STORAGE_KEY, JSON.stringify(p)); }
                catch (e) { /* ignore */ }
            }
            var defaults = {
                reducedMotion:    false,
                compactTables:    false,
                compactUi:        true,           /* Default ON — 90% scale per system directive */
                notificationsOn:  true,
                soundAlerts:      false
            };
            var current = Object.assign({}, defaults, loadPrefs());

            function openSettingsDialog() {
                var userMenu = document.getElementById('userMenu');
                if (userMenu) userMenu.classList.remove('is-open');

                var body = ''
                    + '<form id="settingsForm" class="settings-form" onsubmit="return false;">'
                    +   '<div class="settings-row">'
                    +     '<div class="settings-row-label">'
                    +       '<label for="settingReducedMotion">Reduce motion</label>'
                    +       '<p class="settings-row-help">Minimise animations across the app. Useful for motion sensitivity.</p>'
                    +     '</div>'
                    +     '<label class="syn-switch">'
                    +       '<input type="checkbox" id="settingReducedMotion" ' + (current.reducedMotion ? 'checked' : '') + '>'
                    +       '<span class="syn-switch-slider"></span>'
                    +     '</label>'
                    +   '</div>'
                    +   '<div class="settings-row">'
                    +     '<div class="settings-row-label">'
                    +       '<label for="settingCompactTables">Compact tables</label>'
                    +       '<p class="settings-row-help">Reduce row padding in data tables to fit more rows per screen.</p>'
                    +     '</div>'
                    +     '<label class="syn-switch">'
                    +       '<input type="checkbox" id="settingCompactTables" ' + (current.compactTables ? 'checked' : '') + '>'
                    +       '<span class="syn-switch-slider"></span>'
                    +     '</label>'
                    +   '</div>'
                    +   '<div class="settings-row">'
                    +     '<div class="settings-row-label">'
                    +       '<label for="settingCompactUi">Compact UI (90%)</label>'
                    +       '<p class="settings-row-help">Scale the entire interface to 90% of normal size — fonts, cards, buttons, and gaps. Off = 100% standard size.</p>'
                    +     '</div>'
                    +     '<label class="syn-switch">'
                    +       '<input type="checkbox" id="settingCompactUi" ' + (current.compactUi ? 'checked' : '') + '>'
                    +       '<span class="syn-switch-slider"></span>'
                    +     '</label>'
                    +   '</div>'
                    +   '<div class="settings-row">'
                    +     '<div class="settings-row-label">'
                    +       '<label for="settingNotifications">Notifications</label>'
                    +       '<p class="settings-row-help">Receive in-app alerts when new records are assigned to you.</p>'
                    +     '</div>'
                    +     '<label class="syn-switch">'
                    +       '<input type="checkbox" id="settingNotifications" ' + (current.notificationsOn ? 'checked' : '') + '>'
                    +       '<span class="syn-switch-slider"></span>'
                    +     '</label>'
                    +   '</div>'
                    +   '<div class="settings-row">'
                    +     '<div class="settings-row-label">'
                    +       '<label for="settingSound">Sound alerts</label>'
                    +       '<p class="settings-row-help">Play a short chime when a new notification arrives.</p>'
                    +     '</div>'
                    +     '<label class="syn-switch">'
                    +       '<input type="checkbox" id="settingSound" ' + (current.soundAlerts ? 'checked' : '') + '>'
                    +       '<span class="syn-switch-slider"></span>'
                    +     '</label>'
                    +   '</div>'
                    + '</form>';

                if (!window.synapse || typeof synapse.dialog.open !== 'function') return;

                var dialog = synapse.dialog.open({
                    title: 'Settings',
                    subtitle: 'Personal preferences for this device.',
                    body: body,
                    confirmText: 'Save',
                    cancelText: 'Cancel',
                    onConfirm: function () {
                        current.reducedMotion   = document.getElementById('settingReducedMotion').checked;
                        current.compactTables   = document.getElementById('settingCompactTables').checked;
                        current.compactUi       = document.getElementById('settingCompactUi').checked;
                        current.notificationsOn = document.getElementById('settingNotifications').checked;
                        current.soundAlerts     = document.getElementById('settingSound').checked;
                        savePrefs(current);
                        applyPrefs();
                        if (window.synapse.toast) {
                            synapse.toast({ type: 'success', title: 'Settings saved', message: 'Your preferences have been updated.', duration: 3000 });
                        }
                    }
                });

                applyPrefs();
            }

            // Push the saved preferences onto the live document so the user
            // sees the effect immediately, even before clicking Save.
            function applyPrefs() {
                document.documentElement.classList.toggle('syn-reduced-motion', !!current.reducedMotion);
                document.documentElement.classList.toggle('syn-compact-tables', !!current.compactTables);
                document.documentElement.classList.toggle('syn-compact-ui',     !!current.compactUi);
            }
            applyPrefs();

            settingsBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                openSettingsDialog();
            });
            settingsBtn.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openSettingsDialog();
                }
            });
        })();

        // ============================================================
        // SPA NAVIGATION
        // ============================================================
        // Intercepts same-origin in-app links (sidebar, breadcrumb, and
        // any <a> inside <main>), fetches the target page with AJAX,
        // extracts the <main id="mainContent"> fragment from the
        // response, and swaps it into the current document. This:
        //
        //   • Eliminates full-page reloads between modules
        //   • Keeps the sidebar, header, and CSRF token in memory
        //   • Scrolls the active sidebar item into view automatically
        //   • Updates browser history so Back/Forward work normally
        //
        // Recovery: any fetch / parse / network error falls back to a
        // normal navigation (window.location = href) so the user is
        // never stranded. Anything that needs a real reload (file
        // downloads, target=_blank, external links, POSTs, JS-disabled
        // crawlers) opt out via:
        //
        //     <a href="..." data-synapse-spa-exclude>  — bypass
        //     <a href="..." data-synapse-form-link>    — already handled
        //     <form method="post">                     — full reload
        // ============================================================
        (function () {
            'use strict';

            if (window.__synapseSpaNav) return;     // Init guard.
            window.__synapseSpaNav = true;
            /* Expose spaNavigate so other components (e.g. the per-page
               selector) can request a SPA navigation without going
               through the click-intercept path. */
            window.spaNavigate = function (url, replace, force) {
                return spaNavigate(url, replace, force);
            };

            var mainEl        = document.getElementById('mainContent');
            var sidebar       = document.getElementById('sidebar');
            var pageLoading   = document.getElementById('synPageLoading');
            if (!mainEl || !sidebar) return;        // Layout not ready.

            var sidebarNav    = sidebar.querySelector('.sidebar-nav');
            var currentRequest = null;              // AbortController for in-flight fetches.
            var navInFlight   = false;

            // ---- Decide whether a link should be SPA-ified ----
            function isSpaLink(a) {
                if (!a || a.tagName !== 'A') return false;
                var href = a.getAttribute('href');
                if (!href) return false;

                /* Skip — we never intercept these */
                if (a.target === '_blank')           return false;
                if (a.target === '_download')        return false;
                if (a.hasAttribute('download'))      return false;
                if (a.hasAttribute('data-synapse-spa-exclude')) return false;

                /* External / hash / mailto / tel / non-app schemes */
                var u;
                try { u = new URL(href, window.location.href); }
                catch (e) { return false; }
                if (u.origin !== window.location.origin) return false;
                if (u.protocol !== 'http:' && u.protocol !== 'https:') return false;

                /* Skip data-synapse-form-link — handled by synapse-ui.js */
                if (a.hasAttribute('data-synapse-form-link')) return false;
                if (a.hasAttribute('data-synapse-form-dialog')) return false;
                if (a.hasAttribute('data-synapse-confirm')) return false;
                if (a.hasAttribute('data-synapse-confirm-target')) return false;

                /* Only same-document links (no protocol trickery). */
                return u.pathname && u.pathname.charAt(0) === '/';
            }

            // ---- Active-link state management ----
            // Server emits `class="nav-link active"` based on uri_string().
            // After SPA navigation we have to recompute it client-side
            // using the same prefix-match rule.
            function setActiveSidebarLink() {
                var path = window.location.pathname;
                var links = sidebarNav.querySelectorAll('a.nav-link[href]');
                var bestMatch = null;
                var bestLen = -1;
                links.forEach(function (a) {
                    a.classList.remove('active');
                    a.removeAttribute('aria-current');
                    var href = a.getAttribute('href') || '';
                    if (!href || href.charAt(0) !== '/') return;
                    /* Strip query / hash, normalize trailing slash. */
                    var route = href.split('#')[0].split('?')[0].replace(/\/+$/, '') || '/';
                    var current = path.replace(/\/+$/, '') || '/';
                    /* Exact match wins; longest prefix wins otherwise. */
                    if (route === current) {
                        if (route.length > bestLen) {
                            bestMatch = a;
                            bestLen = route.length;
                        }
                    } else if (current === '/' || route === '/') {
                        // ignore — covered by exact match below
                    } else if (current.indexOf(route + '/') === 0) {
                        if (route.length > bestLen) {
                            bestMatch = a;
                            bestLen = route.length;
                        }
                    }
                });
                if (!bestMatch) {
                    /* Fallback — first link. */
                    bestMatch = links[0];
                }
                if (bestMatch) {
                    bestMatch.classList.add('active');
                    bestMatch.setAttribute('aria-current', 'page');
                }
            }

            // ---- Scroll active sidebar item into view ----
            // This is the user's primary complaint: if the active link
            // is below the sidebar's visible area, they have to scroll
            // manually. We bring it into view the moment it becomes
            // active, smooth-scroll if reduced-motion is off, and
            // center vertically so the user always sees the active
            // item in context (with both neighbours above and below).
            function scrollActiveIntoView() {
                var active = sidebar.querySelector('a.nav-link.active');
                if (!active || !sidebarNav) return;

                /* Skip if the active item is already fully visible
                   AND there is room for context above + below it. We
                   avoid unnecessary re-scrolls (which would feel
                   jittery during fast keyboard nav). */
                var navRect = sidebarNav.getBoundingClientRect();
                var actRect = active.getBoundingClientRect();
                var fullyVisible = actRect.top >= navRect.top && actRect.bottom <= navRect.bottom;
                var headroom    = actRect.top - navRect.top;
                var footroom    = navRect.bottom - actRect.bottom;
                if (fullyVisible && headroom > 60 && footroom > 60) return;

                /* Compute target scrollTop so the active item is
                   vertically centered in the nav viewport. */
                var navScrollHeight = sidebarNav.scrollHeight;
                var navClientHeight = sidebarNav.clientHeight;
                var activeOffsetTop = active.offsetTop;             /* distance from nav top  */
                var activeHeight    = active.offsetHeight;         /* element height in px  */
                var desired         = activeOffsetTop - (navClientHeight - activeHeight) / 2;
                desired = Math.max(0, Math.min(desired, navScrollHeight - navClientHeight));

                if (Math.abs(sidebarNav.scrollTop - desired) < 2) return;

                var reduce = document.documentElement.classList.contains('syn-reduced-motion')
                          || window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                if (reduce) {
                    sidebarNav.scrollTop = desired;
                } else {
                    /* We can either use the smooth option on scrollTo,
                       or smooth-animate via requestAnimationFrame.
                       scrollTo is well-supported and respects
                       prefers-reduced-motion if set later. We prefer
                       it for simplicity. */
                    try {
                        sidebarNav.scrollTo({ top: desired, behavior: 'smooth' });
                    } catch (e) {
                        sidebarNav.scrollTop = desired;
                    }
                }
            }

            // ---- Title / meta updates from new document ----
            function syncHead(newDoc) {
                /* The <title> element picks up the new page's title. */
                var newTitle = newDoc.querySelector('title');
                if (newTitle && newTitle.textContent) {
                    document.title = newTitle.textContent;
                }
                /* Refresh the CSRF token if the new page rotated it. */
                var newCsrf = newDoc.querySelector('meta[name="csrf-token"]');
                if (newCsrf && newCsrf.getAttribute('content')) {
                    var ourCsrf = document.querySelector('meta[name="csrf-token"]');
                    if (ourCsrf) ourCsrf.setAttribute('content', newCsrf.getAttribute('content'));
                }
            }

            // ---- Re-run client-side wiring that runs on initial page load ----
            // The existing main.php IIFEs only run once (DOMContentLoaded).
            // After a content swap we have to re-wire:
            //
            //   1. Flash alert auto-dismiss (the inline IIFE that calls
            //      querySelectorAll('.alert').forEach(...))
            //   2. synapse-ui.js auto-binding (data-synapse-*  attributes)
            //   3. flatpickr date pickers
            //   4. Top-bar user menu / notifications panel
            //   5. <a data-synapse-form-link> dialog triggers
            //
            // Items 2, 3, 5 live in synapse-ui.js and use _synXxxFlag-style
            // sentinels on DOM nodes — so they re-init on demand if we
            // dispatch the right event.
            function runPostSwapHooks() {
                /* Auto-dismiss alerts (only fires for new .alert nodes) */
                try {
                    var dismiss = window.__synapseDismissAlerts;
                    if (typeof dismiss === 'function') dismiss(mainEl);
                } catch (e) { /* ignore */ }

                /* synapse-ui.js auto-binding (dropdowns, forms, dialogs).
                   The library exposes synapse.rebind() for this; we
                   dispatch a custom event as a fallback in case the
                   helper is renamed. */
                try {
                    if (window.synapse && typeof window.synapse.rebind === 'function') {
                        window.synapse.rebind(mainEl);
                    } else {
                        document.dispatchEvent(new CustomEvent('synapse:content-replaced', {
                            detail: { root: mainEl }
                        }));
                    }
                } catch (e) { /* ignore */ }

                /* Flatpickr date pickers — synapse-ui.js auto-inits
                   [data-date] / [data-datetime] inputs once. We have
                   to nudge it for the new inputs. */
                try {
                    if (window.flatpickr && typeof window.flatpickr === 'function') {
                        var dateIn = mainEl.querySelectorAll('[data-date], [data-datetime], input.flatpickr-input');
                        dateIn.forEach(function (el) {
                            /* The synapse-ui.js init uses a sentinel
                               on the element, so calling flatpickr()
                               again on the same node is a no-op. For
                               NEW nodes it works directly. */
                            if (!el._flatpickr) {
                                /* Look up the synapse-ui flatpickr init
                                   config by triggering the same custom
                                   event the library listens to. */
                                el.dispatchEvent(new Event('focus', { bubbles: true }));
                            }
                        });
                    }
                } catch (e) { /* ignore */ }

                /* Re-fire any module-specific custom events. */
                try { mainEl.dispatchEvent(new CustomEvent('synapse:page-loaded', { bubbles: true })); } catch (e) {}
            }

            // ---- Loading overlay control ----
            function showLoading() {
                /* If the initial-page-load skeleton still exists, reuse it.
                   Otherwise create a tiny overlay so the user sees feedback
                   during the fetch. */
                if (pageLoading && pageLoading.parentNode) {
                    pageLoading.classList.remove('is-hidden');
                } else {
                    var overlay = document.createElement('div');
                    overlay.className = 'syn-spa-overlay';
                    overlay.id = 'synSpaLoading';
                    overlay.setAttribute('aria-hidden', 'true');
                    overlay.innerHTML = '<div class="syn-spa-spinner"></div>';
                    document.body.appendChild(overlay);
                }
            }
            function hideLoading() {
                if (pageLoading && pageLoading.parentNode) {
                    /* The skeleton fade is handled by its own IIFE;
                     just remove .is-hidden so the next nav shows it. */
                }
                var overlay = document.getElementById('synSpaLoading');
                if (overlay && overlay.parentNode) {
                    overlay.classList.add('is-hidden');
                    setTimeout(function () {
                        if (overlay && overlay.parentNode) overlay.parentNode.removeChild(overlay);
                    }, 200);
                }
            }

            // ---- Core: navigate to a URL via SPA ----
            function spaNavigate(href, replace, force) {
                if (navInFlight && !force) return false;

                var u;
                try { u = new URL(href, window.location.href); } catch (e) { return false; }
                if (u.origin !== window.location.origin) return false;

                /* Same-path hash scroll: only when we're NOT forcing a
                   popstate refresh. Click-triggered same-path nav just
                   scrolls to the hash; popstate-triggered must always
                   re-fetch in case something changed. */
                if (!force && u.pathname === window.location.pathname && u.search === window.location.search) {
                    if (u.hash) {
                        var tgt = document.getElementById(u.hash.slice(1));
                        if (tgt) tgt.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                    return true;
                }
                if (!force && u.href === window.location.href) return false;

                navInFlight = true;
                showLoading();
                mainEl.classList.add('is-spa-swapping');

                /* Abort any in-flight fetch. */
                if (currentRequest) try { currentRequest.abort(); } catch (e) {}
                currentRequest = (typeof AbortController === 'function') ? new AbortController() : null;

                var fetchOptions = {
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                    redirect: 'follow'
                };
                if (currentRequest) fetchOptions.signal = currentRequest.signal;

                fetch(u.pathname + u.search, fetchOptions)
                    .then(function (res) {
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        /* Defensive: if the server sent a redirect (login
                           timeout / SSO bounce), fall back to a full
                           navigation so the redirect is honoured. */
                        if (res.redirected) {
                            window.location.href = res.url;
                            return null;
                        }
                        return res.text();
                    })
                    .then(function (text) {
                        if (text === null) return;          /* Redirected. */
                        var doc = new DOMParser().parseFromString(text, 'text/html');

                        /* The fetch returned the FULL layout (every
                           page extends layouts/main). We only want the
                           inner <main id="mainContent">. */
                        var newMain = doc.getElementById('mainContent');
                        if (!newMain) {
                            /* Server isn't ours anymore (e.g. login page
                               or 404). Fall back to a full nav. */
                            window.location.href = u.pathname + u.search + u.hash;
                            return;
                        }

                        /* Swap the content. */
                        mainEl.innerHTML = newMain.innerHTML;

                        /* Update head: title + CSRF. */
                        syncHead(doc);

                        /* Update browser history FIRST so
                           setActiveSidebarLink() reads the new path. */
                        var newUrl = u.pathname + u.search + u.hash;
                        if (replace) {
                            window.history.replaceState({ spa: true, url: newUrl }, '', newUrl);
                        } else {
                            window.history.pushState({ spa: true, url: newUrl }, '', newUrl);
                        }

                        /* Update sidebar active state. */
                        setActiveSidebarLink();

                        /* Reset scroll. */
                        try {
                            var pageContent = mainEl.closest('.page-content') || mainEl;
                            pageContent.scrollTop = 0;
                        } catch (e) {}
                        window.scrollTo(0, 0);

                        /* Bring the active sidebar item into view. */
                        requestAnimationFrame(function () {
                            scrollActiveIntoView();
                        });

                        /* Re-init client-side wiring on the new content. */
                        runPostSwapHooks();
                    })
                    .catch(function (err) {
                        if (err && err.name === 'AbortError') return;
                        /* Anything else: fall back to full nav. */
                        window.location.href = u.pathname + u.search + u.hash;
                    })
                    .then(function () {
                        navInFlight = false;
                        hideLoading();
                        mainEl.classList.remove('is-spa-swapping');
                    });
                return true;
            }

            // ---- Click interception (event delegation) ----
            // Listen at document level so it survives content swaps.
            document.addEventListener('click', function (e) {
                if (e.defaultPrevented) return;
                if (e.button !== 0) return;             /* Skip right/middle-click. */
                if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;  /* New tab/window. */

                /* Walk up to the nearest <a>. */
                var a = e.target.closest && e.target.closest('a');
                if (!a || !isSpaLink(a)) return;

                var href = a.getAttribute('href');
                /* Hash-only links (e.g. href="#upcoming") are handled
                   natively — no SPA involvement. */
                if (!href || href.indexOf('#') === 0) return;

                e.preventDefault();
                /* Resolve the URL to an absolute form first so the
                   href can be either '/path' or 'http://host/path'. */
                var resolved;
                try { resolved = new URL(href, window.location.href).href; }
                catch (err) { return; }
                spaNavigate(resolved, false, false);
            }, true /* capture */);

            // ---- Back / forward ----
            window.addEventListener('popstate', function (e) {
                var state = e.state;
                /* Normalize state — pushState without state stores null.
                   If the user came from outside the SPA (e.g. opened a
                   direct link in a new tab) we don't have SPA history,
                   so fall back to a real navigation. */
                var url = (state && state.spa && state.url)
                       ? state.url
                       : window.location.href;

                /* popstate already advanced the browser URL — we
                   must fetch+swap to update content + sidebar active
                   state. The history pushState is already done by the
                   browser, so don't try to pushState again. Use
                   replace-mode so we don't double-up. */
                spaNavigate(url, true /* replace */, true /* force */);
            });

            /* Initialize history.state so popstate has a baseline. Without
               this the first popstate back returns null and we skip. */
            try {
                if (!window.history.state) {
                    window.history.replaceState(
                        { spa: true, url: window.location.href },
                        '',
                        window.location.href
                    );
                } else if (!window.history.state.spa) {
                    window.history.replaceState(
                        Object.assign({}, window.history.state, { spa: true, url: window.location.href }),
                        '',
                        window.location.href
                    );
                }
            } catch (e) { /* ignore */ }

            /* On initial load, the server already marked the active link
               based on uri_string() — but only if the active item is
               below the sidebar fold we still need to scroll it in.
               Run once on DOMContentLoaded (and one frame later, so the
               layout has settled at 90% scale). */
            function initialActiveScroll() {
                setActiveSidebarLink();
                requestAnimationFrame(function () { requestAnimationFrame(scrollActiveIntoView); });
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initialActiveScroll, { once: true });
            } else {
                initialActiveScroll();
            }

            /* On responsive resize (sidebar collapse toggle, viewport
               resize, browser zoom) the active item can fall out of
               view — re-snap it. */
            var resizeTimer;
            window.addEventListener('resize', function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(scrollActiveIntoView, 150);
            });
            /* Sidebar collapse toggle changes the visible nav height —
               catch that too. */
            var sidebarObserver = new MutationObserver(function () {
                requestAnimationFrame(scrollActiveIntoView);
            });
            if (sidebarNav) sidebarObserver.observe(sidebarNav, { attributes: true, childList: true, subtree: true });
        })();

        // ============================================================
        // Sidebar collapse — auto-label tooltips + toggle + persistence
        // ============================================================
        (function () {
            const sidebar    = document.getElementById('sidebar');
            const appLayout  = document.querySelector('.app-layout');
            const STORAGE_KEY = 'synapse.sidebar.collapsed';

            // Auto-populate data-label on every nav link from its visible text.
            // This lets the collapsed tooltip show the link name without
            // having to add data-label attributes to every <a> manually.
            sidebar.querySelectorAll('.nav-link').forEach(link => {
                if (!link.hasAttribute('data-label')) {
                    // Use the text node after the icon as the label
                    const textNode = [...link.childNodes].find(n =>
                        n.nodeType === Node.TEXT_NODE && n.textContent.trim().length > 0
                    );
                    if (textNode) {
                        link.setAttribute('data-label', textNode.textContent.trim());
                    }
                }
            });

            // Apply persisted state on load. We set BOTH the .sidebar-collapsed
            // class (for icon/label visibility) AND an inline CSS variable
            // (for the .main-content offset). The CSS variable ensures the
            // content offset can't get stuck in an intermediate state if a
            // browser zoom interrupts the transition mid-animation.
            function syncSidebarState() {
                const isCollapsed = sidebar.classList.contains('collapsed');
                const width = isCollapsed
                    ? 'var(--sidebar-width-collapsed)'
                    : 'var(--sidebar-width)';
                appLayout.style.setProperty('--sidebar-current', width);
                // Also clear any stale inline width on the sidebar itself.
                sidebar.style.width = '';
            }

            try {
                if (localStorage.getItem(STORAGE_KEY) === '1') {
                    sidebar.classList.add('collapsed');
                    appLayout.classList.add('sidebar-collapsed');
                }
            } catch (e) {
                // localStorage unavailable — silently ignore
            }
            syncSidebarState();

            // Browser zoom changes the visual viewport but doesn't fire
            // 'resize' reliably across all engines. Listen for visualViewport
            // resize (Chromium/Safari) and fall back to window.resize for
            // Firefox. We re-sync the sidebar state so any interrupted
            // transition finishes cleanly.
            if (window.visualViewport) {
                window.visualViewport.addEventListener('resize', syncSidebarState);
            }
            window.addEventListener('resize', syncSidebarState);

            // Expose toggle for the inline onclick handler
            window.toggleSidebarCollapse = function () {
                const isCollapsed = sidebar.classList.toggle('collapsed');
                appLayout.classList.toggle('sidebar-collapsed', isCollapsed);
                syncSidebarState();
                try {
                    localStorage.setItem(STORAGE_KEY, isCollapsed ? '1' : '0');
                } catch (e) {
                    // localStorage unavailable — silently ignore
                }
            };

            // ============================================================
            // Sidebar clock — updates the date/time chip every minute.
            // The chip replaces the old logout button + header clock.
            // ============================================================
            (function () {
                const dateEl = document.getElementById('sidebarFooterDate');
                const timeEl = document.getElementById('sidebarFooterTime');
                if (!dateEl || !timeEl) return;

                // Match the PHP `date('M d, Y')` and `date('h:i A')` formats
                // so the first render matches what JS will produce on tick.
                const MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                function pad(n) { return n < 10 ? '0' + n : '' + n; }
                function tick() {
                    const now = new Date();
                    const m  = MONTHS[now.getMonth()];
                    const dd = pad(now.getDate());
                    const yy = now.getFullYear();
                    let h = now.getHours();
                    const mm = pad(now.getMinutes());
                    const ampm = h >= 12 ? 'PM' : 'AM';
                    h = h % 12; if (h === 0) h = 12;
                    dateEl.textContent = m + ' ' + dd + ', ' + yy;
                    timeEl.textContent = (h < 10 ? h : h) + ':' + mm + ' ' + ampm;
                }
                tick();
                // Align the next tick to the start of the next minute so the
                // seconds-rollover doesn't drift the display, then tick once
                // per minute.
                const msToNextMinute = (60 - new Date().getSeconds()) * 1000;
                setTimeout(function () {
                    tick();
                    setInterval(tick, 60 * 1000);
                }, msToNextMinute);
            })();
        })();
    </script>

    <!-- SYNAPSE Global Notification Scripts -->
    <script>
        (function () {
            'use strict';

            // ============================================================
            // Helpers — CSRF, escaping, time formatting
            // ============================================================

            // Read the randomized CSRF token from the meta tag set in <head>.
            // CI4 rotates the token on every successful POST, so callers must
            // always read the LATEST value (which the controller echoes back
            // as `csrf_hash` in the JSON response).
            function getCsrfToken() {
                var meta = document.querySelector('meta[name="csrf-token"]');
                return meta ? meta.getAttribute('content') : '';
            }

            // Persist a freshly-rotated token so subsequent POSTs send it.
            function setCsrfToken(t) {
                if (!t) return;
                var meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) meta.setAttribute('content', t);
            }

            // Minimal HTML escape — used for any user/DB-derived string
            // we render into innerHTML. All DB-backed fields (title,
            // message, link) flow through here so a notification containing
            // a script tag can't execute.
            function escapeHtml(s) {
                if (s === null || s === undefined) return '';
                return String(s)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            // Safe JSON-encode for use inside JS string literals (e.g. for
            // data-* attribute values). Avoids the classic single-quote
            // breakout when n.link contains an apostrophe.
            function jsString(v) {
                return JSON.stringify(v === null || v === undefined ? '' : String(v));
            }

            function formatTimeAgo(date) {
                var seconds = Math.floor((new Date() - date) / 1000);
                if (isNaN(seconds) || seconds < 0) return '';
                if (seconds >= 31536000) return Math.floor(seconds / 31536000) + ' years ago';
                if (seconds >= 2592000)  return Math.floor(seconds / 2592000) + ' months ago';
                if (seconds >= 86400)    return Math.floor(seconds / 86400) + ' days ago';
                if (seconds >= 3600)     return Math.floor(seconds / 3600) + ' hours ago';
                if (seconds >= 60)       return Math.floor(seconds / 60) + ' minutes ago';
                return Math.floor(seconds) + ' seconds ago';
            }

            // ============================================================
            // Notification rendering
            // ============================================================

            // Choose a FontAwesome icon + colour for each notification type.
            // Keeping this in one place makes future types easy to add.
            function iconForType(type) {
                switch (type) {
                    case 'referral':              return { icon: 'fa-arrow-right-arrow-left', color: 'var(--info)' };
                    case 'crisis':
                    case 'welfare_alert':         return { icon: 'fa-triangle-exclamation',   color: 'var(--danger)' };
                    case 'inventory':
                    case 'low_stock':             return { icon: 'fa-box',                     color: 'var(--warning)' };
                    case 'volunteer_assignment':  return { icon: 'fa-hand-holding-heart',      color: 'var(--primary-500)' };
                    case 'appointment_booked':    return { icon: 'fa-calendar-check',          color: 'var(--info)' };
                    default:                      return { icon: 'fa-bell',                    color: 'var(--primary-500)' };
                }
            }

            // Build the HTML for one notification item. Both title and message
            // come from the DB, so both MUST be escaped. The item is rendered
            // as a <button type="button"> so it is keyboard-focusable and
            // announces itself as interactive to screen readers.
            function renderItem(n) {
                var meta   = iconForType(n.type);
                var title  = n.title || '';
                var msg    = n.message || '';
                var hasLink = !!(n.link && n.link !== '#');

                // Show title as a heading if present and different from message;
                // otherwise just show the message. Either way, never double-render.
                var titleHtml = '';
                if (title && title !== msg) {
                    titleHtml = '<p class="notification-title">' + escapeHtml(title) + '</p>';
                }
                var msgHtml = msg
                    ? '<p class="notification-message">' + escapeHtml(msg) + '</p>'
                    : '';

                return ''
                    + '<button type="button" class="notification-item unread"'
                    +        ' data-id="' + escapeHtml(n.id) + '"'
                    +        ' data-link="' + escapeHtml(n.link || '') + '"'
                    +        (hasLink ? '' : ' data-no-link="true"')
                    +        ' role="listitem">'
                    +   '<span class="notification-icon" style="color:' + meta.color + '" aria-hidden="true">'
                    +     '<i class="fas ' + meta.icon + '"></i>'
                    +   '</span>'
                    +   '<span class="notification-content">'
                    +     titleHtml
                    +     msgHtml
                    +     '<span class="notification-time">' + escapeHtml(formatTimeAgo(new Date(n.created_at))) + '</span>'
                    +   '</span>'
                    + '</button>';
            }

            function updateNotificationUI(count, notifications) {
                var badge = document.getElementById('notifBadge');
                var btn   = document.getElementById('notifBtn');

                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : String(count);
                    badge.hidden = false;
                    if (btn) btn.setAttribute('aria-label', 'Notifications, ' + count + ' unread');
                } else {
                    badge.hidden = true;
                    if (btn) btn.setAttribute('aria-label', 'Notifications');
                }

                var list = document.getElementById('notifList');
                list.innerHTML = '';

                if (!notifications || notifications.length === 0) {
                    var empty = document.createElement('div');
                    empty.className = 'notification-empty';
                    empty.textContent = 'No new notifications';
                    list.appendChild(empty);
                    return;
                }

                var frag = document.createDocumentFragment();
                notifications.forEach(function (n) {
                    var wrap = document.createElement('div');
                    wrap.innerHTML = renderItem(n);
                    var item = wrap.firstElementChild;
                    if (item) {
                        item.addEventListener('click', function () { onItemClick(item); });
                        frag.appendChild(item);
                    }
                });
                list.appendChild(frag);
            }

            // ============================================================
            // Network — fetch + POST with CSRF
            // ============================================================

            function fetchNotifications() {
                return fetch('/notifications/unread', {
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.status === 'success') {
                        updateNotificationUI(data.count, data.notifications || []);
                    }
                })
                .catch(function (err) {
                    console.warn('Notification poll failed:', err);
                });
            }

            // POST a notification action. CI4 CSRF filter rotates the token
            // on every successful POST, so the controller MUST echo back
            // the new hash in its JSON response. We update the meta tag
            // here so the NEXT POST (and the global synapse-ui helpers)
            // pick up the fresh value.
            function postNotificationAction(url, onSuccess) {
                return fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': getCsrfToken()
                    }
                })
                .then(function (r) {
                    return r.json().catch(function () { return {}; }).then(function (body) {
                        return { ok: r.ok, status: r.status, body: body };
                    });
                })
                .then(function (resp) {
                    if (resp.body && resp.body.csrf_hash) {
                        setCsrfToken(resp.body.csrf_hash);
                    }
                    if (resp.ok && resp.body && resp.body.status === 'success') {
                        if (typeof onSuccess === 'function') onSuccess(resp.body);
                    } else {
                        console.warn('Notification POST failed:', resp.status, resp.body);
                        if (window.synapse && typeof synapse.toast === 'function') {
                            synapse.toast({
                                type: 'error',
                                title: 'Could not update notification',
                                message: (resp.body && resp.body.message) || ('HTTP ' + resp.status),
                                duration: 4000
                            });
                        }
                    }
                    return resp;
                })
                .catch(function (err) {
                    console.warn('Notification POST error:', err);
                });
            }

            // ============================================================
            // Item / button handlers
            // ============================================================

            function onItemClick(item) {
                var id   = item.getAttribute('data-id');
                var link = item.getAttribute('data-link');
                postNotificationAction('/notifications/read/' + encodeURIComponent(id), function () {
                    if (link && link !== '') {
                        window.location.href = link;
                    } else {
                        fetchNotifications();
                    }
                });
            }

            function markAllRead() {
                postNotificationAction('/notifications/read/all', function () {
                    fetchNotifications();
                    var panel = document.getElementById('notifPanel');
                    if (panel) panel.classList.remove('open');
                    var btn = document.getElementById('notifBtn');
                    if (btn) btn.setAttribute('aria-expanded', 'false');
                });
            }

            // Expose the few entry points the inline onclick= handlers use.
            window.toggleNotifications = function () {
                var panel = document.getElementById('notifPanel');
                var btn   = document.getElementById('notifBtn');
                if (!panel || !btn) return;
                var isOpen = panel.classList.toggle('open');
                btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            };
            window.markAllRead     = markAllRead;
            window.markRead        = onItemClick; // legacy alias

            // ============================================================
            // Lifecycle — initial fetch, polling, outside-click, hidden tab
            // ============================================================

            document.addEventListener('DOMContentLoaded', function () {
                if (!document.getElementById('notifBtn')) return;

                fetchNotifications();

                // Poll, but pause when the tab is hidden so we don't waste
                // requests in the background. The visibilitychange event is
                // the standard hook for this and is supported by every
                // modern browser.
                var pollHandle = null;
                function startPolling() {
                    if (pollHandle) return;
                    pollHandle = setInterval(fetchNotifications, 30000);
                }
                function stopPolling() {
                    if (pollHandle) { clearInterval(pollHandle); pollHandle = null; }
                }
                startPolling();
                document.addEventListener('visibilitychange', function () {
                    if (document.hidden) {
                        stopPolling();
                    } else {
                        fetchNotifications();
                        startPolling();
                    }
                });

                // Close panel on outside click. Keep keyboard parity: Esc
                // also closes the panel.
                document.addEventListener('click', function (event) {
                    var panel = document.getElementById('notifPanel');
                    var btn   = document.getElementById('notifBtn');
                    if (panel && panel.classList.contains('open')
                            && !panel.contains(event.target)
                            && !btn.contains(event.target)) {
                        panel.classList.remove('open');
                        btn.setAttribute('aria-expanded', 'false');
                    }
                });
                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        var panel = document.getElementById('notifPanel');
                        var btn   = document.getElementById('notifBtn');
                        if (panel && panel.classList.contains('open')) {
                            panel.classList.remove('open');
                            btn.setAttribute('aria-expanded', 'false');
                            btn.focus();
                        }
                    }
                });
            });
        })();

        // ============================================================
        // Page-load skeleton hider
        // The <body> shows a shimmer skeleton on first paint. We hide
        // it once DOM is interactive + all inline <script>s have run.
        // The transition uses opacity so the page below fades in
        // smoothly.
        // ============================================================
        (function () {
            var loader = document.getElementById('synPageLoading');
            if (!loader) return;

            function hideLoader() {
                loader.classList.add('is-hidden');
                // Remove from DOM after the fade so it doesn't trap clicks.
                setTimeout(function () {
                    if (loader && loader.parentNode) {
                        loader.parentNode.removeChild(loader);
                    }
                }, 250);
            }

            // Hide immediately if the document is already complete (i.e.
            // this script ran after DOMContentLoaded). Otherwise wait.
            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                // Defer to next frame so the page paints at least once
                // without flicker.
                requestAnimationFrame(function () { requestAnimationFrame(hideLoader); });
            } else {
                document.addEventListener('DOMContentLoaded', function () {
                    requestAnimationFrame(function () { requestAnimationFrame(hideLoader); });
                }, { once: true });
            }

            // Fallback: never block longer than 2s, even if some inline
            // script throws or a sub-resource hangs.
            setTimeout(hideLoader, 2000);
        })();
    </script>
</body>
</html>
