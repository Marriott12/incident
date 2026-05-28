<?php
declare(strict_types=1);

$base = 'http://127.0.0.1:8080';

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
    return ['headers' => $responseHeaders, 'body' => $content ?: ''];
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
    return preg_match('/name="csrf_token" value="([^"]+)"/', $html, $m) ? $m[1] : '';
}

$loginPage = request('GET', $base . '/auth/login');
$cookie = extractCookie($loginPage['headers']);
$token = extractToken($loginPage['body']);

$login = request('POST', $base . '/auth/login', [
    'Content-Type' => 'application/x-www-form-urlencoded',
    'Cookie' => $cookie,
], http_build_query([
    'csrf_token' => $token,
    'email' => 'admin@example.local',
    'password' => 'ChangeMe123!',
]));

$newCookie = extractCookie($login['headers']);
if ($newCookie !== '') {
    $cookie = $newCookie;
}

$create = request('GET', $base . '/incidents/create', ['Cookie' => $cookie]);
$createToken = extractToken($create['body']);
preg_match('/name="incident_number" id="incident_number" readonly value="([^"]*)"/', $create['body'], $mNum);
$incidentNumber = $mNum[1] ?? '';

$store = request('POST', $base . '/incidents/store', [
    'Content-Type' => 'application/x-www-form-urlencoded',
    'Cookie' => $cookie,
], http_build_query([
    'csrf_token' => $createToken,
    'incident_number' => $incidentNumber,
    'reported_at' => date('Y-m-d\TH:i'),
    'type' => 'crime',
    'reporting_unit' => 'Map Verification Unit',
    'grid_reference' => '35MNV1234512345',
    'latitude' => '-15.4167',
    'longitude' => '28.2833',
    'narrative' => 'Map verification incident',
    'threat_level' => 'high',
    'status' => 'open',
    'confidentiality_level' => 'restricted',
]));

$location = '';
foreach ($store['headers'] as $h) {
    if (stripos($h, 'Location:') === 0) {
        $location = trim(substr($h, strlen('Location:')));
        break;
    }
}

$detail = request('GET', $base . $location, ['Cookie' => $cookie]);
$body = $detail['body'];

echo (strpos($body, 'id="incident-detail-map"') !== false ? "MAP_DIV_OK\n" : "MAP_DIV_MISSING\n");
echo (strpos($body, '/js/map.js') !== false ? "MAP_JS_TAG_OK\n" : "MAP_JS_TAG_MISSING\n");
