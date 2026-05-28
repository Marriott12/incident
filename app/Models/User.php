<?php
declare(strict_types=1);

namespace App\Models;

class User
{
    public int $id;
    public string $name;
    public string $email;
    public string $role;

    public static function find(int $id): ?array
    {
        $pdo = \App\Config\DB::getPDO();
        $stmt = $pdo->prepare('SELECT id, `name`, `rank`, email, role, unit, formation, province, created_at, last_login FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function all(int $limit = 50, int $offset = 0, ?string $search = null): array
    {
        $pdo = \App\Config\DB::getPDO();
        if ($search) {
            $q = '%' . $search . '%';
            $stmt = $pdo->prepare('SELECT id, `name`, `rank`, email, role, unit, formation, province, created_at, last_login FROM users WHERE `name` LIKE ? OR email LIKE ? OR role LIKE ? ORDER BY id ASC LIMIT ? OFFSET ?');
            $stmt->bindValue(1, $q);
            $stmt->bindValue(2, $q);
            $stmt->bindValue(3, $q);
            $stmt->bindValue(4, $limit, \PDO::PARAM_INT);
            $stmt->bindValue(5, $offset, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        $stmt = $pdo->prepare('SELECT id, `name`, `rank`, email, role, unit, formation, province, created_at, last_login FROM users ORDER BY id ASC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function count(?string $search = null): int
    {
        $pdo = \App\Config\DB::getPDO();
        if ($search) {
            $q = '%' . $search . '%';
            $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM users WHERE `name` LIKE ? OR email LIKE ? OR role LIKE ?');
            $stmt->execute([$q, $q, $q]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return (int)($row['c'] ?? 0);
        }
        $stmt = $pdo->query('SELECT COUNT(*) AS c FROM users');
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    public static function findByEmail(string $email): ?array
    {
        $pdo = \App\Config\DB::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = \App\Config\DB::getPDO();
        $stmt = $pdo->prepare('INSERT INTO users (`name`, `rank`, email, password_hash, role, unit, formation, province, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        $stmt->execute([
            $data['name'] ?? '',
            $data['rank'] ?? '',
            $data['email'] ?? '',
            $hash,
            $data['role'] ?? 'incident_officer',
            $data['unit'] ?? null,
            $data['formation'] ?? null,
            $data['province'] ?? null
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = \App\Config\DB::getPDO();
        if (!empty($data['password'])) {
            $hash = password_hash($data['password'], PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('UPDATE users SET `name` = ?, `rank` = ?, email = ?, password_hash = ?, role = ?, unit = ?, formation = ?, province = ? WHERE id = ?');
            return $stmt->execute([
                $data['name'] ?? '',
                $data['rank'] ?? '',
                $data['email'] ?? '',
                $hash,
                $data['role'] ?? 'incident_officer',
                $data['unit'] ?? null,
                $data['formation'] ?? null,
                $data['province'] ?? null,
                $id
            ]);
        }
        $stmt = $pdo->prepare('UPDATE users SET `name` = ?, `rank` = ?, email = ?, role = ?, unit = ?, formation = ?, province = ? WHERE id = ?');
        return $stmt->execute([
            $data['name'] ?? '',
            $data['rank'] ?? '',
            $data['email'] ?? '',
            $data['role'] ?? 'incident_officer',
            $data['unit'] ?? null,
            $data['formation'] ?? null,
            $data['province'] ?? null,
            $id
        ]);
    }

    public static function delete(int $id): bool
    {
        $pdo = \App\Config\DB::getPDO();
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
