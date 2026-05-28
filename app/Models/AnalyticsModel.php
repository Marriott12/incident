<?php
declare(strict_types=1);

namespace App\Models;

use App\Config\DB;
use PDO;

class AnalyticsModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = DB::getPDO();
    }

    /**
     * Build a safe IN clause from an array of incident IDs.
     * Returns [placeholders, safe_ids] or null if no valid IDs.
     */
    private function buildIn(array $ids): ?array
    {
        $safe = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));
        if (empty($safe)) {
            return null;
        }
        return [implode(',', array_fill(0, count($safe), '?')), $safe];
    }

    private function daysClause(?int $days): string
    {
        return $days !== null ? 'AND reported_at >= DATE_SUB(NOW(), INTERVAL ' . (int)$days . ' DAY)' : '';
    }

    /** Overall KPI metrics. */
    public function metrics(array $ids, ?int $days = null): array
    {
        $in = $this->buildIn($ids);
        if ($in === null) {
            return ['total' => 0, 'open_count' => 0, 'total_casualties' => 0, 'weapons_hazmat' => 0];
        }
        [$ph, $safe] = $in;
        $dc = $this->daysClause($days);
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) AS open_count,
                    COALESCE(SUM(casualties_count), 0) AS total_casualties,
                    SUM(CASE WHEN weapons_hazmat_present = 1 THEN 1 ELSE 0 END) AS weapons_hazmat
             FROM incidents WHERE id IN ($ph) $dc"
        );
        $stmt->execute($safe);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'open_count' => 0, 'total_casualties' => 0, 'weapons_hazmat' => 0];
    }

    /** Incident counts grouped by Province. */
    public function byProvince(array $ids, ?int $days = null): array
    {
        $in = $this->buildIn($ids);
        if ($in === null) return [];
        [$ph, $safe] = $in;
        $dc = $this->daysClause($days);
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(NULLIF(TRIM(province), ''), '(Unclassified)') AS label,
                    COUNT(*) AS count,
                    COALESCE(SUM(casualties_count), 0) AS casualties,
                    SUM(CASE WHEN threat_level IN ('high', 'critical') THEN 1 ELSE 0 END) AS high_threat
             FROM incidents WHERE id IN ($ph) $dc
             GROUP BY province ORDER BY count DESC"
        );
        $stmt->execute($safe);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Incident counts grouped by District (top N). */
    public function byDistrict(array $ids, ?int $days = null, int $limit = 12): array
    {
        $in = $this->buildIn($ids);
        if ($in === null) return [];
        [$ph, $safe] = $in;
        $dc = $this->daysClause($days);
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(NULLIF(TRIM(district), ''), '(Unclassified)') AS label,
                    COUNT(*) AS count
             FROM incidents WHERE id IN ($ph) $dc
             GROUP BY district ORDER BY count DESC LIMIT " . (int)$limit
        );
        $stmt->execute($safe);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Incident counts grouped by AO Sector. */
    public function byAoSector(array $ids, ?int $days = null): array
    {
        $in = $this->buildIn($ids);
        if ($in === null) return [];
        [$ph, $safe] = $in;
        $dc = $this->daysClause($days);
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(NULLIF(TRIM(ao_sector), ''), '(Unclassified)') AS label,
                    COUNT(*) AS count
             FROM incidents WHERE id IN ($ph) $dc
             GROUP BY ao_sector ORDER BY count DESC"
        );
        $stmt->execute($safe);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Incident counts grouped by Type. */
    public function byType(array $ids, ?int $days = null): array
    {
        $in = $this->buildIn($ids);
        if ($in === null) return [];
        [$ph, $safe] = $in;
        $dc = $this->daysClause($days);
        $stmt = $this->pdo->prepare(
            "SELECT type AS label, COUNT(*) AS count
             FROM incidents WHERE id IN ($ph) $dc
             GROUP BY type ORDER BY count DESC"
        );
        $stmt->execute($safe);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Incident counts grouped by Threat Level (critical → low order). */
    public function byThreatLevel(array $ids, ?int $days = null): array
    {
        $in = $this->buildIn($ids);
        if ($in === null) return [];
        [$ph, $safe] = $in;
        $dc = $this->daysClause($days);
        $stmt = $this->pdo->prepare(
            "SELECT threat_level AS label, COUNT(*) AS count
             FROM incidents WHERE id IN ($ph) $dc
             GROUP BY threat_level ORDER BY FIELD(threat_level, 'critical', 'high', 'moderate', 'low')"
        );
        $stmt->execute($safe);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Incident counts grouped by Status. */
    public function byStatus(array $ids, ?int $days = null): array
    {
        $in = $this->buildIn($ids);
        if ($in === null) return [];
        [$ph, $safe] = $in;
        $dc = $this->daysClause($days);
        $stmt = $this->pdo->prepare(
            "SELECT status AS label, COUNT(*) AS count
             FROM incidents WHERE id IN ($ph) $dc
             GROUP BY status ORDER BY FIELD(status, 'open', 'under_review', 'contained', 'closed')"
        );
        $stmt->execute($safe);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Incident counts grouped by Shift. */
    public function byShift(array $ids, ?int $days = null): array
    {
        $in = $this->buildIn($ids);
        if ($in === null) return [];
        [$ph, $safe] = $in;
        $dc = $this->daysClause($days);
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(shift, 'day') AS label, COUNT(*) AS count
             FROM incidents WHERE id IN ($ph) $dc
             GROUP BY shift"
        );
        $stmt->execute($safe);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Daily incident count over the trend window.
     * Returns all N days including zeros.
     */
    public function trend(array $ids, ?int $days = null): array
    {
        $in = $this->buildIn($ids);
        if ($in === null) return [];
        [$ph, $safe] = $in;
        $trendDays = $days ?? 30;
        $dc = 'AND reported_at >= DATE_SUB(CURDATE(), INTERVAL ' . ((int)$trendDays - 1) . ' DAY)';
        $stmt = $this->pdo->prepare(
            "SELECT DATE(reported_at) AS date, COUNT(*) AS count
             FROM incidents WHERE id IN ($ph) $dc
             GROUP BY DATE(reported_at) ORDER BY date ASC"
        );
        $stmt->execute($safe);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $byDate = [];
        foreach ($rows as $row) {
            $byDate[$row['date']] = (int)$row['count'];
        }

        $result = [];
        for ($i = (int)$trendDays - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $result[] = ['date' => $date, 'count' => $byDate[$date] ?? 0];
        }
        return $result;
    }

    /** Top N Reporting Units by incident count. */
    public function topUnits(array $ids, ?int $days = null, int $limit = 8): array
    {
        $in = $this->buildIn($ids);
        if ($in === null) return [];
        [$ph, $safe] = $in;
        $dc = $this->daysClause($days);
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(NULLIF(TRIM(reporting_unit), ''), '(Unknown)') AS label,
                    COUNT(*) AS count
             FROM incidents WHERE id IN ($ph) $dc
             GROUP BY reporting_unit ORDER BY count DESC LIMIT " . (int)$limit
        );
        $stmt->execute($safe);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Province → District → AO Sector rollup for the drill-down table. */
    public function geographicSummary(array $ids, ?int $days = null): array
    {
        $in = $this->buildIn($ids);
        if ($in === null) return [];
        [$ph, $safe] = $in;
        $dc = $this->daysClause($days);
        $stmt = $this->pdo->prepare(
            "SELECT
                COALESCE(NULLIF(TRIM(province), ''), '(Unclassified)') AS province,
                COALESCE(NULLIF(TRIM(district), ''), '(Unclassified)') AS district,
                COALESCE(NULLIF(TRIM(ao_sector), ''), '(Unclassified)') AS ao_sector,
                COUNT(*) AS count,
                COALESCE(SUM(casualties_count), 0) AS casualties,
                SUM(CASE WHEN threat_level IN ('high', 'critical') THEN 1 ELSE 0 END) AS high_threat,
                SUM(CASE WHEN weapons_hazmat_present = 1 THEN 1 ELSE 0 END) AS weapons_hazmat,
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) AS open_count
             FROM incidents WHERE id IN ($ph) $dc
             GROUP BY province, district, ao_sector
             ORDER BY province ASC, district ASC, count DESC"
        );
        $stmt->execute($safe);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Personnel exposure totals. */
    public function personnelExposure(array $ids, ?int $days = null): array
    {
        $in = $this->buildIn($ids);
        if ($in === null) return ['military' => 0, 'police' => 0, 'civilians' => 0, 'adversaries' => 0];
        [$ph, $safe] = $in;
        $dc = $this->daysClause($days);
        $stmt = $this->pdo->prepare(
            "SELECT
                COALESCE(SUM(personnel_count_military), 0) AS military,
                COALESCE(SUM(personnel_count_police), 0) AS police,
                COALESCE(SUM(personnel_count_civilians), 0) AS civilians,
                COALESCE(SUM(personnel_count_adversaries), 0) AS adversaries
             FROM incidents WHERE id IN ($ph) $dc"
        );
        $stmt->execute($safe);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['military' => 0, 'police' => 0, 'civilians' => 0, 'adversaries' => 0];
    }

    /** Resolved (closed) incident count. */
    public function resolvedCount(array $ids, ?int $days = null): int
    {
        $in = $this->buildIn($ids);
        if ($in === null) return 0;
        [$ph, $safe] = $in;
        $dc = $this->daysClause($days);
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS count FROM incidents WHERE id IN ($ph) AND status = 'closed' $dc"
        );
        $stmt->execute($safe);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    }

    /** Average response time in hours for closed incidents. */
    public function averageResponseTime(array $ids, ?int $days = null): ?float
    {
        $in = $this->buildIn($ids);
        if ($in === null) return null;
        [$ph, $safe] = $in;
        $dc = $this->daysClause($days);
        $stmt = $this->pdo->prepare(
            "SELECT AVG(TIMESTAMPDIFF(HOUR, reported_at, updated_at)) AS avg_hours
             FROM incidents
             WHERE id IN ($ph) AND status = 'closed' AND updated_at IS NOT NULL $dc"
        );
        $stmt->execute($safe);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return isset($result['avg_hours']) && $result['avg_hours'] !== null
            ? (float)$result['avg_hours']
            : null;
    }
}
