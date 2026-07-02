<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'email',
        // 'password' is the raw plaintext supplied by callers; the
        // hashPassword() callback below hashes it into 'password_hash'
        // before insert/update. 'password_hash' is also allowed so
        // administrative migrations / imports can set a pre-hashed value
        // directly. CI4 enforces $allowedFields BEFORE beforeInsert fires
        // (BaseModel.php insert() flow), so 'password' must be listed here
        // or it would be silently stripped before the hash callback ever
        // sees it.
        'password',
        'password_hash',
        'first_name',
        'last_name',
        'middle_name',
        'avatar_url',
        'phone',
        'is_active',
        'email_verified_at',
        'totp_secret',
        'two_factor_enabled',
        'backup_codes',
        'last_login_at',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation rules applied automatically by insert()/update().
    // - For new records (no {id} placeholder) we require either 'password'
    //   (the raw plaintext hashed by the callback below) or a pre-hashed
    //   'password_hash' of at least 60 chars (bcrypt).
    // - For updates, password is optional — only validated if the caller
    //   passed one. We can't rely on the model's auto-validation to enforce
    //   "if present, validate" cleanly, so we keep it permissive and rely
    //   on the controller-level rules for new users.
    protected $validationRules = [
        'email'         => 'required|valid_email|is_unique[users.email,id,{id}]',
        'first_name'    => 'required|max_length[100]',
        'last_name'     => 'required|max_length[100]',
    ];

    protected $validationMessages = [
        'email' => [
            'is_unique' => 'This email address is already registered.',
        ],
    ];

    // Callbacks
    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    /**
     * Hash password before insert/update if a raw plaintext password is
     * provided. The plaintext is removed from the row so it never reaches
     * the database. Pre-hashed values (>= 60 chars, looks like a bcrypt
     * hash) are accepted as-is so admin imports / migrations work.
     */
    protected function hashPassword(array $data): array
    {
        if (isset($data['data']['password'])) {
            $plain = (string) $data['data']['password'];

            // If the caller already passed a bcrypt hash, don't double-hash.
            // Bcrypt hashes are always $2y$..., 60 chars long.
            if (strlen($plain) === 60 && str_starts_with($plain, '$2y$')) {
                $data['data']['password_hash'] = $plain;
            } else {
                $data['data']['password_hash'] = password_hash(
                    $plain,
                    PASSWORD_BCRYPT,
                    ['cost' => 12]
                );
            }

            unset($data['data']['password']);
        }

        return $data;
    }

    /**
     * Set a new password for a user. Public helper used by the admin
     * "Reset password" flow. Validates length at the model boundary
     * so callers don't have to duplicate the rule.
     *
     * @return bool true on success
     * @throws \InvalidArgumentException when password is too short
     */
    public function setPassword(int $userId, string $plainPassword): bool
    {
        if (strlen($plainPassword) < 10) {
            throw new \InvalidArgumentException('Password must be at least 10 characters.');
        }

        $hash = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        return $this->update($userId, ['password_hash' => $hash]) !== false;
    }

    /**
     * Find a user by email address.
     *
     * Email matching is case-insensitive — MySQL's default utf8mb4_unicode_ci
     * collation already handles this on the WHERE clause, but we also trim
     * and lowercase the input so the underlying query matches the same row
     * the AuthController cached. Without this, "Admin@… " and "admin@…"
     * could end up at different rows when the column collation differs.
     */
    public function findByEmail(string $email): ?array
    {
        return $this->where('email', strtolower(trim($email)))->first();
    }

    /**
     * Verify a plaintext password against the stored hash.
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check if a user account is active.
     */
    public function isActive(int $userId): bool
    {
        $user = $this->find($userId);

        return $user !== null && (bool) $user['is_active'];
    }

    /**
     * Update the last login timestamp.
     */
    public function updateLastLogin(int $userId): void
    {
        $this->update($userId, ['last_login_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Get user with their roles.
     */
    public function getUserWithRoles(int $userId): ?array
    {
        $user = $this->find($userId);

        if ($user === null) {
            return null;
        }

        $userRoleModel = new UserRoleModel();
        $user['roles'] = $userRoleModel->getUserRoles($userId);

        return $user;
    }

    /**
     * Count rows in clinical / inventory tables that reference this user
     * and would BLOCK a hard-delete because of RESTRICT foreign keys. Used
     * by the delete flow to decide whether a hard-delete is even legal.
     *
     * Note: this is a "blocker" count — it intentionally does NOT count
     * audit_logs (which uses SET NULL) or anything CASCADE-cleanable, since
     * those will be handled automatically by the database or by us before
     * issuing the DELETE.
     */
    public function countDeleteBlockers(int $userId): int
    {
        $db = \Config\Database::connect();

        // These are the 17 RESTRICT tables reported by the FK inspection.
        // We alias every column that points at users.id under the same
        // placeholder so a single parameter binding covers them all.
        $restrictQueries = [
            ['consultations',           'attending_user_id'],
            ['counselling_appointments','counsellor_id'],
            ['crisis_alerts',           'acknowledged_by'],
            ['crisis_alerts',           'assigned_counsellor_id'],
            ['crisis_alerts',           'escalated_to'],
            ['inventory_transactions',  'performed_by'],
            ['treatments',              'administered_by'],
            ['referrals',               'referred_by'],
            ['referrals',               'referred_to'],
            ['ai_triage_predictions',   'decided_by'],
            ['assessment_templates',    'created_by'],
            ['report_configurations',   'created_by'],
            ['scheduling_analytics',    'counsellor_id'],   // CASCADE, but counted so we surface it
            ['clinic_staff_schedules',  'user_id'],         // CASCADE, same
        ];

        $total = 0;
        foreach ($restrictQueries as [$table, $col]) {
            try {
                $count = $db->table($table)
                    ->where($col, $userId)
                    ->countAllResults();
                $total += (int) $count;
            } catch (\Throwable $e) {
                // Table might not exist yet on fresh installs; skip silently.
                continue;
            }
        }
        return $total;
    }

    /**
     * Anonymize a user record in place. Replaces personally identifying
     * fields with non-reversible placeholders while preserving the row so
     * existing clinical / audit FK references stay valid and historically
     * accurate. This is the recommended step for former staff accounts.
     *
     * @return bool true if the row was updated
     */
    public function anonymize(int $userId): bool
    {
        $user = $this->find($userId);
        if ($user === null) {
            return false;
        }

        return $this->update($userId, [
            'email'              => 'deleted-' . $userId . '@example.invalid',
            'first_name'         => '—',
            'last_name'          => 'Deleted user #' . $userId,
            'middle_name'        => null,
            'phone'              => null,
            'avatar_url'         => null,
            'totp_secret'        => null,
            'backup_codes'       => null,
            'password_hash'      => password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT, ['cost' => 12]),
            'is_active'          => false,
            'two_factor_enabled' => false,
            'email_verified_at'  => null,
        ]) !== false;
    }

    /**
     * Hard-delete a user record. Caller MUST have already verified that
     * countDeleteBlockers() returns zero — MySQL will reject the DELETE
     * anyway, but checking first gives a friendly error message.
     *
     * The 6 CASCADE children are explicitly removed first inside the same
     * transaction so the deletion is auditable rather than relying on the
     * database's hidden cascade behaviour. (CASCADE is also re-applied by
     * the DB, so the explicit deletes are idempotent.)
     *
     * @return bool true if the user row was deleted
     */
    public function hardDelete(int $userId): bool
    {
        $db = \Config\Database::connect();
        $db->transStart();

        // Explicit cascade so the audit log shows exactly what was removed.
        $db->table('user_roles')->where('user_id', $userId)->delete();
        $db->table('students')->where('user_id', $userId)->delete();
        $db->table('counsellor_availability')->where('counsellor_id', $userId)->delete();
        $db->table('notifications')->where('user_id', $userId)->delete();
        $db->table('refresh_tokens')->where('user_id', $userId)->delete();
        $db->table('scheduling_analytics')->where('counsellor_id', $userId)->delete();

        // SET NULL tables — clear the FK so the row stays for audit/history.
        $db->table('audit_logs')->where('user_id', $userId)->update(['user_id' => null]);
        $db->table('ai_generated_summaries')->where('generated_by', $userId)->update(['generated_by' => null]);
        $db->table('generated_reports')->where('generated_by', $userId)->update(['generated_by' => null]);

        // Finally remove the user row itself. This will throw an
        // IntegrityConstraintViolationException if any RESTRICT FK still
        // points here — we want to fail loudly, not silently.
        $deleted = $this->delete($userId, true);

        $db->transComplete();
        if ($db->transStatus() === false) {
            return false;
        }
        return (bool) $deleted;
    }

    /**
     * Bulk counterpart of the single-user delete. Always runs in a single
     * transaction so a mid-flight failure leaves no partial changes. For
     * hard-delete mode, users with blockers are reported in $skipped and
     * NOT deleted — the rest are processed. For soft / anonymize modes,
     * every selected user is processed (no blocker check needed).
     *
     * Returns:
     *   [
     *     'deleted'   => [ids...],   // successfully processed
     *     'skipped'   => [id => reason, ...], // could not be processed
     *     'snapshots' => [id => row, ...]    // pre-delete data for audit log
     *   ]
     */
    public function bulkDeleteByMode(array $userIds, string $mode): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds), fn($id) => $id > 0)));
        $result = ['deleted' => [], 'skipped' => [], 'snapshots' => []];

        if (empty($userIds)) {
            return $result;
        }

        // Capture pre-state snapshots for the audit log so we always know
        // what each user looked like at the moment of deletion — even if
        // the surrounding transaction rolls back.
        foreach ($userIds as $id) {
            $u = $this->find($id);
            if ($u !== null) {
                $result['snapshots'][$id] = $u;
            } else {
                $result['skipped'][$id] = 'User not found.';
            }
        }

        // For hard-delete, separate clean vs blocked up front.
        if ($mode === 'hard') {
            foreach (array_keys($result['snapshots']) as $id) {
                $blockers = $this->countDeleteBlockers($id);
                if ($blockers > 0) {
                    $result['skipped'][$id] = "Has {$blockers} related record(s) in clinical/inventory tables. Use 'Anonymize' instead.";
                    unset($result['snapshots'][$id]);
                }
            }
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            foreach ($result['snapshots'] as $id => $_snapshot) {
                if ($mode === 'soft') {
                    $this->update($id, ['is_active' => false]);
                    $result['deleted'][] = $id;
                } elseif ($mode === 'anonymize') {
                    $this->anonymize($id);
                    $result['deleted'][] = $id;
                } elseif ($mode === 'hard') {
                    // Re-call hardDelete; we already verified blockers=0 above
                    // but if a race introduced one, this will fail and the
                    // transaction will roll back the whole batch.
                    $ok = $this->hardDelete($id);
                    if (! $ok) {
                        throw new \RuntimeException("Hard delete failed for user {$id}");
                    }
                    $result['deleted'][] = $id;
                }
            }
        } catch (\Throwable $e) {
            $db->transRollback();
            // Report every selected user as skipped so the caller knows
            // nothing was applied.
            return [
                'deleted'   => [],
                'skipped'   => array_fill_keys($userIds, 'Transaction failed: ' . $e->getMessage()),
                'snapshots' => [],
            ];
        }

        $db->transComplete();
        if ($db->transStatus() === false) {
            return [
                'deleted'   => [],
                'skipped'   => array_fill_keys($userIds, 'Transaction did not commit.'),
                'snapshots' => [],
            ];
        }

        return $result;
    }
}
