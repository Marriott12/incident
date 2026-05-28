<?php
declare(strict_types=1);

namespace App\Models;

use App\Config\DB;
use PDO;

class Incident
{
    public const TYPES = [
        'criminal',
        'political',
        'military',
        'health',
        'infrastructure',
        'other'
    ];

    public const RELIABILITY_LEVELS = [
        'most reliable',
        'usually reliable',
        'fairly reliable',
        'not usually reliable',
        'unreliable',
    ];

    public const SHIFTS = [
        'day',
        'night'
    ];

    public const THREAT_LEVELS = [
        'low',
        'moderate',
        'high',
        'critical'
    ];

    /*
     * Brigade Workflow Statuses
     */
    public const STATUSES = [
        'draft',
        'pending_gstaff_review',
        'approved_by_gstaff',
        'rejected_by_gstaff',
        'pending_central_command_review',
        'approved_by_central_command',
        'rejected_by_central_command',
        'published',
        'closed'
    ];

    public const CONFIDENTIALITY_LEVELS = [
        'restricted',
        'confidential',
        'secret'
    ];

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = DB::getPDO();
    }

    public function generateIncidentNumber(): string
    {
        $prefix = 'INC-' . date('Ymd') . '-';

        $stmt = $this->pdo->prepare("
            SELECT incident_number
            FROM incidents
            WHERE incident_number LIKE ?
            ORDER BY id DESC
            LIMIT 1
        ");

        $stmt->execute([$prefix . '%']);

        $last = (string)$stmt->fetchColumn();

        $sequence = 1;

        if (preg_match('/(\d{4})$/', $last, $matches)) {
            $sequence = ((int)$matches[1]) + 1;
        }

        return $prefix . str_pad((string)$sequence, 4, '0', STR_PAD_LEFT);
    }

    public function create(array $data): int
    {
        $payload = $this->preparePayload($data);

        $columns = array_keys($payload);

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        $sql = sprintf(
            'INSERT INTO incidents (%s) VALUES (%s)',
            implode(', ', $columns),
            $placeholders
        );

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute(array_values($payload));

        return (int)$this->pdo->lastInsertId();
    }

    public function saveDraft(array $data): int
    {
        if (!empty($data['id'])) {
            $id = (int)$data['id'];

            $payload = $this->preparePayload($data);

            $set = [];

            foreach (array_keys($payload) as $column) {
                $set[] = "{$column} = ?";
            }

            $sql = "
                UPDATE incidents
                SET " . implode(', ', $set) . ",
                updated_at = NOW()
                WHERE id = ?
            ";

            $stmt = $this->pdo->prepare($sql);

            $values = array_values($payload);
            $values[] = $id;

            $stmt->execute($values);

            return $id;
        }

        return $this->create($data);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM incidents WHERE id = ? LIMIT 1"
        );

        $stmt->execute([$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findByIncidentNumber(string $incidentNumber): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM incidents WHERE incident_number = ? LIMIT 1"
        );

        $stmt->execute([$incidentNumber]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function all(int $limit = 100): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM incidents
             ORDER BY reported_at DESC
             LIMIT ?"
        );

        $stmt->bindValue(1, $limit, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(int $id, array $data): bool
    {
        $set = [];
        $values = [];

        foreach ($data as $column => $value) {
            $set[] = "{$column} = ?";
            $values[] = $value;
        }

        $set[] = "updated_at = NOW()";

        $values[] = $id;

        $sql = "
            UPDATE incidents
            SET " . implode(', ', $set) . "
            WHERE id = ?
        ";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($values);
    }

    public function updateStatus(
        int $id,
        string $status,
        ?int $reviewedBy = null,
        ?string $role = null,
        ?string $comment = null
    ): bool {
        $data = [
            'status' => $status
        ];

        if ($role === 'g_staff') {
            $data['g_staff_comment'] = $comment;
            $data['g_staff_reviewed_by'] = $reviewedBy;
            $data['g_staff_reviewed_at'] = date('Y-m-d H:i:s');
        }

        if ($role === 'central_command') {
            $data['central_command_comment'] = $comment;
            $data['central_command_reviewed_by'] = $reviewedBy;
            $data['central_command_reviewed_at'] = date('Y-m-d H:i:s');
        }

        if ($status === 'published') {
            $data['approved_at'] = date('Y-m-d H:i:s');
        }

        return $this->update($id, $data);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM incidents WHERE id = ? LIMIT 1"
        );

        return $stmt->execute([$id]);
    }

    private function preparePayload(array $data): array
    {
        return [
            'incident_number' => $data['incident_number']
                ?? $this->generateIncidentNumber(),

            'reported_at' => $data['reported_at']
                ?? date('Y-m-d H:i:s'),

            'type' => $data['type'] ?? 'criminal',

            'reliability' => $data['reliability'] ?? 'unknown',

            'reporting_unit' => $data['reporting_unit'] ?? null,

            'commanding_officer' => $data['commanding_officer'] ?? null,

            'shift' => $data['shift'] ?? 'day',

            'narrative' => $data['narrative'] ?? null,

            'province' => $data['province'] ?? null,

            'district' => $data['district'] ?? null,

            'grid_reference' => $data['grid_reference'] ?? null,

            'latitude' => $data['latitude'] ?? null,

            'longitude' => $data['longitude'] ?? null,

            'threat_level' => $data['threat_level'] ?? 'low',

            'status' => $data['status']
                ?? 'pending_gstaff_review',

            'confidentiality_level' =>
                $data['confidentiality_level']
                ?? 'restricted',

            'report_completed_by' =>
                $data['report_completed_by']
                ?? ($_SESSION['user']['id'] ?? null),

            'g_staff_comment' =>
                $data['g_staff_comment'] ?? null,

            'g_staff_reviewed_by' =>
                $data['g_staff_reviewed_by'] ?? null,

            'g_staff_reviewed_at' =>
                $data['g_staff_reviewed_at'] ?? null,

            'central_command_comment' =>
                $data['central_command_comment'] ?? null,

            'central_command_reviewed_by' =>
                $data['central_command_reviewed_by'] ?? null,

            'central_command_reviewed_at' =>
                $data['central_command_reviewed_at'] ?? null,

            'approved_at' =>
                $data['approved_at'] ?? null
        ];
    }
}