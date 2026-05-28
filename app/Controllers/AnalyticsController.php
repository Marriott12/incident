<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Incident;
use App\Models\AnalyticsModel;
use App\Middleware\AuthMiddleware;
use App\Services\AuthorizationService;

class AnalyticsController extends BaseController
{
    public function index(): void
    {
        AuthMiddleware::requirePermission('analytics.view');

        // Parse date range filter from query string
        $daysParam = isset($_GET['days']) ? trim((string)$_GET['days']) : '30';
        if ($daysParam === '7') {
            $days = 7;
        } elseif ($daysParam === '30') {
            $days = 30;
        } elseif ($daysParam === '90') {
            $days = 90;
        } elseif ($daysParam === 'all') {
            $days = null;
        } else {
            $days = 30;
            $daysParam = '30';
        }

        // Load all incidents visible to this user
        $incidentModel = new Incident();
        $user = AuthorizationService::currentUser();
        $allIncidents = AuthorizationService::filterVisibleIncidents($incidentModel->all(5000), $user);
        $ids = array_map(fn($inc) => (int)$inc['id'], $allIncidents);

        $analytics = new AnalyticsModel();

        // Get recent activity and incidents for the activity feed
        $auditLog = new \App\Models\AuditLog();
        $recentActivity = $auditLog->getRecentActivity(15, $days);
        $recentIncidents = array_slice($allIncidents, 0, 8);

        $this->render('analytics/index.php', [
            'metrics'      => $analytics->metrics($ids, $days),
            'byProvince'   => $analytics->byProvince($ids, $days),
            'byDistrict'   => $analytics->byDistrict($ids, $days),
            'byAoSector'   => $analytics->byAoSector($ids, $days),
            'byType'       => $analytics->byType($ids, $days),
            'byThreat'     => $analytics->byThreatLevel($ids, $days),
            'byStatus'     => $analytics->byStatus($ids, $days),
            'byShift'      => $analytics->byShift($ids, $days),
            'trend'        => $analytics->trend($ids, $days),
            'topUnits'     => $analytics->topUnits($ids, $days),
            'geoSummary'   => $analytics->geographicSummary($ids, $days),
            'personnel'    => $analytics->personnelExposure($ids, $days),
            'resolved'     => $analytics->resolvedCount($ids, $days),
            'avgResponseTime' => $analytics->averageResponseTime($ids, $days),
            'recentActivity' => $recentActivity,
            'recentIncidents' => $recentIncidents,
            'days'         => $days,
            'daysParam'    => $daysParam,
            'page_title'   => 'Analytics — IncidentOps',
        ]);
    }
}
