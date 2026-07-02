<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * RoleFilter — Checks if the authenticated user has the required role(s).
 *
 * Usage in Routes.php:
 *   $routes->get('/admin', 'AdminController::index', ['filter' => 'role:admin']);
 *   $routes->get('/clinic', 'ClinicController::index', ['filter' => 'role:admin,clinic_staff']);
 */
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Must be logged in first
        if (! session()->get('logged_in')) {
            return redirect()->to('/login')
                ->with('error', 'Please log in to access that page.');
        }

        // If no role arguments specified, allow access
        if (empty($arguments)) {
            return;
        }

        // Get user's roles from session
        $userRoles = session()->get('roles') ?? [];

        // Check if user has any of the required roles
        $hasAccess = false;
        foreach ($arguments as $requiredRole) {
            if (in_array($requiredRole, $userRoles, true)) {
                $hasAccess = true;
                break;
            }
        }

        if (! $hasAccess) {
            // Return 403 Forbidden
            return response()->setStatusCode(403)
                ->setBody(view('errors/403', [
                    'title'   => '403 — Access Denied',
                    'heading' => 'Access Denied',
                ]));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do after
    }
}
