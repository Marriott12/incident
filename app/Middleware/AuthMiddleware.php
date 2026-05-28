<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Services\AuthorizationService;

class AuthMiddleware
{
    public static function requireRole(array $roles): void
    {
        if (empty($_SESSION['user'])) {
            $base = defined('APP_BASE_PATH') ? APP_BASE_PATH : '';
            header('Location: ' . ($base !== '' ? $base : '') . '/login');
            exit;
        }
        $userRole = $_SESSION['user']['role'] ?? null;
        if (!in_array($userRole, $roles, true)) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    }

    public static function requirePermission(string $permission): void
    {
        if (empty($_SESSION['user'])) {
            $base = defined('APP_BASE_PATH') ? APP_BASE_PATH : '';
            header('Location: ' . ($base !== '' ? $base : '') . '/login');
            exit;
        }

        if (!AuthorizationService::can(AuthorizationService::currentUser(), $permission)) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    }
}
