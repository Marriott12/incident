<?php
declare(strict_types=1);

/**
 * INCIDENT ANALYTICS DASHBOARD
 * Production-safe version
 */

$jFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE;

/*
|--------------------------------------------------------------------------
| Safe helper
|--------------------------------------------------------------------------
*/
function safe_json($value): string
{
    global $jFlags;
    return json_encode($value ?? [], $jFlags) ?: '[]';
}

function safe_array_column(array $data, string $column): array
{
    return array_column($data ?? [], $column);
}

/*
|--------------------------------------------------------------------------
| Geographic
|--------------------------------------------------------------------------
*/
$provinceLabels = safe_json(safe_array_column($byProvince ?? [], 'label'));
$provinceCounts = safe_json(array_map('intval', safe_array_column($byProvince ?? [], 'count')));

$districtLabels = safe_json(safe_array_column($byDistrict ?? [], 'label'));
$districtCounts = safe_json(array_map('intval', safe_array_column($byDistrict ?? [], 'count')));

$sectorLabels = safe_json(safe_array_column($byAoSector ?? [], 'label'));
$sectorCounts = safe_json(array_map('intval', safe_array_column($byAoSector ?? [], 'count')));

/*
|--------------------------------------------------------------------------
| Incident Types
|--------------------------------------------------------------------------
*/
$typeMap = [
    'crime' => 'Crime',
    'crowd_control' => 'Crowd Control',
    'public_disorder' => 'Public Disorder',
    'evacuation' => 'Evacuation',
    'intelligence_tip' => 'Intel Tip',
    'other' => 'Other',
];

$typeLabels = safe_json(array_map(
    fn($r) => $typeMap[$r['label']] ?? ucfirst(str_replace('_', ' ', $r['label'])),
    $byType ?? []
));

$typeCounts = safe_json(array_map('intval', safe_array_column($byType ?? [], 'count')));

$typeColors = safe_json([
    '#0d6efd',
    '#fd7e14',
    '#dc3545',
    '#198754',
    '#6f42c1',
    '#6c757d'
]);

/*
|--------------------------------------------------------------------------
| Threat
|--------------------------------------------------------------------------
*/
$threatColorMap = [
    'critical' => '#dc3545',
    'high' => '#fd7e14',
    'moderate' => '#0dcaf0',
    'low' => '#198754'
];

$threatLabels = safe_json(array_map(
    fn($r) => ucfirst($r['label']),
    $byThreat ?? []
));

$threatCounts = safe_json(array_map(
    'intval',
    safe_array_column($byThreat ?? [], 'count')
));

$threatColors = safe_json(array_map(
    fn($r) => $threatColorMap[$r['label']] ?? '#6c757d',
    $byThreat ?? []
));

/*
|--------------------------------------------------------------------------
| Trend
|--------------------------------------------------------------------------
*/
$trendLabels = safe_json(array_map(
    fn($r) => date('d M', strtotime($r['date'])),
    $trend ?? []
));

$trendCounts = safe_json(array_map(
    'intval',
    safe_array_column($trend ?? [], 'count')
));

$daysLabel = match ($daysParam ?? 'all') {
    '7' => 'Last 7 Days',
    '30' => 'Last 30 Days',
    '90' => 'Last 90 Days',
    default => 'All Time'
};

$m = $metrics ?? [
    'total' => 0,
    'open_count' => 0,
    'total_casualties' => 0
];
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    if (typeof Chart === 'undefined') {
        console.error('Chart.js failed to load');
        return;
    }

    Chart.defaults.font.family = "'Segoe UI', sans-serif";
    Chart.defaults.color = '#6c757d';

    /*
    |--------------------------------------------------------------------------
    | Reusable chart creator
    |--------------------------------------------------------------------------
    */
    function createChart(id, config) {
        const el = document.getElementById(id);

        if (!el) return;

        const ctx = el.getContext('2d');

        if (!ctx) return;

        new Chart(ctx, config);
    }

    /*
    |--------------------------------------------------------------------------
    | Trend Chart
    |--------------------------------------------------------------------------
    */
    createChart('chartTrend', {
        type: 'line',
        data: {
            labels: <?= $trendLabels ?>,
            datasets: [{
                label: 'Incidents',
                data: <?= $trendCounts ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,.08)',
                fill: true,
                tension: 0.35
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Incident Type
    |--------------------------------------------------------------------------
    */
    createChart('chartType', {
        type: 'doughnut',
        data: {
            labels: <?= $typeLabels ?>,
            datasets: [{
                data: <?= $typeCounts ?>,
                backgroundColor: <?= $typeColors ?>
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Province
    |--------------------------------------------------------------------------
    */
    createChart('chartProvince', {
        type: 'bar',
        data: {
            labels: <?= $provinceLabels ?>,
            datasets: [{
                data: <?= $provinceCounts ?>,
                backgroundColor: '#0d6efd'
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false
        }
    });

    /*
    |--------------------------------------------------------------------------
    | District
    |--------------------------------------------------------------------------
    */
    createChart('chartDistrict', {
        type: 'bar',
        data: {
            labels: <?= $districtLabels ?>,
            datasets: [{
                data: <?= $districtCounts ?>,
                backgroundColor: '#198754'
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Sector
    |--------------------------------------------------------------------------
    */
    createChart('chartSector', {
        type: 'bar',
        data: {
            labels: <?= $sectorLabels ?>,
            datasets: [{
                data: <?= $sectorCounts ?>,
                backgroundColor: '#6f42c1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Threat
    |--------------------------------------------------------------------------
    */
    createChart('chartThreat', {
        type: 'bar',
        data: {
            labels: <?= $threatLabels ?>,
            datasets: [{
                data: <?= $threatCounts ?>,
                backgroundColor: <?= $threatColors ?>
            }]
        }
    });

});
</script>