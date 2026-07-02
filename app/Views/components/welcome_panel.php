<?php
/**
 * Welcome / hero panel — sits at the top of dashboard pages.
 *
 * Variables (all optional):
 *   - $welcomeTitle    string   Heading (default 'Welcome back, Admin')
 *   - $welcomeName     string   User's first name (from session by default)
 *   - $welcomeSubtitle string   Sub-line under heading
 *   - $welcomeContext  string   Small contextual note on the right
 *   - $heroSize        string   'sm'|'md'|'lg'|'xl' for the brand SVG
 *   - $actions         array    List of [label, url, icon, variant] buttons
 *
 * Usage:
 *   $data = [
 *     'welcomeTitle'    => 'Good morning, Maria',
 *     'welcomeSubtitle' => 'You have 3 screenings due today and 1 referral awaiting acknowledgement.',
 *     'welcomeContext'  => 'Saturday · June 27, 2026',
 *     'heroSize'        => 'lg',
 *     'actions'         => [
 *       ['label' => 'Open queue',    'url' => '/clinic/consultations', 'icon' => 'fas fa-list',     'variant' => 'primary'],
 *       ['label' => 'View reports',  'url' => '/reports',               'icon' => 'fas fa-chart-bar','variant' => 'secondary'],
 *     ],
 *   ];
 *   echo view('components/welcome_panel', $data);
 */

$welcomeTitle    = $welcomeTitle    ?? 'Welcome to SYNAPSE';
$welcomeSubtitle = $welcomeSubtitle ?? 'A unified view of campus health, counselling, and outreach.';
$welcomeContext  = $welcomeContext  ?? date('l · F j, Y');
$heroSize        = $heroSize        ?? 'lg';
$actions         = $actions         ?? [];

// Get name from session if available
$firstName = trim((string) session()->get('first_name') ?? '');
if ($firstName !== '' && strpos($welcomeTitle, '{name}') !== false) {
    $welcomeTitle = str_replace('{name}', $firstName, $welcomeTitle);
}
?>
<section class="welcome-panel" aria-label="Welcome">
    <div class="welcome-hero">
        <?= view('components/brand_hero', ['heroSize' => $heroSize, 'heroLabel' => 'SYNAPSE']) ?>
    </div>

    <div class="welcome-body">
        <h1 class="welcome-title"><?= esc($welcomeTitle) ?></h1>
        <?php if ($welcomeSubtitle): ?>
            <p class="welcome-subtitle"><?= esc($welcomeSubtitle) ?></p>
        <?php endif; ?>

        <?php if (! empty($actions)): ?>
            <div class="welcome-actions">
                <?php foreach ($actions as $a):
                    $label    = $a['label']    ?? 'Continue';
                    $url      = $a['url']      ?? base_url('/');
                    $icon     = $a['icon']     ?? null;
                    $variant  = $a['variant']  ?? 'primary';
                ?>
                    <a href="<?= esc($url) ?>" class="btn btn-<?= esc($variant) ?>">
                        <?php if ($icon): ?><i class="<?= esc($icon) ?>" aria-hidden="true"></i><?php endif; ?>
                        <span><?= esc($label) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="welcome-aside">
        <div class="welcome-date">
            <i class="fas fa-calendar-day" aria-hidden="true"></i>
            <span><?= esc($welcomeContext) ?></span>
        </div>
    </div>
</section>

<style>
    .welcome-panel {
        position: relative;
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 2rem;
        align-items: center;
        padding: 2rem 2.25rem;
        margin-bottom: 1.75rem;
        background:
            radial-gradient(800px 320px at 0% 0%,    rgba(59,130,246,0.08) 0%, transparent 60%),
            radial-gradient(700px 280px at 100% 100%, rgba(59,130,246,0.06) 0%, transparent 60%),
            #ffffff;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-xl);
        overflow: hidden;
    }
    .welcome-panel::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(59,130,246,0.04), transparent 50%);
        pointer-events: none;
    }

    .welcome-hero {
        position: relative;
        z-index: 1;
        flex-shrink: 0;
    }
    .welcome-body {
        position: relative;
        z-index: 1;
        min-width: 0;
    }
    .welcome-title {
        font-family: var(--font-display);
        font-size: 1.85rem;
        font-weight: 700;
        letter-spacing: -0.025em;
        color: var(--gray-900);
        margin: 0 0 0.4rem;
        line-height: 1.15;
    }
    .welcome-subtitle {
        font-size: 0.95rem;
        color: var(--gray-600);
        margin: 0;
        max-width: 60ch;
        line-height: 1.5;
    }
    .welcome-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 1.25rem;
    }

    .welcome-aside {
        position: relative;
        z-index: 1;
        text-align: right;
    }
    .welcome-date {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.85rem;
        background: var(--gray-50);
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-pill);
        font-size: 0.8rem;
        font-weight: 500;
        color: var(--gray-700);
        font-variant-numeric: tabular-nums;
    }
    .welcome-date i { color: var(--primary-600); }

    @media (max-width: 768px) {
        .welcome-panel {
            grid-template-columns: 1fr;
            text-align: center;
            padding: 1.5rem;
            gap: 1.25rem;
        }
        .welcome-hero { display: flex; justify-content: center; }
        .welcome-aside { text-align: center; }
        .welcome-actions { justify-content: center; }
        .welcome-title { font-size: 1.4rem; }
    }
</style>