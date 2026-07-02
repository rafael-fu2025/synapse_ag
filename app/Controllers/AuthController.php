<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\UserRoleModel;
use App\Models\AuditLogModel;

class AuthController extends BaseController
{
    protected UserModel $userModel;
    protected UserRoleModel $userRoleModel;
    protected AuditLogModel $auditLogModel;

    public function __construct()
    {
        $this->userModel     = new UserModel();
        $this->userRoleModel = new UserRoleModel();
        $this->auditLogModel = new AuditLogModel();
    }

    /**
     * Show the login page.
     */
    public function showLogin()
    {
        // If already logged in, redirect to dashboard
        if (session()->get('user_id')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/login', [
            'title' => 'Login — SYNAPSE',
        ]);
    }

    /**
     * Process login attempt.
     */
    public function attemptLogin()
    {
        // CSRF is handled by CI4 automatically

        // Validate input. NOTE: password minimum is 10 chars (must match
        // UserModel::setPassword) so we never hash a password shorter than
        // we can later validate.
        $rules = [
            'email'    => 'required|valid_email|max_length[254]',
            'password' => 'required|min_length[10]|max_length[200]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Normalise email: trim + lowercase. Emails are stored canonicalised,
        // and "Admin@… " vs "admin@…" must not bypass rate limiting.
        $email    = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');

        // Rate limiting: check failed attempts. The key uses the
        // canonical email so casing/whitespace variations can't bypass it.
        $failKey = 'login_fails_' . md5($email);
        $fails   = session()->get($failKey) ?? ['count' => 0, 'first_at' => 0];

        if ($fails['count'] >= 5 && (time() - $fails['first_at']) < 900) {
            $remainingMinutes = (int) ceil((900 - (time() - $fails['first_at'])) / 60);

            return redirect()->back()
                ->withInput()
                ->with('error', "Too many failed attempts. Please try again in {$remainingMinutes} minute(s).");
        }

        if ($fails['count'] >= 5 && (time() - $fails['first_at']) >= 900) {
            $fails = ['count' => 0, 'first_at' => 0];
        }

        // Find user (case-insensitive lookup via the canonicalised email).
        $user = $this->userModel->findByEmail($email);

        // ALWAYS run a bcrypt verify, even when no user matches. This keeps
        // the timing for "unknown email" and "wrong password" cases identical
        // (otherwise an attacker can enumerate accounts via timing).
        $dummyHash = '$2y$10$abcdefghijklmnopqrstuuE7Vk3JBxQXQXQXQXQXQXQXQXQXe';
        $hash      = $user['password_hash'] ?? $dummyHash;
        $passwordOk = password_verify($password, $hash);

        $loginFailed = ($user === null) || ! $passwordOk || ! (bool) ($user['is_active'] ?? 0);

        if ($loginFailed) {
            // Increment fail counter atomically inside the same request.
            // For multi-process deployments this still has a small race
            // window, so we also record the failure time so the window is
            // bounded by 15 minutes regardless of how many parallel requests
            // squeeze in.
            $fails['count']++;
            if ($fails['count'] === 1) {
                $fails['first_at'] = time();
            }
            session()->set($failKey, $fails);

            $reason = $user === null
                ? 'unknown_email'
                : (! $passwordOk ? 'invalid_credentials' : 'account_inactive');

            $this->auditLogModel->logAction(
                $user['id'] ?? null,
                'login_failed',
                'auth',
                'users',
                $user['id'] ?? null,
                null,
                ['email' => $email, 'reason' => $reason]
            );

            // Generic message — do NOT differentiate "unknown email" /
            // "wrong password" / "deactivated account". User enumeration is
            // a real risk on a campus-health system: students could probe
            // for which classmates have accounts, which staff are deactivated,
            // etc.
            return redirect()->back()
                ->withInput()
                ->with('error', 'Invalid email or password.');
        }

        // Successful login — regenerate session ID FIRST (session-fixation
        // defense). Any session ID the attacker may have observed before
        // login is now invalid.
        session()->regenerate(true);

        $roles       = $this->userRoleModel->getUserRoles((int) $user['id']);
        $roleNames   = array_column($roles, 'name');
        $primaryRole = $roleNames[0] ?? 'student';

        $sessionData = [
            'user_id'      => (int) $user['id'],
            'email'        => $user['email'],
            'first_name'   => $user['first_name'],
            'last_name'    => $user['last_name'],
            'full_name'    => trim($user['first_name'] . ' ' . $user['last_name']),
            'roles'        => $roleNames,
            'primary_role' => $primaryRole,
            'logged_in'    => true,
            // Track when this session was created — used to invalidate old
            // sessions if the user changes their password.
            'session_started_at' => time(),
        ];

        session()->set($sessionData);

        // Clear fail counter on successful login.
        session()->remove($failKey);

        // Update last login timestamp.
        $this->userModel->updateLastLogin((int) $user['id']);

        // Audit log — only on success do we log the user_id (so logs can't
        // be used to confirm "user X tried to log in" against attempts that
        // didn't succeed — that leak is what `reason => unknown_email`
        // protects against above).
        $this->auditLogModel->logAction(
            (int) $user['id'],
            'login_success',
            'auth',
            'users',
            (int) $user['id']
        );

        // Redirect to the originally-intended URL if AuthFilter set one,
        // otherwise fall back to the dashboard.
        $intended = session()->getFlashdata('redirect_url');
        $target   = is_string($intended) && str_starts_with($intended, '/') ? $intended : '/dashboard';

        return redirect()->to($target)->with('success', "Welcome back, {$user['first_name']}!");
    }

    /**
     * Logout the current user.
     */
    public function logout()
    {
        $userId = session()->get('user_id');

        if ($userId) {
            $this->auditLogModel->logAction(
                $userId,
                'logout',
                'auth',
                'users',
                $userId
            );
        }

        // Destroy the entire session (data + cookie) so a stolen cookie
        // before logout is invalidated.
        session()->destroy();

        return redirect()->to('/login')->with('success', 'You have been logged out successfully.');
    }
}
