<?php
declare(strict_types=1);

$base = getenv('APP_BASE_URL') ?: 'http://127.0.0.1:8080';

function request(string $method, string $url, array $headers = [], ?string $body = null): array
{
    $headerLines = [];
    foreach ($headers as $k => $v) {
        $headerLines[] = $k . ': ' . $v;
    }

    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headerLines),
            'content' => $body,
            'ignore_errors' => true,
            'timeout' => 20,
        ],
    ]);

    $content = file_get_contents($url, false, $context);
    $responseHeaders = $http_response_header ?? [];
    $statusCode = 0;
    if (!empty($responseHeaders) && preg_match('#HTTP/\S+\s+(\d{3})#', $responseHeaders[0], $m)) {
        $statusCode = (int)$m[1];
    }

    return ['status' => $statusCode, 'headers' => $responseHeaders, 'body' => $content ?: ''];
}

function extractCookie(array $headers): string
{
    foreach ($headers as $line) {
        if (stripos($line, 'Set-Cookie:') === 0) {
            $parts = explode(';', trim(substr($line, strlen('Set-Cookie:'))));
            return trim($parts[0]);
        }
    }
    return '';
}

function extractToken(string $html): string
{
    if (preg_match('/name="csrf_token" value="([^"]+)"/', $html, $m)) {
        return $m[1];
    }
    return '';
}

function assertStatus(int $actual, array $expected, string $label): void
{
    if (!in_array($actual, $expected, true)) {
        throw new RuntimeException($label . ' expected [' . implode(',', $expected) . '] got ' . $actual);
    }
}

$loginPage = request('GET', $base . '/auth/login');
assertStatus($loginPage['status'], [200], 'GET /auth/login');
$cookie = extractCookie($loginPage['headers']);
$token = extractToken($loginPage['body']);
if ($cookie === '' || $token === '') {
    throw new RuntimeException('Failed to extract session cookie or CSRF token from login page.');
}

$loginBody = http_build_query([
    'csrf_token' => $token,
    'email' => 'admin@example.local',
    'password' => 'ChangeMe123!',
]);
$login = request('POST', $base . '/auth/login', [
    'Content-Type' => 'application/x-www-form-urlencoded',
    'Cookie' => $cookie,
], $loginBody);
assertStatus($login['status'], [302], 'POST /auth/login');
$newCookie = extractCookie($login['headers']);
if ($newCookie !== '') {
    $cookie = $newCookie;
}

$dashboard = request('GET', $base . '/dashboard', ['Cookie' => $cookie]);
assertStatus($dashboard['status'], [200], 'GET /dashboard');

$reports = request('GET', $base . '/reports', ['Cookie' => $cookie]);
assertStatus($reports['status'], [200], 'GET /reports');

$createPage = request('GET', $base . '/incidents/create', ['Cookie' => $cookie]);
assertStatus($createPage['status'], [200], 'GET /incidents/create');
$createToken = extractToken($createPage['body']);
if ($createToken === '') {
    throw new RuntimeException('Failed to extract CSRF token from incident create page.');
}

$incidentNumber = '';
if (preg_match('/name="incident_number" id="incident_number" readonly value="([^"]*)"/', $createPage['body'], $m)) {
    $incidentNumber = $m[1];
}
if ($incidentNumber === '') {
    throw new RuntimeException('Incident number not visible on create page.');
}

$createBody = http_build_query([
    'csrf_token' => $createToken,
    'incident_number' => $incidentNumber,
    'reported_at' => date('Y-m-d\TH:i'),
    'type' => 'crime',
    'reporting_unit' => 'Integration Test Unit',
    'grid_reference' => '35MNV1234512345',
    'latitude' => '-15.4167',
    'longitude' => '28.2833',
    'narrative' => 'Integration smoke incident',
    'threat_level' => 'high',
    'status' => 'open',
    'confidentiality_level' => 'restricted',
]);

$create = request('POST', $base . '/incidents/store', [
    'Content-Type' => 'application/x-www-form-urlencoded',
    'Cookie' => $cookie,
], $createBody);
assertStatus($create['status'], [302], 'POST /incidents/store');

$location = '';
foreach ($create['headers'] as $line) {
    if (stripos($line, 'Location:') === 0) {
        $location = trim(substr($line, strlen('Location:')));
        break;
    }
}
if ($location === '' || strpos($location, '/incidents/') === false) {
    throw new RuntimeException('Incident create redirect missing expected incident path.');
}

$detail = request('GET', $base . $location, ['Cookie' => $cookie]);
assertStatus($detail['status'], [200], 'GET incident detail');
if (strpos($detail['body'], 'incident-detail-map') === false) {
    throw new RuntimeException('Incident detail map is not rendered.');
}
if (strpos($detail['body'], htmlspecialchars($incidentNumber, ENT_QUOTES, 'UTF-8')) === false) {
    throw new RuntimeException('Incident number not displayed on detail page.');
}

echo "Integration smoke test passed.\n";
