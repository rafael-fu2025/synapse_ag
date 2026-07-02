<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AuditLogModel;

/**
 * Admin landing page — system overview tiles that link to the
 * sub-modules (users, roles, audit, system modules).
 */
class DashboardController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();

        // KPI snapshots for landing tiles
        $totalUsers       = (int) $db->table('users')->countAllResults();
        $activeUsers      = (int) $db->table('users')->where('is_active', true)->countAllResults();
        $totalRoles       = (int) $db->table('roles')->countAllResults();
        $totalPermissions = (int) $db->table('permissions')->countAllResults();
        $totalAuditLogs   = (int) $db->table('audit_logs')->countAllResults();
        $totalSysModules  = (int) $db->table('system_modules')->countAllResults();

        // Recent audit activity (top 5)
        $recentAudit = $db->table('audit_logs al')
            ->select('al.*, u.first_name, u.last_name')
            ->join('users u', 'u.id = al.user_id', 'left')
            ->orderBy('al.created_at', 'DESC')
            ->limit(5)
            ->get()->getResultArray();

        // Last 7 days — audit volume (for a small line chart)
        $auditTrend = $db->table('audit_logs')
            ->select('DATE(created_at) AS day, COUNT(*) AS cnt', false)
            ->where('created_at >=', date('Y-m-d', strtotime('-6 days')) . ' 00:00:00')
            ->groupBy('day')
            ->orderBy('day', 'ASC')
            ->get()->getResultArray();

        // Chain integrity quick check (limited to last 100 entries — cheap)
        $integrity = (new AuditLogModel())->verifyChainIntegrity(100);

        // System modules list (with toggle capability)
        $systemModules = $db->table('system_modules')
            ->orderBy('display_name', 'ASC')
            ->get()->getResultArray();

        return view('admin/dashboard', [
            'title'            => 'Admin Console — SYNAPSE',
            'heading'          => 'System Administration',
            'totalUsers'       => $totalUsers,
            'activeUsers'      => $activeUsers,
            'totalRoles'       => $totalRoles,
            'totalPermissions' => $totalPermissions,
            'totalAuditLogs'   => $totalAuditLogs,
            'totalSysModules'  => $totalSysModules,
            'recentAudit'      => $recentAudit,
            'auditTrend'       => $auditTrend,
            'integrity'        => $integrity,
            'systemModules'    => $systemModules,
        ]);
    }
}