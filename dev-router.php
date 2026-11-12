<?php

declare(strict_types=1);

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($requestPath) ? rawurldecode($requestPath) : '';

if ($path === '/' || $path === '/index.php') {
    require __DIR__ . '/index.php';
    return true;
}

if ($path === '' || str_contains($path, "\0") || str_contains($path, '\\') || preg_match('~(?:^|/)\.~', $path)) {
    http_response_code(404);
    return true;
}

$publicRoot = realpath(__DIR__ . '/public');
$requestedFile = realpath(__DIR__ . $path);
$allowedExtensions = ['css', 'gif', 'ico', 'jpeg', 'jpg', 'js', 'png', 'svg', 'webp', 'woff', 'woff2'];

if (
    $publicRoot !== false
    && $requestedFile !== false
    && is_file($requestedFile)
    && str_starts_with($requestedFile, $publicRoot . DIRECTORY_SEPARATOR)
    && in_array(strtolower(pathinfo($requestedFile, PATHINFO_EXTENSION)), $allowedExtensions, true)
) {
    return false;
}

http_response_code(404);
return true;
