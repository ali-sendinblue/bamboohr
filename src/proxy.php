<?php
// Same-origin proxy for the BambooHR API (BambooHR sends no CORS headers).
// The API key travels in the X-Bamboo-Key header, is used once and never stored or logged.
//
// GET /proxy?d=<companyDomain>&p=<endpoint>&<query params forwarded as-is>

const ALLOWED_PATHS = [
    'employees/directory',
    'time_off/requests',
    'time_off/whos_out',
    'meta/time_off/types',
];

header('Content-Type: application/json');
header('Cache-Control: no-store');

function fail(int $code, string $message): never
{
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

$domain = $_GET['d'] ?? '';
$path = $_GET['p'] ?? '';
$key = $_SERVER['HTTP_X_BAMBOO_KEY'] ?? '';

if (!preg_match('/^[a-z0-9][a-z0-9-]{0,62}$/i', $domain)) {
    fail(400, 'Invalid company domain');
}
if (!in_array($path, ALLOWED_PATHS, true)) {
    fail(400, 'Endpoint not allowed');
}
if ($key === '') {
    fail(401, 'Missing API key');
}

$query = $_GET;
unset($query['d'], $query['p']);
$url = "https://$domain.bamboohr.com/api/v1/$path" . ($query ? '?' . http_build_query($query) : '');

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => "$key:x",
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
    CURLOPT_TIMEOUT => 60,
    CURLOPT_FOLLOWLOCATION => false,
]);
$body = curl_exec($ch);
$status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($body === false) {
    fail(502, "Could not reach BambooHR: $curlError");
}

http_response_code($status ?: 502);
echo $body === '' ? json_encode(['error' => "BambooHR answered HTTP $status"]) : $body;
