<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AuthFilter — Checks if the user is logged in.
 * Redirects unauthenticated users to the login page.
 *
 * Accepts either `logged_in` (set by AuthController::attemptLogin) or
 * `isLoggedIn` (legacy alias) so older session keys still work after
 * the 2026-06-28 auth refactor.
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $loggedIn = (bool) (session()->get('logged_in') ?: session()->get('isLoggedIn'));
        if (! $loggedIn) {
            // Store intended URL for redirect after login. We only do this
            // for safe same-origin GET requests — never for POST/PUT/DELETE
            // (an attacker could craft a form that POSTs to a protected URL,
            // trick the victim into submitting, and after login the victim
            // would be redirected to the attacker's page).
            if (strtoupper((string) $request->getMethod()) === 'GET') {
                $intended = (string) current_url();
                // Only accept paths that start with a single slash and contain
                // no scheme/host (defense against open-redirect).
                if (str_starts_with($intended, '/') && ! str_contains($intended, '//')) {
                    session()->setFlashdata('redirect_url', $intended);
                }
            }

            return redirect()->to('/login')
                ->with('error', 'Please log in to access that page.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do after
    }
}

