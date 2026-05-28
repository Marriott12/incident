<?php
declare(strict_types=1);

namespace App\Controllers;

abstract class BaseController
{
    protected function url(string $path = '/'): string
    {
        $base = defined('APP_BASE_PATH') ? APP_BASE_PATH : '';
        if ($path === '' || $path === '/') {
            return $base !== '' ? $base . '/' : '/';
        }
        return ($base !== '' ? $base : '') . '/' . ltrim($path, '/');
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $this->url($path));
        exit;
    }

    protected function render(string $view, array $params = []): void
    {
        $params['base_path'] = defined('APP_BASE_PATH') ? APP_BASE_PATH : '';
        extract($params, EXTR_SKIP);
        $viewFile = __DIR__ . '/../Views/' . $view;
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View not found: {$viewFile}");
        }
        require __DIR__ . '/../Views/layout/header.php';
        require $viewFile;
        require __DIR__ . '/../Views/layout/footer.php';
    }
}
