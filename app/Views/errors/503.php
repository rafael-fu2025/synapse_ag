<?php
/**
 * 503 — Service Unavailable / Maintenance Mode
 *
 * Used when the application is intentionally offline (deploy, DB maintenance,
 * scheduled outage). Includes a contact line + retry guidance.
 *
 * Variable (optional):
 *   - $retryAfter  string  hint like "~10 minutes", "soon"
 */

$retryAfter = $retryAfter ?? 'shortly';
$requested  = '/' . ltrim(service('request')->getUri()->getPath(), '/');

$heading     = 'SYNAPSE is briefly offline for maintenance.';
$description = 'We are performing scheduled maintenance to keep things running smoothly. ' .
               "SYNAPSE should be back {$retryAfter}. Your data is safe &mdash; no work has been lost.";

$actions = [];
$actions[] = ['label' => 'Retry now',           'url' => $requested,                                    'variant' => 'primary',   'icon' => 'fas fa-rotate-right'];
$actions[] = ['label' => 'Status page',         'url' => 'https://status.synapse.edu.ph',               'variant' => 'secondary', 'icon' => 'fas fa-signal'];
$actions[] = ['label' => 'Email support',       'url' => 'mailto:support@synapse.edu.ph',                'variant' => 'ghost',     'icon' => 'fas fa-envelope'];

$supportHtml  = '<h2 class="error-context-title">During this window</h2>';
$supportHtml .= '<ul style="list-style: none; padding: 0; margin: 0; font-size: 0.85rem; color: var(--gray-700); line-height: 1.7;">';
$supportHtml .= '<li style="padding: 0.35rem 0;">Scheduled maintenance windows are published on the status page in advance.</li>';
$supportHtml .= '<li style="padding: 0.35rem 0;">Active consultations, appointments, and outreach activities are paused &mdash; they will resume automatically.</li>';
$supportHtml .= '<li style="padding: 0.35rem 0;">Audit logging continues in degraded mode so no records are lost.</li>';
$supportHtml .= '</ul>';

echo view('errors/_layout', [
    'code'        => '503',
    'title'       => 'Temporarily unavailable',
    'statusLabel' => 'Maintenance',
    'heading'     => $heading,
    'description' => $description,
    'variant'     => 'info',
    'actions'     => $actions,
    'supportHtml' => $supportHtml,
    'requestPath' => $requested,
]);