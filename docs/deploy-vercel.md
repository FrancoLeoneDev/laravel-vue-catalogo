---
name: laravel-vercel-deploy
description: Use when deploying a Laravel (+ Inertia/Vue/React) application to Vercel, or when a Vercel deploy of a PHP app fails. Covers the vercel-php community runtime, the read-only filesystem, the `php: command not found` build error, source-code leaking via public/index.php, serverless env var overrides, and getting a free external MySQL. Triggers on "deploy Laravel to Vercel", "Laravel en Vercel", "vercel-php", "PHP on Vercel".
---

# Deploying Laravel + Vue on Vercel

Verified against Laravel 13.25, Inertia 3, Vue 3, Vite 8, `vercel-php@0.9.0` (PHP 8.5).

## Decide first: is Vercel right for this app?

Vercel's official runtimes are Node.js, Bun, Python, Rust, Go, Ruby, Wasm and Edge.
**PHP is not among them.** `vercel-php` is a community runtime that Vercel lists as
recommended; it supports PHP up to 8.5.

Three non-negotiable consequences:

- **Read-only filesystem**, except `/tmp` (500 MB, ephemeral, per-instance). Laravel
  needs to write to `storage/`.
- **No MySQL on Vercel.** The marketplace offers Postgres (Neon, Supabase, Prisma).
  MySQL must come from an external provider.
- **File uploads do not persist** between invocations. Apps that accept uploads need
  Vercel Blob or S3.

If any of that is unacceptable, Laravel Cloud or Railway are the lower-friction options.
Vercel's advantage is a permanently free Hobby tier (200 projects, no credit card).

## Step 1 — Front controller in `api/`

Vercel discovers functions in `api/`. Create a front controller there that relocates
Laravel's writable paths to `/tmp` before the framework boots. The `VERCEL` guard keeps
the file usable locally, so it can be tested without deploying.

```php
<?php
// api/index.php

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
```

Verify locally before deploying — if this fails, the deploy fails:

```bash
php -S 127.0.0.1:8010 -t public api/index.php
```

## Step 2 — `vercel.json`

`outputDirectory: "public"` makes Vercel serve Vite's compiled assets as static files.
The rewrite only fires when no static route matched, so the two coexist.

```json
{
    "framework": null,
    "buildCommand": "npm ci && npm run build",
    "outputDirectory": "public",
    "functions": {
        "api/index.php": {
            "runtime": "vercel-php@0.9.0",
            "maxDuration": 30
        }
    },
    "rewrites": [
        { "source": "/(.*)", "destination": "/api/index.php" }
    ]
}
```

**Runtime version ≠ PHP version.** `@0.9.0` is PHP 8.5, `@0.8.0` is 8.4, `@0.7.4` is 8.3,
`@0.6.2` is 8.2. Laravel 13 requires PHP 8.3+, so anything below `@0.7.4` will not boot.

**Do not set `memory`.** Under Active CPU billing Vercel ignores it and warns on every deploy.

## Step 3 — Keep `public/index.php` out of the upload

With `outputDirectory: "public"`, everything in `public/` is served as a static file —
including `public/index.php`. Requesting `/index.php` returns the **PHP source in plain
text**, because Vercel does not execute it; the front controller is now `api/index.php`.

This is a real source-code leak and it is invisible from the homepage.

```
# .vercelignore
public/index.php
public/hot
public/storage

node_modules
tests
storage/logs/*.log
docker-compose.yml
```

`.vercelignore` only affects what is uploaded to Vercel — the repo and local environment
keep using `public/index.php` normally.

## Step 4 — The Vercel build image has no PHP

`buildCommand` runs in the Node build image. **There is no PHP there.** The PHP runtime
only exists when the function is built, which is a separate stage.

Any Vite plugin that shells out to `php artisan` breaks the build. The common case is
Wayfinder, failing with `php: command not found`.

Fix in two parts. First, commit the generated helpers — remove `/resources/js/actions`,
`/resources/js/routes` and `/resources/js/wayfinder` from `.gitignore` and regenerate with
`php artisan wayfinder:generate --with-form`. Second, skip generation on Vercel:

```ts
// vite.config.ts
const canRunArtisan = !process.env.VERCEL;

export default defineConfig({
    plugins: [
        /* ... */
        ...(canRunArtisan ? [wayfinder({ formVariants: true })] : []),
    ],
});
```

Verify locally by simulating the environment: `VERCEL=1 npm run build`.

## Step 5 — Free external MySQL

**Aiven for MySQL** free tier: 1 GB disk, 1 GB RAM, real MySQL (not "compatible"),
backups included, no credit card, no time limit.

- Sign up at <https://console.aiven.io/signup>
- **Create service → MySQL** (the grid puts PostgreSQL and Kafka next to it — easy to misclick)
- Service tier **Free**, plan `Free-1-1gb`
- Pick the region closest to `iad1`, Vercel's default

Limits that matter: **76 max connections**, and the service powers off after prolonged
inactivity. Fine for a demo, not for production.

Aiven requires SSL. Download the CA certificate from the panel, commit it (it is not a
secret) and point `MYSQL_ATTR_SSL_CA` at it.

## Step 6 — Environment variables

Laravel's defaults assume a writable filesystem and a long-lived process:

| Variable | Value | Why |
| --- | --- | --- |
| `SESSION_DRIVER` | `cookie` | No shared disk or state across instances; also avoids burning Aiven's 76 connections |
| `CACHE_STORE` | `array` | File cache needs to write to disk |
| `LOG_CHANNEL` | `stderr` | Logs go to the Vercel panel; `storage/logs` is useless |
| `QUEUE_CONNECTION` | `sync` | No worker process running |
| `APP_KEY` | `base64:…` | Generate with `php artisan key:generate --show` |
| `APP_DEBUG` | `false` | Set `true` only while debugging the deploy; lower it before sharing the URL |

Load them from the terminal, values via stdin so they do not land in shell history:

```bash
printf '%s' "cookie" | vercel env add SESSION_DRIVER production
printf '%s' "array"  | vercel env add CACHE_STORE production
```

Variables apply to the **next** deploy, not the running one. Redeploy after setting them.

## Step 7 — Deploy and migrate

```bash
vercel login
vercel link --yes
vercel deploy --prod --yes
```

Migrations do **not** run automatically — there is no deploy hook running artisan. Because
the database is external and reachable over the internet, run them from your machine with
the local `.env` pointed at the production database:

```bash
php artisan migrate --force --seed
```

Make seeders idempotent (an early "is there data already?" check) so they can be re-run
without duplicating rows.

## Step 8 — Checklist before sharing the URL

- [ ] `APP_DEBUG=false`, and redeployed after changing it
- [ ] `/index.php` does not return source code
- [ ] Login and session work (log in, navigate, reload)
- [ ] Vite assets load — no 404s under `/build/`
- [ ] Migrations and seeders run against the production database
- [ ] Documented that file uploads do not persist, if the app has them
