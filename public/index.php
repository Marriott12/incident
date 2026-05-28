<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Incident Management System - Secure Front Controller
|--------------------------------------------------------------------------
| Fully corrected and production-ready bootstrap/router.
| Features:
| - Secure sessions
| - Error handling
| - HTTPS detection
| - CSP security
| - Route normalization
| - Exception logging
| - Safe routing
| - Subfolder support
| - Built-in server compatibility
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Built-in PHP Server Static File Handling
|--------------------------------------------------------------------------
*/
if (PHP_SAPI === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

    $file = realpath(__DIR__ . $requestPath);
    $root = realpath(__DIR__);

    if (
        $file !== false &&
        $root !== false &&
        str_starts_with($file, $root) &&
        is_file($file)
    ) {
        return false;
    }
}

/*
|--------------------------------------------------------------------------
| Application Paths
|--------------------------------------------------------------------------
*/
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('STORAGE_PATH', BASE_PATH . '/storage');

/*
|--------------------------------------------------------------------------
| Autoload Composer
|--------------------------------------------------------------------------
*/
require BASE_PATH . '/vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Import Controllers
|--------------------------------------------------------------------------
*/
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\IncidentController;
use App\Controllers\AdminController;
use App\Controllers\ApiController;
use App\Controllers\ReportController;
use App\Controllers\AnalyticsController;

/*
|--------------------------------------------------------------------------
| Environment Configuration
|--------------------------------------------------------------------------
*/
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Africa/Lusaka');

$appDebug = filter_var(
    getenv('APP_DEBUG') ?: false,
    FILTER_VALIDATE_BOOL
);

/*
|--------------------------------------------------------------------------
| Error Reporting
|--------------------------------------------------------------------------
*/
if ($appDebug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}

/*
|--------------------------------------------------------------------------
| HTTPS Detection
|--------------------------------------------------------------------------
*/
$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ($_SERVER['SERVER_PORT'] ?? null) == 443
);

/*
|--------------------------------------------------------------------------
| Secure Session Configuration
|--------------------------------------------------------------------------
*/
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_secure', $isHttps ? '1' : '0');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Session Timeout Handling
|--------------------------------------------------------------------------
*/
$idleTimeout = (int)(getenv('APP_SESSION_IDLE_TIMEOUT') ?: 1800);

if (
    isset($_SESSION['last_activity']) &&
    (time() - (int)$_SESSION['last_activity']) > $idleTimeout
) {
    session_unset();
    session_destroy();

    session_start();
    session_regenerate_id(true);
}

$_SESSION['last_activity'] = time();

/*
|--------------------------------------------------------------------------
| Security Headers
|--------------------------------------------------------------------------
*/
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-XSS-Protection: 1; mode=block');

header(
    "Permissions-Policy: geolocation=(), microphone=(), camera=()"
);

header(
    "Content-Security-Policy:
    default-src 'self';
    base-uri 'self';
    frame-ancestors 'none';
    form-action 'self';
    img-src 'self' data: https:;
    script-src 'self' 'unsafe-inline'
        https://code.jquery.com
        https://cdn.jsdelivr.net
        https://cdnjs.cloudflare.com
        https://unpkg.com;
    style-src 'self' 'unsafe-inline'
        https://cdn.jsdelivr.net
        https://cdnjs.cloudflare.com
        https://unpkg.com;
    font-src 'self'
        https://cdn.jsdelivr.net
        https://cdnjs.cloudflare.com;
    connect-src 'self';"
);

/*
|--------------------------------------------------------------------------
| Error Page Renderer
|--------------------------------------------------------------------------
*/
function renderErrorPage(
    int $status,
    string $title,
    string $message,
    bool $debug = false,
    ?string $details = null
): void {
    http_response_code($status);

    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    $safeDetails = '';

    if ($debug && $details !== null) {
        $safeDetails = sprintf(
            '<pre class="bg-light p-3 rounded border mt-3">%s</pre>',
            htmlspecialchars($details, ENT_QUOTES, 'UTF-8')
        );
    }

    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$safeTitle}</title>

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

</head>

<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="card shadow border-0">
                <div class="card-body p-5">

                    <h1 class="h3 mb-3 text-danger">
                        {$safeTitle}
                    </h1>

                    <p class="text-muted">
                        {$safeMessage}
                    </p>

                    {$safeDetails}

                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
HTML;

    exit;
}

/*
|--------------------------------------------------------------------------
| Application Error Logging
|--------------------------------------------------------------------------
*/
function logApplicationError(Throwable $e): void
{
    $logDir = STORAGE_PATH . '/logs';

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/app.log';

    $message = sprintf(
        "[%s] %s %s\nMessage: %s\nFile: %s:%d\nTrace:\n%s\n\n",
        date('Y-m-d H:i:s'),
        $_SERVER['REQUEST_METHOD'] ?? 'CLI',
        $_SERVER['REQUEST_URI'] ?? '-',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );

    file_put_contents($logFile, $message, FILE_APPEND);
}

/*
|--------------------------------------------------------------------------
| Global Exception Handler
|--------------------------------------------------------------------------
*/
set_exception_handler(function (Throwable $e) use ($appDebug) {

    logApplicationError($e);

    renderErrorPage(
        500,
        'Application Error',
        'An unexpected error occurred.',
        $appDebug,
        $e->getMessage()
    );
});

/*
|--------------------------------------------------------------------------
| Request Parsing
|--------------------------------------------------------------------------
*/
$uri = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
) ?: '/';

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

/*
|--------------------------------------------------------------------------
| Base Path Support
|--------------------------------------------------------------------------
*/
$scriptDir = str_replace(
    '\\',
    '/',
    dirname($_SERVER['SCRIPT_NAME'] ?? '')
);

$basePath = rtrim($scriptDir, '/');

if ($basePath === '/' || $basePath === '.') {
    $basePath = '';
}

if ($basePath !== '' && str_ends_with($basePath, '/public')) {
    $basePath = substr($basePath, 0, -7);
}

$normalizedUri = strtolower($uri);
$normalizedBasePath = strtolower($basePath);

define('APP_BASE_PATH', $basePath);

if (
    $basePath !== '' &&
    str_starts_with($normalizedUri, $normalizedBasePath)
) {
    $uri = substr($uri, strlen($basePath));
}

$uri = '/' . trim($uri, '/');

if ($uri === '//') {
    $uri = '/';
}

/*
|--------------------------------------------------------------------------
| Ignore favicon requests
|--------------------------------------------------------------------------
*/
if ($uri === '/favicon.ico') {
    http_response_code(204);
    exit;
}

/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
*/
try {

    /*
    |--------------------------------------------------------------------------
    | Home Routes
    |--------------------------------------------------------------------------
    */
    if ($uri === '/' || $uri === '/dashboard') {
        (new HomeController())->index();
        exit;
    }

    if ($uri === '/dashboard/commanding-officer') {
        (new HomeController())->landing('commanding_officer');
        exit;
    }

    if ($uri === '/dashboard/hq-readonly') {
        (new HomeController())->landing('hq_readonly');
        exit;
    }

    if ($uri === '/dashboard/incident-officer') {
        (new HomeController())->landing('incident_officer');
        exit;
    }

    if ($uri === '/dashboard/cpo') {
        (new HomeController())->landing('cpo');
        exit;
    }

    if ($uri === '/dashboard/g-staff') {
        (new HomeController())->landing('g_staff');
        exit;
    }

    if ($uri === '/dashboard/formation-commander') {
        (new HomeController())->landing('formation_commander');
        exit;
    }

    if ($uri === '/dashboard/army-hq') {
        (new HomeController())->landing('army_hq');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Authentication Routes
    |--------------------------------------------------------------------------
    */
    if ($uri === '/login' || $uri === '/auth/login') {

        $controller = new AuthController();

        if ($method === 'POST') {
            $controller->loginPost();
        } else {
            $controller->login();
        }

        exit;
    }

    if ($uri === '/auth/logout' && $method === 'POST') {
        (new AuthController())->logout();
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Incident Routes
    |--------------------------------------------------------------------------
    */
    if ($uri === '/incidents') {
        (new IncidentController())->index();
        exit;
    }

    if ($uri === '/incidents/create') {
        (new IncidentController())->create();
        exit;
    }

    if ($uri === '/incidents/store' && $method === 'POST') {
        (new IncidentController())->store();
        exit;
    }

    if (
        preg_match('#^/incidents/(\d+)$#', $uri, $matches)
    ) {
        (new IncidentController())->show((int)$matches[1]);
        exit;
    }

    if (
        preg_match('#^/incidents/(\d+)/status$#', $uri, $matches)
        && $method === 'POST'
    ) {
        (new IncidentController())->updateStatus((int)$matches[1]);
        exit;
    }

    if (
        preg_match('#^/incidents/(\d+)/delete$#', $uri, $matches)
        && $method === 'POST'
    ) {
        (new IncidentController())->delete((int)$matches[1]);
        exit;
    }

    if (
        preg_match(
            '#^/incidents/(\d+)/attachments/(\d+)/download$#',
            $uri,
            $matches
        )
        && $method === 'GET'
    ) {
        (new IncidentController())->downloadAttachment(
            (int)$matches[1],
            (int)$matches[2]
        );

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Draft Save API
    |--------------------------------------------------------------------------
    */
    if (
        $uri === '/api/incidents/save-draft'
        && $method === 'POST'
    ) {
        (new IncidentController())->saveDraft();
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    */
    if ($uri === '/api/incidents/geojson') {
        (new ApiController())->incidentsGeojson();
        exit;
    }

    if ($uri === '/api/sectors') {
        (new ApiController())->sectors();
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    */
    if ($uri === '/admin/users') {
        (new AdminController())->usersIndex();
        exit;
    }

    if ($uri === '/admin/users/create') {
        (new AdminController())->usersCreate();
        exit;
    }

    if (
        $uri === '/admin/users/store'
        && $method === 'POST'
    ) {
        (new AdminController())->usersStore();
        exit;
    }

    if (
        preg_match('#^/admin/users/(\d+)/edit$#', $uri, $matches)
    ) {
        (new AdminController())->usersEdit((int)$matches[1]);
        exit;
    }

    if (
        preg_match('#^/admin/users/(\d+)/update$#', $uri, $matches)
        && $method === 'POST'
    ) {
        (new AdminController())->usersUpdate((int)$matches[1]);
        exit;
    }

    if (
        preg_match('#^/admin/users/(\d+)/delete$#', $uri, $matches)
        && $method === 'POST'
    ) {
        (new AdminController())->usersDelete((int)$matches[1]);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */
    if ($uri === '/reports') {
        (new ReportController())->index();
        exit;
    }

    if (
        preg_match('#^/reports/export/(\d+)/pdf$#', $uri, $matches)
        && $method === 'GET'
    ) {
        (new ReportController())->exportPdf((int)$matches[1]);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Analytics
    |--------------------------------------------------------------------------
    */
    if ($uri === '/analytics') {
        (new AnalyticsController())->index();
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | 404 Not Found
    |--------------------------------------------------------------------------
    */
    renderErrorPage(
        404,
        '404 Not Found',
        'The requested page could not be found.'
    );

} catch (Throwable $e) {

    logApplicationError($e);

    renderErrorPage(
        500,
        'Server Error',
        'An unexpected server error occurred.',
        $appDebug,
        $e->getMessage()
    );
}