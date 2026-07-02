<?php
/**
 * 403 — Access Denied
 *
 * Reached when the authenticated user lacks the role/permission for a route,
 * typically because the RoleFilter blocks them.
 *
 * Customised per audience:
 *   - Logged-out users: prompt to sign in (or sign in as a different account)
 *   - Logged-in users: tell them their role can't reach this page,
 *                      show what they can do, and how to ask for access.
 *
 * Variables provided by the framework:
 *   - $code, $title, $heading (string|null) — defaults already supplied by _layout
 */

$isLoggedIn = (bool) session()->get('logged_in');
$role       = session()->get('primary_role') ?? 'guest';
$fullName   = trim((string) session()->get('full_name'));

$requested = '/' . ltrim(service('request')->getUri()->getPath(), '/');

// Headline copy — different for logged-in vs anonymous
if ($isLoggedIn) {
    $statusLabel = 'Forbidden';
    $heading     = 'Your account does not have access to this page.';
    $description = 'You are signed in as ' . ($fullName !== '' ? "<strong>" . esc($fullName) . "</strong> " : '') .
                   '(' . esc(ucwords(str_replace('_', ' ', (string) $role))) . '). ' .
                   'The page you requested, <code>' . esc($requested) . '</code>, ' .
                   'is restricted to a different role in SYNAPSE.';
} else {
    $statusLabel = 'Sign-in required';
    $heading     = 'You need to be signed in to view this page.';
    $description = 'SYNAPSE restricts clinical, counselling, and administrative pages to authenticated users. ' .
                   'Sign in with your institutional account, or return to the home page.';
}

// Actions — context sensitive
$actions = [];
if ($isLoggedIn) {
    $actions[] = ['label' => 'Back to my dashboard',   'url' => base_url('dashboard'),                                                                                'variant' => 'primary',   'icon' => 'fas fa-th-large'];
    $actions[] = ['label' => 'Request access',          'url' => error_mailto_link('Access request', "I need access to {$requested}."),                              'variant' => 'secondary', 'icon' => 'fas fa-envelope'];
    $actions[] = ['label' => 'Sign in as another user', 'url' => base_url('logout'),                                                                                  'variant' => 'ghost',     'icon' => 'fas fa-right-left'];
} else {
    $actions[] = ['label' => 'Sign in',                 'url' => base_url('login'),                                                                                   'variant' => 'primary',   'icon' => 'fas fa-sign-in-alt'];
    $actions[] = ['label' => 'Return to home',          'url' => base_url('/'),                                                                                      'variant' => 'secondary', 'icon' => 'fas fa-home'];
}

// Right-rail "what now" panel — explains the next step without being patronising
$supportHtml = '<h2 class="error-context-title">What you can do</h2>'
    . '<ol style="padding-left: 1.2rem; font-size: 0.85rem; color: var(--gray-700); line-height: 1.7;">'
    . '<li>If you believe you should have access, contact your SYNAPSE administrator with the reference number on the right.</li>'
    . ($isLoggedIn
        ? '<li>You can sign out and sign back in with a different account if you have one.</li>'
        : '<li>If you do not have an account, ask your institution\'s SYNAPSE administrator to issue one.</li>')
    . '<li>All requests are logged for security review.</li>'
    . '</ol>';

echo view('errors/_layout', [
    'code'        => '403',
    'title'       => 'Access Denied',
    'statusLabel' => $statusLabel,
    'heading'     => $heading,
    'description' => $description,
    'variant'     => 'danger',
    'actions'     => $actions,
    'supportHtml' => $supportHtml,
    'requestPath' => $requested,
    'userRole'    => (string) $role,
]);
