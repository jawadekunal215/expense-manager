<?php

// Single Vercel PHP entry point for SpendSmart.
// Existing PHP pages remain in their original locations.

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestPath = '/' . ltrim($requestPath, '/');

// Remove query-string-related path artifacts.
$requestPath = preg_replace('/\/+/', '/', $requestPath);

// Root of the website -> existing index.php
if ($requestPath === '/' || $requestPath === '/index.php') {
    require __DIR__ . '/../index.php';
    exit;
}

// Existing application PHP endpoints.
$allowedPrefixes = [
    '/auth/',
    '/pages/',
    '/actions/'
];

// Only allow PHP files inside the application's existing endpoint folders.
$isAllowed = false;

foreach ($allowedPrefixes as $prefix) {
    if (strpos($requestPath, $prefix) === 0 && substr($requestPath, -4) === '.php') {
        $isAllowed = true;
        break;
    }
}

if (!$isAllowed) {
    http_response_code(404);
    echo '404 - Page not found';
    exit;
}

// Prevent path traversal.
$relativePath = ltrim($requestPath, '/');

if (strpos($relativePath, '..') !== false) {
    http_response_code(403);
    echo '403 - Forbidden';
    exit;
}

$target = __DIR__ . '/../' . $relativePath;

if (!is_file($target)) {
    http_response_code(404);
    echo '404 - PHP page not found';
    exit;
}

// Execute the original PHP page without modifying it.
require $target;
exit;
