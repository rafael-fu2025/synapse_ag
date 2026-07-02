<?php
/**
 * Shared layout used by all error pages.
 *
 * Rendered via:
 *     echo view('errors/_layout', $layoutData)
 *
 * Expected $layoutData keys (all optional, all have safe defaults):
 *   - string   $code         e.g. "403", "404", "500"
 *   - string   $title        e.g. "Access Denied", "Page Not Found"
 *   - string   $statusLabel  short status text under the code (e.g. "Forbidden")
 *   - string   $heading      one-sentence summary shown to the user
 *   - string   $description  longer explanation paragraph
 *   - array    $actions      list of [label, url, variant, icon] for action buttons
 *   - string   $contextHtml  pre-rendered HTML (output of error_context_panel())
 *   - string   $supportHtml  optional right-rail extra (e.g. links to docs)
 *   - string   $requestPath  optional URL the user tried (else uses uri_string())
 *   - string   $userRole     optional role label (else reads from session)
 *
 * This file deliberately:
 *   - Defines its own CSS (the full layout is unavailable here, so we cannot
 *     extend it without coupling to its session-dependent assumptions)
 *   - Reuses the exact same color tokens as layouts/main.php to stay on-brand
 *   - Loads only Inter + Font Awesome (no Chart.js, no debug bar shims)
 *   - Avoids the generic "big circle icon + giant status number" cliché
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <meta name="description" content="SYNAPSE — Campus Health and Counseling Management">
    <title><?= esc(($code ?? 'Error') . ' · ' . ($title ?? 'SYNAPSE')) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/synapse-ui.css') ?>">

    <style>
        /* ============================================================
           SYNAPSE Error Page — uses the same tokens as layouts/main.php
           ============================================================ */
        :root {
            --primary-50:  #FDF2F4;
            --primary-100: #FBE0E5;
            --primary-500: #B8304A;
            --primary-600: #9D2235;
            --primary-700: #7B1F2C;
            --primary-800: #5A1722;

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

            --success: #10B981;
            --warning: #F59E0B;
            --danger:  #EF4444;
            --info:    #9D2235;

            --hairline: 1px solid var(--gray-200);
            --radius-md: 10px;
            --radius-lg: 14px;
            --radius-pill: 9999px;

            --font-mono:
                'JetBrains Mono', ui-monospace, SFMono-Regular,
                'SF Mono', Menlo, Consolas, 'Liberation Mono', monospace;

            --transition: 200ms ease;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body { height: 100%; }

        body {
            font-family: 'Inter', 'PingFang SC', 'PingFang HK', 'Hiragino Sans GB',
                         'Microsoft YaHei', 'Noto Sans SC', 'Noto Sans', system-ui,
                         -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--gray-800);
            line-height: 1.55;
            background:
                radial-gradient(circle at 0% 0%,   var(--primary-50) 0%, transparent 45%),
                radial-gradient(circle at 100% 100%, var(--primary-100) 0%, transparent 40%),
                var(--gray-50);
            min-height: 100%;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        a { color: var(--primary-600); text-decoration: none; }
        a:hover { color: var(--primary-700); text-decoration: underline; }

        .err-shell {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Top bar — minimal, no full sidebar/nav */
        .err-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: saturate(180%) blur(8px);
        }
        .err-brand {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            text-decoration: none;
            color: var(--gray-900);
        }
        .err-brand:hover { text-decoration: none; color: var(--gray-900); }
        .err-brand-mark {
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
        }

        .err-brand-mark .err-brand-logo {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }
        .err-brand-name {
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        .err-brand-wordmark {
            display: block;
            height: 20px;
            width: auto;
            object-fit: contain;
        }
        .err-brand-sub {
            display: block;
            font-size: 0.65rem;
            font-weight: 500;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            line-height: 1.1;
        }
        .err-topbar-actions { display: flex; gap: 0.5rem; }
        .err-topbar-link {
            font-size: 0.85rem;
            color: var(--gray-600);
            padding: 0.4rem 0.75rem;
            border-radius: 0.4rem;
        }
        .err-topbar-link:hover { background: var(--gray-100); color: var(--gray-800); text-decoration: none; }

        /* Main two-column area */
        .err-main {
            flex: 1;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 360px;
            gap: 3rem;
            padding: 3rem 1.5rem;
            max-width: 1180px;
            width: 100%;
            margin: 0 auto;
        }

        @media (max-width: 900px) {
            .err-main { grid-template-columns: 1fr; gap: 2rem; padding: 2rem 1rem; }
        }

        .err-content { max-width: 640px; }

        .err-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.75rem;
            border-radius: 99px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            background: var(--gray-100);
            color: var(--gray-700);
            border: 1px solid var(--gray-200);
            font-variant-numeric: tabular-nums;
        }
        .err-status-pill.is-danger  { background: #FEF2F2; color: var(--danger);  border-color: #FECACA; }
        .err-status-pill.is-warning { background: #FFFBEB; color: #B45309;       border-color: #FDE68A; }
        .err-status-pill.is-info    { background: var(--primary-50); color: var(--primary-700); border-color: var(--primary-100); }

        .err-code {
            margin-top: 1rem;
            font-size: 4rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1;
            color: var(--gray-900);
            font-variant-numeric: tabular-nums;
        }
        .err-title {
            margin-top: 0.5rem;
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-900);
            letter-spacing: -0.015em;
        }
        .err-heading {
            margin-top: 1.25rem;
            font-size: 1.05rem;
            color: var(--gray-700);
        }
        .err-description {
            margin-top: 0.75rem;
            font-size: 0.95rem;
            color: var(--gray-600);
            max-width: 56ch;
        }

        .err-actions {
            margin-top: 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
        }
        .err-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.1rem;
            border-radius: 0.5rem;
            font-family: inherit;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all var(--transition);
            line-height: 1.2;
        }
        .err-btn:focus-visible {
            outline: 2px solid var(--primary-500);
            outline-offset: 2px;
        }
        .err-btn-primary {
            background: var(--primary-600);
            color: white;
        }
        .err-btn-primary:hover {
            background: var(--primary-700);
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 6px 14px -4px rgba(79, 70, 229, 0.4);
        }
        .err-btn-secondary {
            background: white;
            color: var(--gray-700);
            border-color: var(--gray-300);
        }
        .err-btn-secondary:hover {
            background: var(--gray-50);
            color: var(--gray-800);
            text-decoration: none;
            border-color: var(--gray-400);
        }
        .err-btn-ghost {
            background: transparent;
            color: var(--gray-600);
        }
        .err-btn-ghost:hover {
            background: var(--gray-100);
            color: var(--gray-800);
            text-decoration: none;
        }

        /* Right rail — context panel */
        .err-rail {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 0.75rem;
            padding: 1.5rem;
            align-self: start;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }

        .error-context-title {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--gray-500);
            margin-bottom: 1rem;
        }
        .error-context-list {
            display: grid;
            grid-template-columns: max-content 1fr;
            gap: 0.5rem 1rem;
            font-size: 0.825rem;
        }
        .error-context-list dt {
            color: var(--gray-500);
            font-weight: 500;
        }
        .error-context-list dd {
            color: var(--gray-800);
            font-weight: 600;
            word-break: break-word;
        }
        .error-context-list dd.err-ctx-mono {
            font-family: var(--font-mono);
            font-size: 0.78rem;
            color: var(--primary-700);
            font-feature-settings: 'liga' 0, 'calt' 0;
        }
        .error-context-hint {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--gray-100);
            font-size: 0.78rem;
            color: var(--gray-500);
            line-height: 1.5;
        }

        .err-rail + .err-rail { margin-top: 1rem; }

        /* Footer */
        .err-footer {
            padding: 1.25rem 1.5rem;
            border-top: 1px solid var(--gray-200);
            background: rgba(255, 255, 255, 0.5);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .err-footer-meta {
            font-size: 0.75rem;
            color: var(--gray-500);
            font-variant-numeric: tabular-nums;
        }
        .err-footer-links { display: flex; gap: 1rem; font-size: 0.75rem; }

        @media (max-width: 600px) {
            .err-topbar { padding: 0.75rem 1rem; }
            .err-main { padding: 1.5rem 1rem; }
            .err-code { font-size: 3rem; }
            .err-title { font-size: 1.4rem; }
        }
    </style>
</head>
<body>
    <div class="err-shell">

        <header class="err-topbar">
            <a href="<?= base_url('/') ?>" class="err-brand" aria-label="SYNAPSE home">
                <span class="err-brand-mark"><img src="<?= base_url('assets/img/logo.svg') ?>" alt="SYNAPSE mark" class="err-brand-logo"></span>
                <span>
                    <img src="<?= base_url('assets/img/text.svg') ?>" alt="SYNAPSE" class="err-brand-wordmark">
                    <span class="err-brand-sub">Campus Health</span>
                </span>
            </a>
            <div class="err-topbar-actions">
                <?php if (session()->get('logged_in')): ?>
                    <a href="<?= base_url('dashboard') ?>" class="err-topbar-link">
                        <i class="fas fa-th-large" aria-hidden="true"></i> Dashboard
                    </a>
                    <a href="<?= base_url('logout') ?>" class="err-topbar-link">
                        <i class="fas fa-sign-out-alt" aria-hidden="true"></i> Sign out
                    </a>
                <?php else: ?>
                    <a href="<?= base_url('login') ?>" class="err-topbar-link">
                        <i class="fas fa-sign-in-alt" aria-hidden="true"></i> Sign in
                    </a>
                <?php endif; ?>
            </div>
        </header>

        <main class="err-main">
            <section class="err-content">
                <?php
                    $code        = $code        ?? 'Error';
                    $title       = $title       ?? 'Something went wrong';
                    $statusLabel = $statusLabel ?? null;
                    $heading     = $heading     ?? null;
                    $description = $description ?? null;
                    $actions     = $actions     ?? [];
                    $variant     = $variant     ?? 'info';
                ?>

                <span class="err-status-pill <?= $variant === 'danger' ? 'is-danger' : ($variant === 'warning' ? 'is-warning' : 'is-info') ?>">
                    <span aria-hidden="true">●</span>
                    <?= esc($code) ?><?= $statusLabel ? ' · ' . esc($statusLabel) : '' ?>
                </span>

                <div class="err-code"><?= esc($code) ?></div>
                <h1 class="err-title"><?= esc($title) ?></h1>

                <?php if ($heading): ?>
                    <p class="err-heading"><?= esc($heading) ?></p>
                <?php endif; ?>

                <?php if ($description): ?>
                    <p class="err-description"><?= $description /* trusted HTML if set, else plain text via esc above */ ?></p>
                <?php endif; ?>

                <?php if (! empty($actions)): ?>
                    <div class="err-actions">
                        <?php foreach ($actions as $a):
                            $label   = $a['label']   ?? 'Continue';
                            $url     = $a['url']     ?? base_url('/');
                            $avariant= $a['variant'] ?? 'primary';
                            $icon    = $a['icon']    ?? null;
                            $btnClass = 'err-btn-' . ($avariant === 'primary' ? 'primary' : ($avariant === 'secondary' ? 'secondary' : 'ghost'));
                        ?>
                            <a href="<?= esc($url) ?>" class="err-btn <?= $btnClass ?>">
                                <?php if ($icon): ?><i class="<?= esc($icon) ?>" aria-hidden="true"></i><?php endif; ?>
                                <?= esc($label) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <aside>
                <?= $contextHtml ?? error_context_panel($requestPath ?? null, $userRole ?? null) ?>

                <?php if (! empty($supportHtml)): ?>
                    <section class="err-rail"><?= $supportHtml ?></section>
                <?php endif; ?>
            </aside>
        </main>

        <footer class="err-footer">
            <div class="err-footer-meta">
                <span>Ref&nbsp;<?= esc(error_request_id()) ?></span>
                <span style="margin: 0 0.5rem; color: var(--gray-300);">·</span>
                <span><?= esc(date('M d, Y · H:i')) ?></span>
            </div>
            <div class="err-footer-links">
                <a href="<?= base_url('/') ?>">Home</a>
                <a href="mailto:support@synapse.edu.ph">Support</a>
            </div>
        </footer>
    </div>
</body>
</html>