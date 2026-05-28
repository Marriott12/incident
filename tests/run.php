<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Services\AuthorizationService;
use App\Services\IncidentValidator;

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$validator = new IncidentValidator();
[$errors, $normalized] = $validator->validate([
    'incident_number' => 'INC-20260417-0001',
    'reported_at' => '2026-04-17T14:30',
    'type' => 'crime',
    'latitude' => '-15.41670000',
    'longitude' => '28.28330000',
    'grid_reference' => '35MNV1234512345',
    'threat_level' => 'high',
    'status' => 'open',
], false);

assertTrue($errors === [], 'Expected valid incident payload to pass validation.');
assertTrue($normalized['reported_at'] === '2026-04-17 14:30:00', 'Expected reported_at normalization to match SQL format.');
assertTrue($normalized['incident_number'] === 'INC-20260417-0001', 'Expected incident number to be preserved.');

[$invalidErrors] = $validator->validate([
    'reported_at' => 'bad-value',
    'type' => 'invalid-type',
    'latitude' => '500',
    'longitude' => '500',
], false);

assertTrue(isset($invalidErrors['reported_at']), 'Expected invalid date/time to be rejected.');
assertTrue(isset($invalidErrors['type']), 'Expected invalid incident type to be rejected.');
assertTrue(isset($invalidErrors['coordinates']), 'Expected invalid coordinates to be rejected.');

$admin = ['id' => 1, 'role' => 'admin'];
$officer = ['id' => 2, 'role' => 'incident_officer'];
$readonly = ['id' => 3, 'role' => 'hq_readonly'];
$incident = ['report_completed_by' => 2, 'reviewed_by' => null];

assertTrue(AuthorizationService::can($admin, 'user.manage'), 'Admin should manage users.');
assertTrue(!AuthorizationService::can($readonly, 'incident.create'), 'HQ readonly should not create incidents.');
assertTrue(AuthorizationService::canViewIncident($officer, $incident), 'Owning incident officer should view own incident.');
assertTrue(!AuthorizationService::canEditIncident($readonly, $incident), 'HQ readonly should not edit incidents.');

echo "All lightweight regression checks passed.\n";