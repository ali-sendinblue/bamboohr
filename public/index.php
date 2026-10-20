<?php
// Front controller: every request that is not a file under public/ lands here.
//   php -S localhost:8000 -t public public/index.php
// On Apache, public/.htaccess does the same rewrite; on nginx use `try_files $uri /index.php?$query_string;`.

const ROOT = __DIR__ . '/..';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// PHP's built-in server: let it serve existing assets (public/asset/...) itself
if (PHP_SAPI === 'cli-server' && $path !== '/' && is_file(__DIR__ . $path)) {
    return false;
}

// Strip the base path when the app is hosted in a sub-folder
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
if ($base !== '' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base));
}
$path = '/' . trim($path, '/');

$routes = [
    '/' => fn() => require ROOT . '/src/pages/home.php',
    '/leaves' => fn() => readfile(ROOT . '/src/pages/leaves.html'),
    '/oncall' => fn() => readfile(ROOT . '/src/pages/oncall.html'),
    '/proxy' => fn() => require ROOT . '/src/proxy.php',
    '/holidays' => function () {
        header('Content-Type: application/json');
        header('Cache-Control: no-cache');
        readfile(ROOT . '/holidays.json');
    },
];

if (!isset($routes[$path])) {
    http_response_code(404);
    header('Content-Type: text/plain');
    exit("Not found\n");
}

$routes[$path]();
