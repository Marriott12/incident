<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Incident;
use App\Models\AoSector;
use App\Middleware\AuthMiddleware;
use App\Services\AuthorizationService;

class ApiController
{
    public function incidentsGeojson(): void
    {
        AuthMiddleware::requirePermission('api.incidents.view');
        $m = new Incident();
        $incidents = AuthorizationService::filterVisibleIncidents($m->all(1000), AuthorizationService::currentUser());
        $features = [];
        foreach ($incidents as $inc) {
            $features[] = $m->toGeoJSONFeature($inc);
        }
        header('Content-Type: application/json');
        echo json_encode(['type' => 'FeatureCollection', 'features' => $features]);
    }

    public function sectors(): void
    {
        AuthMiddleware::requirePermission('api.sectors.view');
        $m = new AoSector();
        $sectors = $m->all();
        $features = [];
        foreach ($sectors as $s) {
            $gj = json_decode($s['geojson'], true);
            if ($gj) {
                $features[] = [
                    'type' => 'Feature',
                    'properties' => ['id' => $s['id'], 'name' => $s['name']],
                    'geometry' => $gj
                ];
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['type' => 'FeatureCollection', 'features' => $features]);
    }
}
