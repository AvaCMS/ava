<?php

declare(strict_types=1);

/**
 * Ava CMS Front Controller
 *
 * All requests route through here. The bootstrap handles loading,
 * the router handles matching, the renderer handles output.
 * 
 * Performance: Cached pages are served before full boot for minimal TTFB.
 */

define('AVA_START', microtime(true));
define('AVA_ROOT', dirname(__DIR__));

$app = require AVA_ROOT . '/bootstrap.php';

// Fast path: Try to serve a cached page without full boot
// This skips plugin loading, theme loading, and cache freshness checks
$request = Ava\Http\Request::capture();
$cached = $app->tryCachedResponse($request);
if ($cached !== null) {
    $cached->withHeader('X-Fast-Path', 'true')->send();
    exit;
}

// Full path: Boot the application and handle the request
$app->boot();
$response = $app->handle($request);
$response->send();
