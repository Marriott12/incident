<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Incident;

class AuditService
{
    private AuditLog $auditLog;
    private Incident $incidentModel;

    public function __construct()
    {
        $this->auditLog = new AuditLog();
        $this->incidentModel = new Incident();
    }

    public function record(
        string $action,
        ?string $tableAffected = null,
        ?int $incidentId = null,
        ?array $old = null,
        ?array $new = null,
        array $metadata = [],
        ?int $userId = null
    ): void {
        try {
            $actorId = $userId ?? (isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null);
            $incidentNumber = $metadata['incident_number'] ?? null;
            if ($incidentNumber === null) {
                $incidentNumber = $this->extractIncidentNumber($incidentId, $old, $new);
            }

            $metadata['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $metadata['request_uri'] = $_SERVER['REQUEST_URI'] ?? null;
            $metadata['request_method'] = $_SERVER['REQUEST_METHOD'] ?? null;

            $this->auditLog->write(
                $actorId,
                $incidentId,
                $action,
                $tableAffected,
                $old,
                $new,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $incidentNumber,
                $metadata
            );
        } catch (\Throwable $ex) {
            // Audit failures must not break the user workflow.
        }
    }

    private function extractIncidentNumber(?int $incidentId, $old, $new): ?string
    {
        foreach ([$new, $old] as $payload) {
            if (is_array($payload) && !empty($payload['incident_number'])) {
                return (string)$payload['incident_number'];
            }
        }

        if ($incidentId === null) {
            return null;
        }

        $incident = $this->incidentModel->find($incidentId);
        return $incident['incident_number'] ?? null;
    }
}