<?php
/**
 * 500 — Internal Server Error / Unhandled Exception
 *
 * This view is used by CodeIgniter's HTTP\Exceptions\* chain (specifically
 * for code 500 / uncaught Throwable) when Config\Exceptions.php is wired to
 * route through here. Stack traces are NEVER rendered to the user — they are
 * already in the log.
 *
 * Framework-supplied variables (when invoked via Exceptions::render()):
 *   - $exception  the Throwable that triggered this page
 *
 * Optional variables (for direct testing / fallback):
 *   - $heading, $description  override the default copy
 */

$isLoggedIn = (bool) session()->get('logged_in');
$role       = session()->get('primary_role') ?? 'guest';
$requested  = '/' . ltrim(service('request')->getUri()->getPath(), '/');

// Show technical detail only in development, never in production
$showDebug = (defined('ENVIRONMENT') && ENVIRONMENT === 'development')
          && isset($exception) && $exception instanceof \Throwable;

$heading = $heading ?? 'Something went wrong on our end.';
$description = $description
    ?? 'Our engineers have been notified automatically. You can retry the request, ' .
       'go back to the previous page, or return to your dashboard. ' .
       'If this keeps happening, please share the reference number with support so we can investigate.';

$actions = [];
if ($isLoggedIn) {
    $actions[] = ['label' => 'Retry the request',  'url' => $requested,                          'variant' => 'primary',   'icon' => 'fas fa-rotate-right'];
    $actions[] = ['label' => 'Back to dashboard',  'url' => base_url('dashboard'),               'variant' => 'secondary', 'icon' => 'fas fa-th-large'];
    $actions[] = ['label' => 'Contact support',    'url' => error_mailto_link('500 error report', 'I encountered a 500 at ' . $requested . '.'), 'variant' => 'ghost', 'icon' => 'fas fa-envelope'];
} else {
    $actions[] = ['label' => 'Return home',        'url' => base_url('/'),                       'variant' => 'primary',   'icon' => 'fas fa-home'];
    $actions[] = ['label' => 'Sign in',            'url' => base_url('login'),                   'variant' => 'secondary', 'icon' => 'fas fa-sign-in-alt'];
}

$supportHtml  = '<h2 class="error-context-title">What we already know</h2>';
$supportHtml .= '<ul style="list-style: none; padding: 0; margin: 0; font-size: 0.85rem; color: var(--gray-700); line-height: 1.7;">';
$supportHtml .= '<li style="padding: 0.35rem 0;">This incident is recorded in the audit log with the reference number on the right.</li>';
$supportHtml .= '<li style="padding: 0.35rem 0;">No data was lost &mdash; any unsaved changes on the previous page were preserved locally.</li>';
$supportHtml .= '<li style="padding: 0.35rem 0;">SYNAPSE runs active monitoring &mdash; most transient failures clear themselves within a minute.</li>';
$supportHtml .= '</ul>';

if ($showDebug) {
    $safeClass = esc(get_class($exception));
    $safeMsg   = esc($exception->getMessage());
    $safeFile  = esc(clean_path($exception->getFile()));
    $safeLine  = (int) $exception->getLine();
    $supportHtml .= '<details style="margin-top: 1.25rem; padding: 0.75rem; background: #FEF2F2; border: 1px solid #FECACA; border-radius: 0.5rem;">'
                  . '<summary style="cursor: pointer; font-size: 0.78rem; font-weight: 700; color: #B91C1C;">Developer details (development only)</summary>'
                  . '<pre style="margin: 0.75rem 0 0; padding: 0.75rem; background: #1F2937; color: #F9FAFB; border-radius: 0.4rem; font-size: 0.72rem; overflow-x: auto; white-space: pre-wrap;">'
                  . $safeClass . "\n" . $safeMsg . "\n\nat " . $safeFile . ':' . $safeLine . "\n"
                  . '</pre></details>';
}

echo view('errors/_layout', [
    'code'        => '500',
    'title'       => 'Unexpected error',
    'statusLabel' => 'Server Error',
    'heading'     => $heading,
    'description' => $description,
    'variant'     => 'danger',
    'actions'     => $actions,
    'supportHtml' => $supportHtml,
    'requestPath' => $requested,
    'userRole'    => (string) $role,
]);