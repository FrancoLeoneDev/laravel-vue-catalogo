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

$app->handleRequest(Request::capture());
