<?php
/**
 * Inline-SVG brand hero with pulse animation.
 *
 * Reusable brand mark for the welcome panels. Pure SVG + CSS — no JS, no
 * external assets. Safe to include on any view.
 *
 * Variables:
 *   - $heroSize (string, optional) — 'sm' (48px), 'md' (96px), 'lg' (160px),
 *                                   'xl' (240px). Default 'lg'.
 *   - $heroLabel (string, optional) — accessible label. Default 'SYNAPSE'.
 *
 * Usage:
 *   echo view('components/brand_hero', ['heroSize' => 'xl', 'heroLabel' => 'SYNAPSE — Campus Health']);
 */

$heroSize   = $heroSize   ?? 'lg';
$heroLabel  = $heroLabel  ?? 'SYNAPSE';

$sizes = [
    'sm' => 48,
    'md' => 96,
    'lg' => 160,
    'xl' => 240,
];
$px = $sizes[$heroSize] ?? $sizes['lg'];
$vb = 200; // viewBox is 0 0 200 200 — internal units

// Stroke widths scale with size
$ringStroke = $px <= 60 ? 1.2 : ($px <= 120 ? 1.8 : 2.4);
$coreStroke = $px <= 60 ? 1.5 : ($px <= 120 ? 2.4 : 3);
$glyphFill  = $px <= 60 ? 0.55 : ($px <= 120 ? 0.7 : 0.85);
?>
<svg class="brand-hero brand-hero--<?= esc($heroSize) ?>"
     role="img" aria-label="<?= esc($heroLabel) ?>"
     viewBox="0 0 <?= $vb ?> <?= $vb ?>"
     width="<?= $px ?>" height="<?= $px ?>"
     xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="bh-grad-<?= esc($heroSize) ?>" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%"  stop-color="var(--primary-400)" />
            <stop offset="50%" stop-color="var(--primary-600)" />
            <stop offset="100%" stop-color="var(--primary-800)" />
        </linearGradient>
        <radialGradient id="bh-glow-<?= esc($heroSize) ?>" cx="50%" cy="50%" r="50%">
            <stop offset="0%"  stop-color="var(--primary-300)" stop-opacity="0.55" />
            <stop offset="60%" stop-color="var(--primary-500)" stop-opacity="0.10" />
            <stop offset="100%" stop-color="var(--primary-500)" stop-opacity="0" />
        </radialGradient>
    </defs>

    <!-- Outer pulsing rings -->
    <circle class="bh-ring bh-ring--1" cx="100" cy="100" r="92"
            fill="none" stroke="url(#bh-grad-<?= esc($heroSize) ?>)"
            stroke-width="<?= $ringStroke ?>" stroke-opacity="0.45" />
    <circle class="bh-ring bh-ring--2" cx="100" cy="100" r="74"
            fill="none" stroke="url(#bh-grad-<?= esc($heroSize) ?>)"
            stroke-width="<?= $ringStroke ?>" stroke-opacity="0.35" />
    <circle class="bh-ring bh-ring--3" cx="100" cy="100" r="56"
            fill="none" stroke="url(#bh-grad-<?= esc($heroSize) ?>)"
            stroke-width="<?= $ringStroke ?>" stroke-opacity="0.25" />

    <!-- Soft glow behind the brain glyph -->
    <circle cx="100" cy="100" r="48" fill="url(#bh-glow-<?= esc($heroSize) ?>)" />

    <!-- Brain / synapse glyph: two hemispheres + center node + radiating signals -->
    <g class="bh-glyph" fill="url(#bh-grad-<?= esc($heroSize) ?>)">
        <!-- Left hemisphere -->
        <path d="M100,52
                 C 78,52 64,68 64,86
                 C 56,90 52,98 52,108
                 C 52,124 66,134 80,134
                 L 96,134
                 L 96,52 Z"
              fill-opacity="<?= $glyphFill ?>"
              stroke="url(#bh-grad-<?= esc($heroSize) ?>)"
              stroke-width="<?= $coreStroke ?>"
              stroke-linejoin="round" />
        <!-- Right hemisphere (mirror) -->
        <path d="M100,52
                 C 122,52 136,68 136,86
                 C 144,90 148,98 148,108
                 C 148,124 134,134 120,134
                 L 104,134
                 L 104,52 Z"
              fill-opacity="<?= $glyphFill ?>"
              stroke="url(#bh-grad-<?= esc($heroSize) ?>)"
              stroke-width="<?= $coreStroke ?>"
              stroke-linejoin="round" />
        <!-- Central cleft line -->
        <line x1="100" y1="56" x2="100" y2="130"
              stroke="url(#bh-grad-<?= esc($heroSize) ?>)"
              stroke-width="<?= max(1, $coreStroke / 2) ?>"
              stroke-opacity="0.55" />

        <!-- Synaptic dots inside each hemisphere -->
        <circle cx="78"  cy="78"  r="2.4" fill="white" fill-opacity="0.85" />
        <circle cx="84"  cy="100" r="2.0" fill="white" fill-opacity="0.75" />
        <circle cx="74"  cy="116" r="2.2" fill="white" fill-opacity="0.80" />
        <circle cx="122" cy="78"  r="2.4" fill="white" fill-opacity="0.85" />
        <circle cx="116" cy="100" r="2.0" fill="white" fill-opacity="0.75" />
        <circle cx="126" cy="116" r="2.2" fill="white" fill-opacity="0.80" />
    </g>

    <!-- Pulsing signal dots — radiate outward from the center -->
    <g class="bh-signals">
        <circle class="bh-signal bh-signal--n" cx="100" cy="20"  r="3" fill="var(--primary-500)" />
        <circle class="bh-signal bh-signal--e" cx="180" cy="100" r="3" fill="var(--primary-500)" />
        <circle class="bh-signal bh-signal--s" cx="100" cy="180" r="3" fill="var(--primary-500)" />
        <circle class="bh-signal bh-signal--w" cx="20"  cy="100" r="3" fill="var(--primary-500)" />
    </g>
</svg>

<style>
    .brand-hero { display: inline-block; vertical-align: middle; }
    .brand-hero--sm { /* tight spacing */ }
    .brand-hero--md { /* default */ }
    .brand-hero--lg { /* hero panel */ }
    .brand-hero--xl { /* landing */ }

    /* Pulsing rings — slow, calm */
    @keyframes bh-pulse {
        0%   { transform: scale(0.85); opacity: 0.0; }
        20%  { opacity: 1; }
        100% { transform: scale(1.15); opacity: 0; }
    }
    .bh-ring { transform-origin: 100px 100px; animation: bh-pulse 4s ease-out infinite; }
    .bh-ring--1 { animation-delay: 0s; }
    .bh-ring--2 { animation-delay: 1s; }
    .bh-ring--3 { animation-delay: 2s; }

    /* Signal dots — gentle bobbing */
    @keyframes bh-signal-pulse {
        0%, 100% { transform: scale(1);   opacity: 0.55; }
        50%      { transform: scale(1.5); opacity: 1;    }
    }
    .bh-signal { transform-origin: center; animation: bh-signal-pulse 2.4s ease-in-out infinite; }
    .bh-signal--n { animation-delay: 0s; }
    .bh-signal--e { animation-delay: 0.6s; }
    .bh-signal--s { animation-delay: 1.2s; }
    .bh-signal--w { animation-delay: 1.8s; }

    /* Glyph subtle breathing */
    @keyframes bh-glyph-breath {
        0%, 100% { transform: scale(1); }
        50%      { transform: scale(1.025); }
    }
    .bh-glyph { transform-origin: 100px 100px; animation: bh-glyph-breath 3.5s ease-in-out infinite; }

    /* Respect reduced-motion */
    @media (prefers-reduced-motion: reduce) {
        .bh-ring, .bh-signal, .bh-glyph { animation: none; }
    }
</style>