<?php
// Migration runner for 007_workflow_and_incident_fields.sql
// Ensure FILTER_VALIDATE_BOOL exists in older/stripped PHP builds
if (!defined('FILTER_VALIDATE_BOOL')) {
    define('FILTER_VALIDATE_BOOL', 258);
}

// Avoid Composer autoload (system PHP is 7.4); include DB config directly
require __DIR__ . '/../../config/DB.php';

use App\Config\DB;

try {
    $pdo = DB::getPDO();
    $pdo->beginTransaction();

    $dbNameStmt = $pdo->query('SELECT DATABASE()');
    $dbName = $dbNameStmt->fetchColumn();

    // Helper: column exists?
    $columnExists = function(string $table, string $column) use ($pdo, $dbName) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute([$dbName, $table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    };

    // 1) users: add formation, province
    if (!$columnExists('users', 'formation')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN formation VARCHAR(100) NULL AFTER unit");
    }
    if (!$columnExists('users', 'province')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN province VARCHAR(100) NULL AFTER formation");
    }

    // 2) users: extend role enum to include new roles
    $col = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch(PDO::FETCH_ASSOC);
    if ($col && isset($col['Type'])) {
        preg_match("/enum\((.*)\)/", $col['Type'], $m);
        if (isset($m[1])) {
            $current = str_getcsv($m[1], ',', "'");
            $wanted = array_unique(array_merge($current, ['g_staff','formation_commander','cpo','army_hq']));
            $enumList = implode(',', array_map(function($v){ return "'".str_replace("'","''", $v)."'"; }, $wanted));
            $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM($enumList) NOT NULL DEFAULT 'incident_officer'");
        }
    }

    // 3) incidents: add workflow columns
    $incAdds = [
        "formation VARCHAR(100) NULL AFTER report_completed_by",
        "g_staff_comment TEXT NULL AFTER district",
        "g_staff_reviewed_by INT NULL AFTER g_staff_comment",
        "g_staff_reviewed_at DATETIME NULL AFTER g_staff_reviewed_by",
        "formation_comment TEXT NULL AFTER g_staff_reviewed_at",
        "formation_reviewed_by INT NULL AFTER formation_comment",
        "formation_reviewed_at DATETIME NULL AFTER formation_reviewed_by",
        "approved_at DATETIME NULL AFTER formation_reviewed_at",
    ];
    foreach ($incAdds as $add) {
        // extract column name
        if (preg_match('/^([a-z_0-9]+)\s+/i', $add, $mm)) {
            $colName = $mm[1];
            if (!$columnExists('incidents', $colName)) {
                $pdo->exec("ALTER TABLE incidents ADD COLUMN $add");
            }
        }
    }

    // 4) incidents: extend status enum
    $col = $pdo->query("SHOW COLUMNS FROM incidents LIKE 'status'")->fetch(PDO::FETCH_ASSOC);
    if ($col && isset($col['Type'])) {
        preg_match("/enum\((.*)\)/", $col['Type'], $m);
        if (isset($m[1])) {
            $current = str_getcsv($m[1], ',', "'");
            $wanted = array_unique(array_merge($current, ['g_staff_review','formation_review','approved','rejected','under_review']));
            $enumList = implode(',', array_map(function($v){ return "'".str_replace("'","''", $v)."'"; }, $wanted));
            $pdo->exec("ALTER TABLE incidents MODIFY COLUMN status ENUM($enumList) DEFAULT 'open'");
        }
    }

    // 5) Add indexes if missing
    $indexExists = function(string $table, string $indexName) use ($pdo, $dbName) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?');
        $stmt->execute([$dbName, $table, $indexName]);
        return (int)$stmt->fetchColumn() > 0;
    };
    if (!$indexExists('incidents', 'idx_incidents_formation')) {
        $pdo->exec('CREATE INDEX idx_incidents_formation ON incidents (formation)');
    }
    if (!$indexExists('incidents', 'idx_incidents_gstaff_reviewed_at')) {
        $pdo->exec('CREATE INDEX idx_incidents_gstaff_reviewed_at ON incidents (g_staff_reviewed_at)');
    }
    if (!$indexExists('incidents', 'idx_incidents_formation_reviewed_at')) {
        $pdo->exec('CREATE INDEX idx_incidents_formation_reviewed_at ON incidents (formation_reviewed_at)');
    }
    if (!$indexExists('incidents', 'idx_incidents_approved_at')) {
        $pdo->exec('CREATE INDEX idx_incidents_approved_at ON incidents (approved_at)');
    }

    // 6) Foreign keys: add if not exists
    $fkExists = function(string $table, string $fkName) use ($pdo, $dbName){
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?');
        $stmt->execute([$dbName, $table, $fkName]);
        return (int)$stmt->fetchColumn() > 0;
    };
    if (!$fkExists('incidents', 'fk_incidents_gstaff_reviewed_by')) {
        // Add FK but ignore errors if users.id not match or engine differences
        try {
            $pdo->exec('ALTER TABLE incidents ADD CONSTRAINT fk_incidents_gstaff_reviewed_by FOREIGN KEY (g_staff_reviewed_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE');
        } catch (\Throwable $e) {
            // ignore
        }
    }
    if (!$fkExists('incidents', 'fk_incidents_formation_reviewed_by')) {
        try {
            $pdo->exec('ALTER TABLE incidents ADD CONSTRAINT fk_incidents_formation_reviewed_by FOREIGN KEY (formation_reviewed_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE');
        } catch (\Throwable $e) {
            // ignore
        }
    }

    $pdo->commit();
    echo "Migration 007 applied successfully.\n";
} catch (\Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
