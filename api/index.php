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

if (getenv('VERCEL') !== false) {
    $storagePath = '/tmp/storage';

    foreach ([
        $storagePath.'/app/public',
        $storagePath.'/framework/cache/data',
        $storagePath.'/framework/sessions',
        $storagePath.'/framework/views',
        $storagePath.'/logs',
    ] as $directory) {
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    $app->useStoragePath($storagePath);
}

$app->handleRequest(Request::capture());
