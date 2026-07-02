<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SYNAPSE — Campus Health & Counseling Management System">
    <title><?= esc($title ?? 'Login — SYNAPSE') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/synapse-ui.css') ?>">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --maroon-50:  #FDF2F4;
            --maroon-100: #FBE0E5;
            --maroon-200: #F5BCC6;
            --maroon-300: #EA8E9F;
            --maroon-400: #D45D78;
            --maroon-500: #B8304A;
            --maroon-600: #9D2235;
            --maroon-700: #7B1F2C;
            --maroon-800: #5A1722;
            --maroon-900: #3D0F18;
            --maroon-950: #260810;
            --teal-700: #0F766E;
            --teal-800: #115E59;
            --teal-900: #134E4A;
            --teal-950: #042F2E;
            --ink-900: #0C1821;
            --ink-800: #1B2A36;
            --ink-700: #2D3F4E;
            --paper: #FAFAF8;
            --stone-200: #E7E5E4;
            --stone-300: #D6D3D1;
            --stone-400: #A8A29E;
            --stone-500: #78716C;
            --stone-600: #57534E;
            --stone-700: #44403C;
            --amber-500: #F59E0B;
            --red-50: #FEF2F2;
            --red-500: #EF4444;
            --red-700: #B91C1C;
            --green-50: #F0FDF4;
            --green-600: #16A34A;
            --green-700: #15803D;
        }

        /* ============================================================
           COMPACT UI SCALE (90%)
           Toggled by the inline <script> below if the user has
           compactUi = true (default) in their saved preferences.
           Scales all rem units by 90% — fonts, padding, margins.
           ============================================================ */
        html.syn-compact-ui {
            font-size: 14.4px;
            --syn-field-height: 34.2px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            color: var(--ink-800);
            background: var(--paper);
        }

        /* ============================================================
           LEFT PANEL — Brand / Illustration
           ============================================================ */
        .brand-panel {
            flex: 1;
            background: var(--maroon-700);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 2.75rem 3rem;
            position: relative;
            overflow: hidden;
        }

        /* Subtle topographic line pattern — institutional, not flashy */
        .brand-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                repeating-linear-gradient(
                    135deg,
                    transparent 0,
                    transparent 60px,
                    rgba(255, 255, 255, 0.02) 60px,
                    rgba(255, 255, 255, 0.02) 61px
                );
            pointer-events: none;
        }

        .brand-panel::after {
            content: '';
            position: absolute;
            bottom: -120px;
            right: -120px;
            width: 360px;
            height: 360px;
            background: radial-gradient(circle, rgba(157, 34, 53, 0.18) 0%, transparent 70%);
            pointer-events: none;
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            position: relative;
            z-index: 1;
        }

        .brand-mark {
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: transparent;
            border: none;
        }

        .brand-mark .brand-logo {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .brand-header-text h1 {
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            line-height: 1;
        }

        .brand-header-text .brand-wordmark {
            display: block;
            height: 22px;
            width: auto;
            object-fit: contain;
        }

        .brand-header-text span {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: rgba(255, 255, 255, 0.55);
            display: block;
            margin-top: 0.2rem;
        }

        .brand-header-text .brand-institution {
            font-family: 'Times New Roman', Times, serif;
            font-size: 0.7rem;
            font-weight: 400;
            letter-spacing: 0.18em;
            color: rgba(255, 255, 255, 0.7);
            font-style: normal;
        }

        .brand-footer {
            position: relative;
            z-index: 1;
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.45);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .brand-footer .divider {
            width: 1px;
            height: 12px;
            background: rgba(255, 255, 255, 0.2);
        }

        /* ============================================================
           RIGHT PANEL — Form
           ============================================================ */
        .form-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 2rem;
            background: var(--paper);
        }

        .form-container {
            width: 100%;
            max-width: 360px;
        }

        .form-panel h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--ink-900);
            letter-spacing: -0.02em;
            margin-bottom: 0.4rem;
        }

        .form-panel .subtitle {
            font-size: 0.875rem;
            color: var(--stone-500);
            margin-bottom: 2rem;
        }

        /* Alerts */
        .alert {
            padding: 0.7rem 0.85rem;
            border-radius: 6px;
            margin-bottom: 1.25rem;
            font-size: 0.8rem;
            font-weight: 500;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            line-height: 1.45;
        }

        .alert i {
            margin-top: 0.1rem;
            flex-shrink: 0;
        }

        .alert-danger {
            background: var(--red-50);
            color: var(--red-700);
            border: 1px solid #FECACA;
        }

        .alert-success {
            background: var(--green-50);
            color: var(--green-700);
            border: 1px solid #BBF7D0;
        }

        /* Form fields */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--stone-700);
            margin-bottom: 0.4rem;
            letter-spacing: 0.01em;
        }

        .form-input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 0.7rem 0.85rem;
            background: white;
            border: 1px solid var(--stone-300);
            border-radius: 6px;
            color: var(--ink-900);
            font-family: inherit;
            font-size: 0.875rem;
            outline: none;
            transition: border-color 150ms ease, box-shadow 150ms ease;
        }

        #password.form-input {
            padding-right: 2.5rem;
        }

        .form-input::placeholder {
            color: var(--stone-400);
        }

        .form-input:focus {
            border-color: var(--maroon-600);
            box-shadow: 0 0 0 3px rgba(157, 34, 53, 0.12);
        }

        /* Password toggle */
        .password-toggle {
            position: absolute;
            right: 0.6rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--stone-400);
            cursor: pointer;
            font-size: 0.85rem;
            padding: 0.35rem;
            border-radius: 4px;
            transition: color 150ms ease, background 150ms ease;
        }

        .password-toggle:hover {
            color: var(--stone-600);
            background: var(--stone-200);
        }

        /* Submit button — solid, no gradient */
        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background: var(--maroon-700);
            color: white;
            border: none;
            border-radius: 6px;
            font-family: inherit;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 150ms ease;
            margin-top: 0.25rem;
        }

        .btn-login:hover {
            background: var(--maroon-800);
        }

        .btn-login:active {
            background: var(--maroon-900);
        }

        /* ============================================================
           Responsive
           ============================================================ */
        @media (max-width: 860px) {
            body {
                flex-direction: column;
            }

            .brand-panel {
                min-height: auto;
                padding: 2rem 1.5rem;
            }

            .brand-footer {
                display: none;
            }

            .form-panel {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- LEFT: Brand Panel -->
    <aside class="brand-panel">
        <div class="brand-header">
            <div class="brand-mark">
                <img src="<?= base_url('assets/img/logo.svg') ?>" alt="SYNAPSE mark" class="brand-logo">
            </div>
            <div class="brand-header-text">
                <img src="<?= base_url('assets/img/text.svg') ?>" alt="SYNAPSE" class="brand-wordmark">
                <span class="brand-institution">F&nbsp;O&nbsp;U&nbsp;N&nbsp;D&nbsp;A&nbsp;T&nbsp;I&nbsp;O&nbsp;N&nbsp;&nbsp;&nbsp;U&nbsp;N&nbsp;I&nbsp;V&nbsp;E&nbsp;R&nbsp;S&nbsp;I&nbsp;T&nbsp;Y</span>
            </div>
        </div>

        <div class="brand-footer">
            <span>&copy; <?= date('Y') ?> SYNAPSE</span>
            <span class="divider"></span>
            <span>Authorized personnel only</span>
        </div>
    </aside>

    <!-- RIGHT: Form Panel -->
    <main class="form-panel">
        <div class="form-container">
            <h2>Sign in</h2>
            <p class="subtitle">Enter your credentials to access the system.</p>

            <!-- Flash Messages -->
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-circle-exclamation"></i>
                    <span><?= esc(session()->getFlashdata('error')) ?></span>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success">
                    <i class="fas fa-circle-check"></i>
                    <span><?= esc(session()->getFlashdata('success')) ?></span>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('errors')): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-circle-exclamation"></i>
                    <div>
                        <?php foreach (session()->getFlashdata('errors') as $err): ?>
                            <div><?= esc($err) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form action="<?= base_url('login') ?>" method="POST" novalidate data-synapse-submit data-synapse-no-spin="false">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="form-input-wrapper">
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-input"
                            value="<?= esc(old('email')) ?>"
                            required
                            autocomplete="email"
                            spellcheck="false"
                            autocapitalize="off"
                            inputmode="email"
                            autofocus
                            maxlength="254"
                            aria-describedby="email-hint"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="form-input-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            required
                            autocomplete="current-password"
                            minlength="10"
                            maxlength="200"
                            aria-describedby="password-hint"
                        >
                        <button
                            type="button"
                            class="password-toggle"
                            id="passwordToggle"
                            aria-label="Show password"
                            aria-pressed="false"
                            aria-controls="password"
                            title="Show password"
                        >
                            <i class="fas fa-eye" id="toggle-icon" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    Sign In
                </button>
            </form>
        </div>
    </main>

    <script>
        // Password visibility toggle. Keeps aria-pressed in sync so screen
        // readers announce the new state, and exposes the action as a
        // progressive enhancement — the form is still usable if JS is off.
        (function () {
            var btn   = document.getElementById('passwordToggle');
            var input = document.getElementById('password');
            var icon  = document.getElementById('toggle-icon');
            if (!btn || !input || !icon) return;

            btn.addEventListener('click', function () {
                var isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
                icon.classList.toggle('fa-eye', !isHidden);
                icon.classList.toggle('fa-eye-slash', isHidden);
                btn.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
                btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                btn.setAttribute('title', isHidden ? 'Hide password' : 'Show password');
                // Keep focus on the toggle so screen-reader users hear the
                // state change announcement.
                btn.focus();
            });
        })();
        // Respect the user's saved "Compact UI (90%)" preference from
        // localStorage so login stays consistent with the rest of the
        // app. Safe-fail silently if storage is disabled.
        (function () {
            try {
                var prefs = JSON.parse(localStorage.getItem('synapse.user.preferences') || '{}');
                if (prefs.compactUi !== false) {
                    /* default ON if unset, so even first visit gets 90% */
                    document.documentElement.classList.add('syn-compact-ui');
                }
            } catch (e) { /* ignore */ }
        })();
    </script>
</body>
</html>
