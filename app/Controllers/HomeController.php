<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AuthorizationService;

class HomeController extends BaseController
{
    public function index(): void
    {
        AuthMiddleware::requirePermission('dashboard.view');
        $incidentModel = new \App\Models\Incident();
        $incidents = AuthorizationService::filterVisibleIncidents(
            $incidentModel->all(100),
            AuthorizationService::currentUser()
        );

        $stats = [
            'total'        => count($incidents),
            'total_open'   => 0,
            'total_resolved' => 0,
            'high_critical'=> 0,
            'pending_hq'   => 0,
            'today'        => 0,
            'by_status'    => ['open' => 0, 'contained' => 0, 'closed' => 0, 'under_review' => 0],
            'by_threat'    => ['critical' => 0, 'high' => 0, 'moderate' => 0, 'low' => 0],
        ];
        $today = date('Y-m-d');
        foreach ($incidents as $inc) {
            $status = $inc['status'] ?? '';
            $threat = $inc['threat_level'] ?? '';
            if ($status === 'open') $stats['total_open']++;
            if ($status === 'closed') $stats['total_resolved']++;
            if (in_array($threat, ['high','critical'], true)) $stats['high_critical']++;
            if (!empty($inc['submitted_to_hq_at']) && $status !== 'closed') $stats['pending_hq']++;
            if (!empty($inc['created_at']) && date('Y-m-d', strtotime($inc['created_at'])) === $today) $stats['today']++;
            if (isset($stats['by_status'][$status])) $stats['by_status'][$status]++;
            if (isset($stats['by_threat'][$threat])) $stats['by_threat'][$threat]++;
        }

        // Get analytics data using visible incident IDs
        $ids = array_map(fn($inc) => (int)$inc['id'], $incidents);
        $analyticsModel = new \App\Models\AnalyticsModel();
        $analyticsMetrics = $analyticsModel->metrics($ids, 30);
        $avgResponseTime = $analyticsModel->averageResponseTime($ids, 30);
        $threatBreakdown = $analyticsModel->byThreatLevel($ids, 30);
        $statusBreakdown = $analyticsModel->byStatus($ids, 30);
        $topProvinces = $analyticsModel->byProvince($ids, 30);
        $topUnits = $analyticsModel->topUnits($ids, 8);
        $trendData = $analyticsModel->trend($ids, 30);

        // Get recent activity feed
        $auditLog = new \App\Models\AuditLog();
        $recentActivity = $auditLog->getRecentActivity(10);

        // Only show the 25 most recent in the table
        $recent = array_slice($incidents, 0, 25);

        $this->render('dashboard/index.php', [
            'incidents' => $recent,
            'stats'     => $stats,
            'analyticsMetrics' => $analyticsMetrics,
            'avgResponseTime' => $avgResponseTime,
            'threatBreakdown' => $threatBreakdown,
            'statusBreakdown' => $statusBreakdown,
            'topProvinces' => $topProvinces,
            'topUnits' => $topUnits,
            'trendData' => $trendData,
            'pending' => [],
            'recentActivity' => $recentActivity,
            'csrf_token'=> \App\Services\CsrfService::generateToken(),
            'show_map'  => true,
            'page_title'=> 'Dashboard — IncidentOps',
            'landing_role' => null,
            'landing_title' => 'Operational Dashboard',
            'landing_description' => 'Your operational overview for incident management.',
        ]);
    }

    public function landing(string $role): void
    {
        AuthMiddleware::requirePermission('dashboard.view');

        $user = AuthorizationService::currentUser();
        $actualRole = $user['role'] ?? 'incident_officer';

        if ($actualRole !== $role) {
            $this->redirect($this->getRedirectForRole($actualRole));
            return;
        }

        $incidentModel = new \App\Models\Incident();
        $incidents = AuthorizationService::filterVisibleIncidents(
            $incidentModel->all(100),
            $user
        );

        $stats = [
            'total'        => count($incidents),
            'total_open'   => 0,
            'high_critical'=> 0,
            'pending_hq'   => 0,
            'today'        => 0,
            'by_status'    => ['open' => 0, 'contained' => 0, 'closed' => 0, 'under_review' => 0],
            'by_threat'    => ['critical' => 0, 'high' => 0, 'moderate' => 0, 'low' => 0],
        ];

        $today = date('Y-m-d');
        foreach ($incidents as $inc) {
            $status = $inc['status'] ?? '';
            $threat = $inc['threat_level'] ?? '';
            if ($status === 'open') $stats['total_open']++;
            if (in_array($threat, ['high','critical'], true)) $stats['high_critical']++;
            if (!empty($inc['submitted_to_hq_at']) && $status !== 'closed') $stats['pending_hq']++;
            if (!empty($inc['created_at']) && date('Y-m-d', strtotime($inc['created_at'])) === $today) $stats['today']++;
            if (isset($stats['by_status'][$status])) $stats['by_status'][$status]++;
            if (isset($stats['by_threat'][$threat])) $stats['by_threat'][$threat]++;
        }

        $recent = array_slice($incidents, 0, 25);

        // Get analytics data using visible incident IDs
        $ids = array_map(fn($inc) => (int)$inc['id'], $incidents);
        $analyticsModel = new \App\Models\AnalyticsModel();
        $analyticsMetrics = $analyticsModel->metrics($ids, 30);
        $avgResponseTime = $analyticsModel->averageResponseTime($ids, 30);
        $threatBreakdown = $analyticsModel->byThreatLevel($ids, 30);
        $statusBreakdown = $analyticsModel->byStatus($ids, 30);
        $topProvinces = $analyticsModel->byProvince($ids, 30);
        $topUnits = $analyticsModel->topUnits($ids, 8);
        $trendData = $analyticsModel->trend($ids, 30);

        // Role-specific pending queue
        $pending = [];
        if ($role === 'g_staff') {
            foreach ($incidents as $inc) {
                if (($inc['formation'] ?? '') !== '' && ($inc['formation'] ?? '') === ($user['formation'] ?? '')) {
                    if ($inc['status'] === 'open' || $inc['status'] === 'under_review') $pending[] = $inc;
                }
                // also include incidents matching province when formation not set
                if (empty($inc['formation']) && !empty($inc['province']) && ($inc['province'] ?? '') === ($user['province'] ?? '')) {
                    if ($inc['status'] === 'open' || $inc['status'] === 'under_review') $pending[] = $inc;
                }
            }
        } elseif ($role === 'formation_commander') {
            foreach ($incidents as $inc) {
                if (($inc['formation'] ?? '') === ($user['formation'] ?? '') && $inc['status'] === 'g_staff_review') {
                    $pending[] = $inc;
                }
            }
        } elseif ($role === 'army_hq') {
            foreach ($incidents as $inc) {
                if (!empty($inc['approved_at']) || $inc['status'] === 'approved') {
                    $pending[] = $inc;
                }
            }
        }

        $landingData = [
            'g_staff' => [
                'title' => 'G Staff Dashboard',
                'description' => 'Review and approve CPO incidents for your brigade/formation before they are escalated.',
            ],
            'formation_commander' => [
                'title' => 'Formation Commander Dashboard',
                'description' => 'Review approved CPO incidents and prepare them for Army HQ visibility.',
            ],
            'cpo' => [
                'title' => 'CPO Dashboard',
                'description' => 'Provincial incident overview and trends for your brigade/formation.',
            ],
            'hq_readonly' => [
                'title' => 'HQ Read Only Dashboard',
                'description' => 'View-only operational status and incident summaries from headquarters.',
            ],
            'incident_officer' => [
                'title' => 'Incident Officer Dashboard',
                'description' => 'Create, manage, and monitor incidents assigned to your unit.',
            ],
            'army_hq' => [
                'title' => 'Army HQ Dashboard',
                'description' => 'Consolidated national reports and analytics across all provinces.',
            ],
        ];

        $pageHeading = $landingData[$role]['title'] ?? 'Operational Dashboard';
        $pageDescription = $landingData[$role]['description'] ?? 'Your operational overview for incident management.';

        $this->render('dashboard/index.php', [
            'incidents' => $recent,
            'stats'     => $stats,
            'analyticsMetrics' => $analyticsMetrics,
            'avgResponseTime' => $avgResponseTime,
            'threatBreakdown' => $threatBreakdown,
            'statusBreakdown' => $statusBreakdown,
            'topProvinces' => $topProvinces,
            'topUnits' => $topUnits,
            'trendData' => $trendData,
            'pending' => $pending,
            'csrf_token'=> \App\Services\CsrfService::generateToken(),
            'show_map'  => true,
            'page_title'=> $pageHeading . ' — IncidentOps',
            'landing_role' => $role,
            'landing_title' => $pageHeading,
            'landing_description' => $pageDescription,
        ]);
    }

    private function getRedirectForRole(string $role): string
    {
        if ($role === 'admin') {
            return '/admin/users';
        }

        if ($role === 'g_staff') {
            return '/dashboard/g-staff';
        }

        if ($role === 'formation_commander') {
            return '/dashboard/formation-commander';
        }

        if ($role === 'hq_readonly') {
            return '/dashboard/hq-readonly';
        }

        if ($role === 'incident_officer') {
            return '/dashboard/incident-officer';
        }

        if ($role === 'cpo') {
            return '/dashboard/cpo';
        }

        if ($role === 'army_hq') {
            return '/dashboard/army-hq';
        }

        return '/dashboard';
    }
}
