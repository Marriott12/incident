<?php
declare(strict_types=1);

namespace App\Models;

use App\Config\DB;
use PDO;

class AuditLog
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = DB::getPDO();
    }

    public function write(
        ?int $userId,
        ?int $incidentId,
        string $action,
        ?string $table = null,
        ?array $old = null,
        ?array $new = null,
        ?string $ip = null,
        ?string $incidentNumber = null,
        ?array $metadata = null
    ): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO audit_log (user_id, incident_id, incident_number, action, table_affected, old_value, new_value, metadata, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $oldJson = $old !== null ? json_encode($old) : null;
        $newJson = $new !== null ? json_encode($new) : null;
        $metadataJson = $metadata !== null ? json_encode($metadata) : null;
        $stmt->execute([$userId, $incidentId, $incidentNumber, $action, $table, $oldJson, $newJson, $metadataJson, $ip]);
        return (int)$this->pdo->lastInsertId();
    }

    public function forIncident(int $incidentId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM audit_log WHERE incident_id = ? ORDER BY created_at DESC LIMIT ?');
        $stmt->bindValue(1, $incidentId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecentActivity(int $limit = 20, ?int $days = null): array
    {
        $dayClause = $days !== null ? 'AND created_at >= DATE_SUB(NOW(), INTERVAL ' . (int)$days . ' DAY)' : '';
        $stmt = $this->pdo->prepare(
            "SELECT a.*, u.name, u.email, i.incident_number
             FROM audit_log a
             LEFT JOIN users u ON a.user_id = u.id
             LEFT JOIN incidents i ON a.incident_id = i.id
             WHERE 1=1 $dayClause
             ORDER BY a.created_at DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
