<?php

/**
 * Error Page Helpers
 *
 * - error_request_id()   Returns a stable per-request ID (X-Request-Id) used
 *                        in error pages so users can quote it to support.
 * - error_context(...)   Renders a small "incident context" panel:
 *                        request id, path, when, who — for 4xx pages.
 *
 * Loaded via Composer autoload ("files") is one option, but to keep things
 * simple we rely on CI4's helper auto-discovery for any function defined in
 * app/Helpers. Each function here is small and side-effect-free.
 */

if (! function_exists('error_request_id')) {
    /**
     * Return a short, sortable request ID — first 8 chars of a v4-ish string.
     * Generated once per request via static cache.
     */
    function error_request_id(): string
    {
        static $id = null;
        if ($id === null) {
            // Prefer header set by reverse proxy / load balancer if present
            $hdr = $_SERVER['HTTP_X_REQUEST_ID'] ?? null;
            if (is_string($hdr) && preg_match('/^[A-Za-z0-9._-]{6,64}$/', $hdr)) {
                $id = substr($hdr, 0, 16);
            } else {
                $id = substr(bin2hex(random_bytes(8)), 0, 10);
            }
            // Surface as response header for downstream tracing
            if (function_exists('header') && ! headers_sent()) {
                header('X-Request-Id: ' . $id);
            }
        }
        return $id;
    }
}

if (! function_exists('error_context_panel')) {
    /**
     * Render a compact "incident context" panel (request id, path, role, ts).
     * Used by all error pages except the maintenance page.
     *
     * @param string|null $requestedPath  The URL the user tried.
     * @param string|null $userRole       The role of the logged-in user, if any.
     */
    function error_context_panel(?string $requestedPath = null, ?string $userRole = null): string
    {
        $rid  = error_request_id();
        $path = $requestedPath ?? (uri_string() !== '' ? uri_string() : '/');
        $ts   = date('M d, Y · H:i:s');
        $role = $userRole ?? (session()->get('primary_role') ?? 'guest');

        $rows = [
            ['Reference',   $rid,                                          'mono'],
            ['Path',        '/' . ltrim($path, '/'),                       'mono'],
            ['When',        $ts,                                           'plain'],
            ['Signed in as', $role,                                        'plain'],
        ];

        $html  = '<aside class="error-context" aria-label="Incident context">';
        $html .= '<h2 class="error-context-title">For support</h2>';
        $html .= '<dl class="error-context-list">';
        foreach ($rows as [$label, $value, $kind]) {
            $safeValue = esc($value);
            $html .= "<dt>{$label}</dt>";
            $html .= "<dd class=\"err-ctx-{$kind}\">{$safeValue}</dd>";
        }
        $html .= '</dl>';
        $html .= '<p class="error-context-hint">Quote the reference when contacting the SYNAPSE team so we can find your request in the logs immediately.</p>';
        $html .= '</aside>';

        return $html;
    }
}

if (! function_exists('error_mailto_link')) {
    /**
     * Build a mailto: URL with prefilled subject + body including the request
     * context. Kept as a helper so all error pages render it identically.
     */
    function error_mailto_link(string $subject, string $summary): string
    {
        $rid  = error_request_id();
        $path = '/' . ltrim(uri_string() ?? '/', '/');
        $ts   = date('Y-m-d H:i:s');

        $body = "Hello SYNAPSE support,\n\n"
              . "I encountered an error while using SYNAPSE.\n\n"
              . "What I was doing:\n[Please describe]\n\n"
              . "Reference: {$rid}\n"
              . "Path: {$path}\n"
              . "When: {$ts}\n\n"
              . "Error summary: {$summary}\n\n"
              . "Thank you.";

        return 'mailto:support@synapse.edu.ph'
             . '?subject=' . rawurlencode('[SYNAPSE] ' . $subject . ' [' . $rid . ']')
             . '&body='    . rawurlencode($body);
    }
}