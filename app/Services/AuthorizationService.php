<?php
declare(strict_types=1);

namespace App\Services;

class AuthorizationService
{
    private const ROLE_PERMISSIONS = [
        'admin' => [
            'dashboard.view',
            'incident.list',
            'incident.view',
            'incident.create',
            'incident.edit',
            'incident.delete',
            'incident.draft',
            'incident.status.update',
            'attachment.download',
            'report.list',
            'report.export',
            'analytics.view',
            'user.manage',
            'api.incidents.view',
            'api.sectors.view',
        ],
        'g_staff' => [
            'dashboard.view',
            'incident.list',
            'incident.view',
            'incident.status.update',
            'report.list',
            'report.export',
            'analytics.view',
            'api.incidents.view',
            'api.sectors.view',
        ],
        'formation_commander' => [
            'dashboard.view',
            'incident.list',
            'incident.view',
            'incident.status.update',
            'report.list',
            'report.export',
            'analytics.view',
            'api.incidents.view',
            'api.sectors.view',
        ],
        'incident_officer' => [
            'dashboard.view',
            'incident.list',
            'incident.view',
            'incident.create',
            'incident.edit',
            'incident.delete',
            'incident.draft',
            'incident.status.update',
            'attachment.download',
            'report.list',
            'report.export',
            'api.incidents.view',
            'api.sectors.view',
        ],
        'hq_readonly' => [
            'dashboard.view',
            'incident.list',
            'incident.view',
            'attachment.download',
            'report.list',
            'report.export',
            'analytics.view',
            'api.incidents.view',
            'api.sectors.view',
        ],
        'cpo' => [
            'dashboard.view',
            'incident.list',
            'incident.view',
            'incident.create',
            'incident.edit',
            'incident.draft',
            'attachment.download',
            'analytics.view',
            'api.incidents.view',
        ],
        'army_hq' => [
            'dashboard.view',
            'incident.list',
            'incident.view',
            'report.list',
            'report.export',
            'analytics.view',
            'api.incidents.view',
            'api.sectors.view',
        ],
    ];

    public static function currentUser(): ?array
    {
        $user = $_SESSION['user'] ?? null;
        return is_array($user) ? $user : null;
    }

    public static function can(?array $user, string $permission): bool
    {
        if ($user === null) {
            return false;
        }

        $role = (string)($user['role'] ?? '');
        $permissions = self::ROLE_PERMISSIONS[$role] ?? [];
        return in_array($permission, $permissions, true);
    }

    public static function canViewIncident(?array $user, array $incident): bool
    {
        if (!self::can($user, 'incident.view')) {
            return false;
        }

        $role = (string)($user['role'] ?? '');
        if (in_array($role, ['admin'], true)) {
            return true;
        }

        if (in_array($role, ['hq_readonly', 'army_hq'], true)) {
            return in_array($incident['status'] ?? 'open', ['approved', 'contained', 'closed'], true);
        }

        if ($role === 'cpo') {
            $userProvince = trim((string)($user['province'] ?? ''));
            $incidentProvince = trim((string)($incident['province'] ?? ''));
            return $userProvince !== '' && $userProvince === $incidentProvince;
        }

        if ($role === 'g_staff') {
            $userProvince = trim((string)($user['province'] ?? ''));
            $userFormation = trim((string)($user['formation'] ?? ''));
            $incidentProvince = trim((string)($incident['province'] ?? ''));
            $incidentFormation = trim((string)($incident['formation'] ?? ''));
            return $userProvince !== '' && $userFormation !== '' && $userProvince === $incidentProvince && $userFormation === $incidentFormation;
        }

        if ($role === 'formation_commander') {
            $userFormation = trim((string)($user['formation'] ?? ''));
            $incidentFormation = trim((string)($incident['formation'] ?? ''));
            return $userFormation !== '' && $userFormation === $incidentFormation && in_array($incident['status'] ?? 'open', ['g_staff_review', 'formation_review', 'approved'], true);
        }

        $userId = (int)($user['id'] ?? 0);
        return $userId > 0 && (
            $userId === (int)($incident['report_completed_by'] ?? 0)
            || $userId === (int)($incident['reviewed_by'] ?? 0)
        );
    }

    public static function canEditIncident(?array $user, array $incident): bool
    {
        if (!self::can($user, 'incident.edit')) {
            return false;
        }

        $role = (string)($user['role'] ?? '');
        // Admins and commanding officers can edit any incident
        if (in_array($role, ['admin'], true)) {
            return true;
        }

        if ($role === 'cpo') {
            $userProvince = trim((string)($user['province'] ?? ''));
            $incidentProvince = trim((string)($incident['province'] ?? ''));
            if ($userProvince === '' || $userProvince !== $incidentProvince) {
                return false;
            }
        }

        return (int)($user['id'] ?? 0) === (int)($incident['report_completed_by'] ?? 0);
    }

    public static function filterVisibleIncidents(array $incidents, ?array $user): array
    {
        return array_values(array_filter(
            $incidents,
            static fn (array $incident): bool => self::canViewIncident($user, $incident)
        ));
    }

    public static function canTransitionIncidentStatus(?array $user, array $incident, string $targetStatus): bool
    {
        if (!self::can($user, 'incident.status.update')) {
            return false;
        }

        $current = (string)($incident['status'] ?? 'open');
        $role = (string)($user['role'] ?? '');
        if (!in_array($targetStatus, ['open', 'g_staff_review', 'formation_review', 'approved', 'rejected', 'contained', 'closed', 'under_review'], true)) {
            return false;
        }

        if (in_array($role, ['admin'], true)) {
            return $current !== $targetStatus;
        }

        if ($role === 'g_staff') {
            $allowed = [
                'open' => ['g_staff_review', 'rejected'],
                'g_staff_review' => ['rejected'],
                'formation_review' => [],
                'approved' => [],
                'rejected' => [],
                'contained' => [],
                'closed' => [],
                'under_review' => [],
            ];
            return in_array($targetStatus, $allowed[$current] ?? [], true);
        }

        if ($role === 'formation_commander') {
            $allowed = [
                'g_staff_review' => ['formation_review', 'rejected'],
                'formation_review' => ['approved', 'rejected'],
                'approved' => [],
                'open' => [],
                'rejected' => [],
                'contained' => [],
                'closed' => [],
                'under_review' => [],
            ];
            return in_array($targetStatus, $allowed[$current] ?? [], true);
        }

        if ($role === 'army_hq') {
            $allowed = [
                'approved' => ['contained', 'closed'],
                'contained' => ['closed'],
                'closed' => [],
                'open' => [],
                'g_staff_review' => [],
                'formation_review' => [],
                'rejected' => [],
                'under_review' => [],
            ];
            return in_array($targetStatus, $allowed[$current] ?? [], true);
        }

        if ($role === 'incident_officer') {
            if ((int)($user['id'] ?? 0) !== (int)($incident['report_completed_by'] ?? 0)) {
                return false;
            }
            $allowed = [
                'open' => ['under_review', 'contained'],
                'under_review' => ['contained'],
                'contained' => ['under_review'],
                'closed' => [],
                'g_staff_review' => [],
                'formation_review' => [],
                'approved' => [],
                'rejected' => [],
            ];
            return in_array($targetStatus, $allowed[$current] ?? [], true);
        }

        return false;
    }

    public static function allowedStatusTransitions(?array $user, array $incident): array
    {
        $targets = ['open', 'g_staff_review', 'formation_review', 'approved', 'rejected', 'contained', 'closed', 'under_review'];
        return array_values(array_filter(
            $targets,
            static fn (string $status): bool => self::canTransitionIncidentStatus($user, $incident, $status)
        ));
    }
}