<?php
declare(strict_types=1);

namespace App\Models;

use App\Config\DB;
use PDO;

class Attachment
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = DB::getPDO();
    }

    public function create(int $incidentId, string $fileName, string $filePath, string $fileType, int $uploadedBy, ?string $mimeType = null, ?int $fileSize = null): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO incident_attachments (incident_id, file_name, file_path, file_type, mime_type, file_size, uploaded_by, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$incidentId, $fileName, $filePath, $fileType, $mimeType, $fileSize, $uploadedBy]);
        return (int)$this->pdo->lastInsertId();
    }

    public function allForIncident(int $incidentId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM incident_attachments WHERE incident_id = ? ORDER BY uploaded_at DESC');
        $stmt->execute([$incidentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findForIncident(int $attachmentId, int $incidentId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM incident_attachments WHERE id = ? AND incident_id = ? LIMIT 1');
        $stmt->execute([$attachmentId, $incidentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
