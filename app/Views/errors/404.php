<?php
/**
 * 404 — Page Not Found
 *
 * Variables from the framework (PageNotFoundException is rendered by CI4 via
 * $errorViewPath . '/html/error_404.php' but we override that — see
 * Config/Exceptions.php — and route every 404 through this file).
 */

$isLoggedIn = (bool) session()->get('logged_in');
$role       = session()->get('primary_role') ?? 'guest';

$req        = service('request');
$requested  = '/' . ltrim($req->getUri()->getPath(), '/');
$method     = strtoupper($req->getMethod());

// Suggest likely destinations based on role
$suggestions = [];
if ($isLoggedIn) {
    $suggestions[] = ['label' => 'Your dashboard', 'url' => base_url('dashboard'),                 'icon' => 'fas fa-th-large'];
    if (in_array($role, ['admin'], true)) {
        $suggestions[] = ['label' => 'User management', 'url' => base_url('admin/users'),         'icon' => 'fas fa-users'];
        $suggestions[] = ['label' => 'Reports',         'url' => base_url('reports'),              'icon' => 'fas fa-chart-bar'];
    }
    if (in_array($role, ['admin','clinic_staff'], true)) {
        $suggestions[] = ['label' => 'Clinic queue',    'url' => base_url('clinic/consultations'), 'icon' => 'fas fa-stethoscope'];
        $suggestions[] = ['label' => 'Inventory',       'url' => base_url('inventory'),            'icon' => 'fas fa-pills'];
    }
    if (in_array($role, ['admin','counsellor'], true)) {
        $suggestions[] = ['label' => 'Appointments',    'url' => base_url('counselling'),          'icon' => 'fas fa-calendar-check'];
    }
} else {
    $suggestions[] = ['label' => 'Sign in',           'url' => base_url('login'),                 'icon' => 'fas fa-sign-in-alt'];
}

$actions = [];
if ($isLoggedIn) {
    $actions[] = ['label' => 'Back to dashboard',   'url' => base_url('dashboard'),              'variant' => 'primary',   'icon' => 'fas fa-th-large'];
    $actions[] = ['label' => 'Report broken link',  'url' => error_mailto_link('Broken link report', "404 at {$requested}"), 'variant' => 'secondary', 'icon' => 'fas fa-flag'];
} else {
    $actions[] = ['label' => 'Sign in',             'url' => base_url('login'),                  'variant' => 'primary',   'icon' => 'fas fa-sign-in-alt'];
    $actions[] = ['label' => 'Return home',         'url' => base_url('/'),                     'variant' => 'secondary', 'icon' => 'fas fa-home'];
}

$description  = 'The page <code>' . esc($requested) . '</code> does not exist on SYNAPSE. ' .
                'It may have been moved, renamed, or the link may be incorrect. ' .
                'You can try one of the destinations below, or report the broken link to support.';

// Right-rail "Try one of these" panel
$suggestionHtml = '<h2 class="error-context-title">Try one of these</h2>'
    . '<ul style="list-style: none; padding: 0; margin: 0; display: grid; gap: 0.4rem;">';
foreach ($suggestions as $s) {
    $suggestionHtml .= '<li><a href="' . esc($s['url']) . '" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.55rem 0.75rem; border-radius: 0.5rem; background: var(--gray-50); color: var(--gray-700); font-size: 0.85rem; text-decoration: none;">'
                    . '<i class="' . esc($s['icon']) . '" style="color: var(--primary-600); width: 18px; text-align: center;" aria-hidden="true"></i>'
                    . esc($s['label']) . '</a></li>';
}
$suggestionHtml .= '</ul>';

echo view('errors/_layout', [
    'code'        => '404',
    'title'       => 'Page not found',
    'statusLabel' => 'Not Found',
    'heading'     => 'We could not find what you were looking for.',
    'description' => $description,
    'variant'     => 'warning',
    'actions'     => $actions,
    'supportHtml' => $suggestionHtml,
    'requestPath' => $requested,
    'userRole'    => (string) $role,
]);