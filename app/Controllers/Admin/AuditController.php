<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AuditLogModel;

/**
 * Admin Audit Log Viewer
 *
 * Endpoints:
 *   GET  /admin/audit             → paginated table with filters
 *   GET  /admin/audit/verify      → run chain-integrity check (last 1000 entries)
 *   GET  /admin/audit/export      → CSV download of filtered logs
 */
class AuditController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();

        $q       = trim((string) ($this->request->getGet('q') ?? ''));
        $module  = trim((string) ($this->request->getGet('module') ?? ''));
        $action  = trim((string) ($this->request->getGet('action') ?? ''));
        $userId  = (int) ($this->request->getGet('user_id') ?? 0);
        $startDT = trim((string) ($this->request->getGet('start') ?? ''));
        $endDT   = trim((string) ($this->request->getGet('end') ?? ''));

        $builder = $db->table('audit_logs al')
            ->select('al.*, u.first_name, u.last_name, u.email')
            ->join('users u', 'u.id = al.user_id', 'left');

        if ($q !== '') {
            $builder->groupStart()
                ->like('al.entity_type', $q)
                ->orLike('al.ip_address', $q)
                ->orLike('al.entity_id', $q)
                ->groupEnd();
        }

        if ($module !== '') $builder->where('al.module', $module);
        if ($action !== '') $builder->where('al.action', $action);
        if ($userId > 0)    $builder->where('al.user_id', $userId);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDT) === 1) {
            $builder->where('al.created_at >=', $startDT . ' 00:00:00');
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDT) === 1) {
            $builder->where('al.created_at <=', $endDT . ' 23:59:59');
        }

        $builder->orderBy('al.created_at', 'DESC');

        $page        = max(1, (int) ($this->request->getGet('page') ?? 1));
        /* Per-page selector: 25 / 50 / 100 / 200, default 50.
           Clamp to a sane range so a malicious query string can't
           request a million-row page. */
        $perPageRaw  = (int) ($this->request->getGet('per_page') ?? 50);
        $perPage     = max(10, min(500, $perPageRaw ?: 50));

        // Count for pagination — clone builder first so we don't reset limit
        $total = $builder->countAllResults(false);
        /* Clamp page to actual range so an over-large ?page= query
           parameter (e.g. from a stale bookmark) doesn't return 0 rows. */
        $page = max(1, min($page, max(1, (int) ceil($total / $perPage))));

        $logs = (clone $builder)
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()->getResultArray();

        // Distinct modules + actions for filter dropdowns
        $modules = $db->table('audit_logs')->distinct()->select('module')
            ->orderBy('module')->get()->getResultArray();
        $actions = $db->table('audit_logs')->distinct()->select('action')
            ->orderBy('action')->get()->getResultArray();

        // Light integrity check (last 100 entries) — surfaced in UI banner
        $integrity = (new AuditLogModel())->verifyChainIntegrity(100);

        return view('admin/audit_logs', [
            'title'      => 'Audit Logs — SYNAPSE',
            'heading'    => 'System Audit Logs',
            'logs'       => $logs,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => (int) ceil($total / $perPage),
            'total'      => $total,
            'modules'    => array_column($modules, 'module'),
            'actions'    => array_column($actions, 'action'),
            'q'          => $q,
            'module'     => $module,
            'action'     => $action,
            'userId'     => $userId,
            'start'      => $startDT,
            'end'        => $endDT,
            'integrity'  => $integrity,
        ]);
    }

    public function verify()
    {
        $integrity = (new AuditLogModel())->verifyChainIntegrity(1000);

        return view('admin/audit_verify', [
            'title'     => 'Audit Integrity — SYNAPSE',
            'heading'   => 'Hash-Chain Integrity Check',
            'integrity' => $integrity,
        ]);
    }

    public function export()
    {
        $db = \Config\Database::connect();

        $module = trim((string) ($this->request->getGet('module') ?? ''));
        $action = trim((string) ($this->request->getGet('action') ?? ''));
        $start  = trim((string) ($this->request->getGet('start') ?? ''));
        $end    = trim((string) ($this->request->getGet('end') ?? ''));

        $builder = $db->table('audit_logs al')
            ->select('al.created_at, al.action, al.module, al.entity_type, al.entity_id, al.ip_address, al.old_values, al.new_values, al.hash, al.previous_hash, u.first_name, u.last_name, u.email')
            ->join('users u', 'u.id = al.user_id', 'left')
            ->orderBy('al.created_at', 'DESC')
            ->limit(10000);

        if ($module !== '') $builder->where('al.module', $module);
        if ($action !== '') $builder->where('al.action', $action);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) === 1) $builder->where('al.created_at >=', $start . ' 00:00:00');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)   === 1) $builder->where('al.created_at <=', $end   . ' 23:59:59');

        $rows = $builder->get()->getResultArray();

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Timestamp', 'Action', 'Module', 'Entity Type', 'Entity ID', 'User', 'Email', 'IP', 'Hash', 'Previous Hash', 'Old Values', 'New Values']);
        foreach ($rows as $r) {
            fputcsv($handle, [
                $r['created_at'],
                $r['action'],
                $r['module'],
                $r['entity_type'],
                $r['entity_id'],
                trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')),
                $r['email'] ?? '',
                $r['ip_address'],
                $r['hash'],
                $r['previous_hash'],
                $r['old_values'],
                $r['new_values'],
            ]);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        // Audit the export itself
        (new AuditLogModel())->logAction(
            (int) session()->get('user_id'),
            'export',
            'admin',
            'audit_logs',
            null,
            null,
            ['filters' => compact('module', 'action', 'start', 'end'), 'row_count' => count($rows)]
        );

        $filename = 'synapse_audit_' . date('Ymd_His') . '.csv';
        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'no-store, no-cache')
            ->setBody($csv);
    }
}
