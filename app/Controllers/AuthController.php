<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\AuditService;
use App\Services\CsrfService;
use Throwable;

class AuthController extends BaseController
{
    /**
     * Display Login Page
     */
    public function login(): void
    {
        try {

            // Redirect already authenticated users to their role-specific landing page
            if (!empty($_SESSION['user'])) {
                $role = $_SESSION['user']['role'] ?? 'incident_officer';
                $this->redirect($this->getRedirectPathForRole($role));
                return;
            }

            $token = CsrfService::generateToken();

            $this->render('auth/login.php', [
                'csrf_token' => $token,
                'page_title' => 'Login',
            ]);

        } catch (Throwable $e) {

            error_log($e->getMessage());

            http_response_code(500);

            echo 'Unable to load login page.';
        }
    }

    /**
     * Process Login Request
     */
    public function loginPost(): void
    {
        try {

            /*
            |--------------------------------------------------------------------------
            | Validate Request Method
            |--------------------------------------------------------------------------
            */
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

                http_response_code(405);

                echo 'Method Not Allowed';

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | CSRF Validation
            |--------------------------------------------------------------------------
            */
            $csrfToken = $_POST['csrf_token'] ?? '';

            if (!CsrfService::validateToken($csrfToken)) {

                http_response_code(403);

                $_SESSION['error'] = 'Invalid security token. Please try again.';

                $this->redirect('/login');

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Sanitize Inputs
            |--------------------------------------------------------------------------
            */
            $email = trim((string)($_POST['email'] ?? ''));
            $password = trim((string)($_POST['password'] ?? ''));
            $rememberMe = isset($_POST['remember_me']);

            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */
            if (empty($email) || empty($password)) {

                $_SESSION['error'] = 'Email and password are required.';

                $this->redirect('/login');

                return;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

                $_SESSION['error'] = 'Invalid email address.';

                $this->redirect('/login');

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Authentication Service
            |--------------------------------------------------------------------------
            */
            $authService = new AuthService();

            /*
            |--------------------------------------------------------------------------
            | Check Account Lock
            |--------------------------------------------------------------------------
            */
            if ($authService->isLocked($email)) {

                $_SESSION['error'] =
                    'Your account has been temporarily locked due to multiple failed login attempts.';

                $this->redirect('/login');

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Attempt Login
            |--------------------------------------------------------------------------
            */
            $user = $authService->attemptLogin($email, $password);

            if (!$user) {

                $authService->recordFailedAttempt($email);

                $_SESSION['error'] = 'Invalid login credentials.';

                $this->redirect('/login');

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Password Rehashing
            |--------------------------------------------------------------------------
            */
            if (
                isset($user['password']) &&
                password_needs_rehash(
                    $user['password'],
                    PASSWORD_DEFAULT
                )
            ) {
                $authService->rehashPassword(
                    (int)$user['id'],
                    $password
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Regenerate Session ID
            |--------------------------------------------------------------------------
            */
            session_regenerate_id(true);

            /*
            |--------------------------------------------------------------------------
            | Create Session
            |--------------------------------------------------------------------------
            */
            $authService->createSession($user);

            /*
            |--------------------------------------------------------------------------
            | Remember Me
            |--------------------------------------------------------------------------
            */
            if ($rememberMe) {
                $authService->createRememberToken((int)$user['id']);
            }

            /*
            |--------------------------------------------------------------------------
            | Audit Log
            |--------------------------------------------------------------------------
            */
            (new AuditService())->record(
                'auth.login.success',
                'users',
                (int)$user['id']
            );

            /*
            |--------------------------------------------------------------------------
            | Success Message
            |--------------------------------------------------------------------------
            */
            $_SESSION['success'] =
                'Welcome back, ' . htmlspecialchars($user['name']);

            /*
            |--------------------------------------------------------------------------
            | Redirect Based On Role
            |--------------------------------------------------------------------------
            */
            $redirectPath = $this->getRedirectPathForRole($user['role'] ?? 'incident_officer');
            $this->redirect($redirectPath);

        } catch (Throwable $e) {

            error_log($e->getMessage());

            (new AuditService())->record(
                'auth.login.error',
                'users'
            );

            http_response_code(500);

            $_SESSION['error'] =
                'An unexpected error occurred during login.';

            $this->redirect('/login');
        }
    }

    /**
     * Determine redirect destination after successful login, by role.
     */
    private function getRedirectPathForRole(string $role): string
    {
        if ($role === 'admin') {
            return '/admin/users';
        }

        if ($role === 'g_staff') {
            return '/dashboard/g-staff';
        }

        if ($role === 'formation_commander') {
            return '/dashboard/formation-commander';
        }

        if ($role === 'hq_readonly') {
            return '/dashboard/hq-readonly';
        }

        if ($role === 'incident_officer') {
            return '/dashboard/incident-officer';
        }

        if ($role === 'cpo') {
            return '/dashboard/cpo';
        }

        if ($role === 'army_hq') {
            return '/dashboard/army-hq';
        }

        return '/dashboard';
    }

    /**
     * Logout User
     */
    public function logout(): void
    {
        try {

            /*
            |--------------------------------------------------------------------------
            | Validate Request Method
            |--------------------------------------------------------------------------
            */
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

                http_response_code(405);

                echo 'Method Not Allowed';

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Validate CSRF Token
            |--------------------------------------------------------------------------
            */
            $csrfToken = $_POST['csrf_token'] ?? '';

            if (!CsrfService::validateToken($csrfToken)) {

                http_response_code(403);

                echo 'Invalid CSRF token';

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Audit Logout
            |--------------------------------------------------------------------------
            */
            if (!empty($_SESSION['user']['id'])) {

                (new AuditService())->record(
                    'auth.logout',
                    'users',
                    (int)$_SESSION['user']['id']
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Remove Remember Me Cookie
            |--------------------------------------------------------------------------
            */
            if (isset($_COOKIE['remember_token'])) {

                setcookie(
                    'remember_token',
                    '',
                    time() - 3600,
                    '/',
                    '',
                    isset($_SERVER['HTTPS']),
                    true
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Destroy Session
            |--------------------------------------------------------------------------
            */
            $_SESSION = [];

            if (ini_get('session.use_cookies')) {

                $params = session_get_cookie_params();

                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            session_destroy();

            /*
            |--------------------------------------------------------------------------
            | Start Fresh Session
            |--------------------------------------------------------------------------
            */
            session_start();

            session_regenerate_id(true);

            $_SESSION['success'] =
                'You have been logged out successfully.';

            /*
            |--------------------------------------------------------------------------
            | Redirect Login
            |--------------------------------------------------------------------------
            */
            $this->redirect('/login');

        } catch (Throwable $e) {

            error_log($e->getMessage());

            http_response_code(500);

            echo 'Unable to process logout request.';
        }
    }
}