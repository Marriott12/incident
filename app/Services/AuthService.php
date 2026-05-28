<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\DB;
use PDO;

class AuthService
{
    private PDO $pdo;
    private AuditService $audit;

    public function __construct()
    {
        $this->pdo = DB::getPDO();
        $this->audit = new AuditService();
    }

    public function isLocked(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT attempts, locked_until FROM failed_logins WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;
        if (empty($row['locked_until'])) return false;
        return strtotime($row['locked_until']) > time();
    }

    public function recordFailedAttempt(string $email): void
    {
        $stmt = $this->pdo->prepare('SELECT id, attempts FROM failed_logins WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $attempts = (int)$row['attempts'] + 1;
            $lockedUntil = null;
            if ($attempts >= 5) {
                $lockedUntil = date('Y-m-d H:i:s', time() + 15 * 60);
            }
            $u = $this->pdo->prepare('UPDATE failed_logins SET attempts = ?, last_attempt = NOW(), locked_until = ? WHERE id = ?');
            $u->execute([$attempts, $lockedUntil, $row['id']]);
        } else {
            $attempts = 1;
            $lockedUntil = null;
            $i = $this->pdo->prepare('INSERT INTO failed_logins (email, attempts, last_attempt, locked_until) VALUES (?, ?, NOW(), ?)');
            $i->execute([$email, $attempts, $lockedUntil]);
        }

        $this->audit->record('auth.login_failed', 'users', null, null, ['email' => $email, 'attempts' => $attempts], ['email' => $email]);
    }

    public function resetAttempts(string $email): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM failed_logins WHERE email = ?');
        $stmt->execute([$email]);
    }

    public function attemptLogin(string $email, string $password): ?array
    {
        if ($this->isLocked($email)) {
            $this->audit->record('auth.login_blocked', 'users', null, null, ['email' => $email], ['email' => $email]);
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            $this->recordFailedAttempt($email);
            return null;
        }

        if (password_verify($password, $user['password_hash'])) {
            $this->resetAttempts($email);
            // update last_login
            $u = $this->pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
            $u->execute([(int)$user['id']]);
            $this->audit->record('auth.login_success', 'users', null, ['email' => $email], ['id' => (int)$user['id'], 'email' => $email], [], (int)$user['id']);
            return $user;
        }

        $this->recordFailedAttempt($email);
        return null;
    }

    public function createSession(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'unit' => $user['unit'] ?? null,
        ];
        $_SESSION['last_activity_at'] = time();
    }

    public function rehashPassword(int $userId, string $password): void
    {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        if ($newHash === false) {
            return;
        }

        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$newHash, $userId]);
        $this->audit->record('auth.password_rehash', 'users', $userId);
    }

    public function createRememberToken(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $expires = time() + 30 * 24 * 60 * 60;

        setcookie(
            'remember_token',
            $token,
            $expires,
            '/',
            '',
            isset($_SERVER['HTTPS']),
            true
        );

        // Persistent remember-me storage is not implemented yet;
        // the cookie is created for future support.
        $this->audit->record('auth.remember_token_created', 'users', $userId);
    }
}
