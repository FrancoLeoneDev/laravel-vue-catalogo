<?php

/**
 * Front controller for Vercel.
 *
 * Vercel Functions run on a read-only filesystem with only /tmp writable, so
 * Laravel's writable paths are relocated there before the framework boots.
 * Outside Vercel this file behaves exactly like public/index.php, which keeps
 * it testable locally.
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// Detect the read-only filesystem by probing it rather than by trusting a
// platform env var: VERCEL is only present when the project opts into exposing
// system environment variables, and without the relocation Laravel fails while
// resolving the view compiler.
if (! is_writable(__DIR__.'/../storage/framework')) {
    $storagePath = '/tmp/storage';

    foreach ([
        $storagePath.'/app/public',
        $storagePath.'/framework/cache/data',
        $storagePath.'/framework/sessions',
        $storagePath.'/framework/views',
        $storagePath.'/logs',
        // Laravel builds its package manifest here on the first request. The
        // APP_PACKAGES_CACHE / APP_SERVICES_CACHE env vars point at this path,
        // so no manifest has to be shipped with the deployment.
        '/tmp/bootstrap-cache',
    ] as $directory) {
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    $app->useStoragePath($storagePath);
}

try {
    $app->handleRequest(Request::capture());
} catch (Throwable $e) {
    // Temporary: the platform log viewer truncates the message.
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo get_class($e).': '.$e->getMessage()."\n\n";

    $base = __DIR__.'/../';
    echo "cache dir exists: ".var_export(is_dir($base.'bootstrap/cache'), true)."\n";
    echo "cache writable:   ".var_export(is_writable($base.'bootstrap/cache'), true)."\n";
    echo "cache contents:   ".implode(', ', array_diff(@scandir($base.'bootstrap/cache') ?: [], ['.', '..']))."\n";
    echo "providers.php:    ".var_export(is_file($base.'bootstrap/providers.php'), true)."\n";
    echo "installed.json:   ".var_export(is_file($base.'vendor/composer/installed.json'), true)."\n";
    echo "pail installed:   ".var_export(
        is_file($base.'vendor/composer/installed.json')
            && str_contains((string) @file_get_contents($base.'vendor/composer/installed.json'), 'laravel/pail'),
        true
    )."\n\n";

    echo $e->getTraceAsString();
}
