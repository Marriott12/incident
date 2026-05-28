<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Middleware\AuthMiddleware;
use App\Services\AuditService;
use App\Services\CsrfService;

class AdminController extends BaseController
{
    public function usersIndex(): void
    {
        AuthMiddleware::requirePermission('user.manage');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 15;
        $search = isset($_GET['q']) ? trim((string)$_GET['q']) : null;
        $offset = ($page - 1) * $perPage;
        $total = User::count($search);
        $users = User::all($perPage, $offset, $search);
        $this->render('admin/users/index.php', ['users' => $users, 'page' => $page, 'perPage' => $perPage, 'total' => $total, 'q' => $search]);
    }

    public function usersCreate(): void
    {
        AuthMiddleware::requirePermission('user.manage');
        $token = CsrfService::generateToken();
        $this->render('admin/users/create.php', ['csrf_token' => $token]);
    }

    public function usersStore(): void
    {
        AuthMiddleware::requirePermission('user.manage');
        $token = $_POST['csrf_token'] ?? null;
        if (!CsrfService::validateToken($token)) {
            http_response_code(400);
            echo 'Invalid CSRF token';
            return;
        }

        $data = $_POST;
        $errors = [];
        if (empty($data['name'])) $errors[] = 'Name required';
        if (empty($data['email'])) $errors[] = 'Email required';
        if (empty($data['password'])) $errors[] = 'Password required';
        // password complexity
        if (!empty($data['password'])) {
            $pw = $data['password'];
            if (strlen($pw) < 12 || !preg_match('/[A-Z]/', $pw) || !preg_match('/[a-z]/', $pw) || !preg_match('/[0-9]/', $pw) || !preg_match('/[^a-zA-Z0-9]/', $pw)) {
                $errors[] = 'Password must be at least 12 characters and include upper, lower, number and special char.';
            }
        }

        if ($errors) {
            $token = CsrfService::generateToken();
            $this->render('admin/users/create.php', ['errors' => $errors, 'old' => $data, 'csrf_token' => $token]);
            return;
        }

        $id = User::create($data);
        (new AuditService())->record('admin.user_create', 'users', null, null, ['id' => $id, 'email' => $data['email'] ?? null, 'role' => $data['role'] ?? null]);
        $this->redirect('/admin/users');
    }

    public function usersEdit(int $id): void
    {
        AuthMiddleware::requirePermission('user.manage');
        $user = User::find($id);
        if (!$user) {
            http_response_code(404);
            echo 'User not found';
            return;
        }
        $token = CsrfService::generateToken();
        $this->render('admin/users/edit.php', ['user' => $user, 'csrf_token' => $token]);
    }

    public function usersUpdate(int $id): void
    {
        AuthMiddleware::requirePermission('user.manage');
        $token = $_POST['csrf_token'] ?? null;
        if (!CsrfService::validateToken($token)) {
            http_response_code(400);
            echo 'Invalid CSRF token';
            return;
        }
        $data = $_POST;
        $errors = [];
        if (!empty($data['password'])) {
            $pw = $data['password'];
            if (strlen($pw) < 12 || !preg_match('/[A-Z]/', $pw) || !preg_match('/[a-z]/', $pw) || !preg_match('/[0-9]/', $pw) || !preg_match('/[^a-zA-Z0-9]/', $pw)) {
                $errors[] = 'Password must be at least 12 characters and include upper, lower, number and special char.';
            }
        }
        if ($errors) {
            $user = User::find($id);
            $token = CsrfService::generateToken();
            $this->render('admin/users/edit.php', ['user' => $user, 'errors' => $errors, 'csrf_token' => $token]);
            return;
        }

        $before = User::find($id);
        User::update($id, $data);
        (new AuditService())->record('admin.user_update', 'users', null, $before, ['id' => $id, 'email' => $data['email'] ?? null, 'role' => $data['role'] ?? null]);
        $this->redirect('/admin/users');
    }

    public function usersDelete(int $id): void
    {
        AuthMiddleware::requirePermission('user.manage');
        if (!CsrfService::validateToken($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'Invalid CSRF token';
            return;
        }
        $before = User::find($id);
        User::delete($id);
        (new AuditService())->record('admin.user_delete', 'users', null, $before, ['id' => $id]);
        $this->redirect('/admin/users');
    }
}
