---
name: laravel-vercel-deploy
description: Use when deploying a Laravel (+ Inertia/Vue/React) application to Vercel, or when a Vercel deploy of a PHP app fails or renders a blank page. Covers the vercel-php community runtime, the read-only filesystem, `php: command not found` during the build, `Target class [view] does not exist`, missing dev service providers, mixed-content asset blocking behind the TLS proxy, source-code leaking via public/index.php, serverless env var overrides, free external MySQL, and the GitHub Actions fixes the same stack needs. Triggers on "deploy Laravel to Vercel", "Laravel en Vercel", "vercel-php", "PHP on Vercel".
---

# Deploying Laravel + Vue on Vercel

Verified end to end against Laravel 13.25, Inertia 3, Vue 3, Vite 8, `vercel-php@0.9.0`
(PHP 8.5), with a managed MySQL on Aiven. Every gotcha below is one that actually broke a
real deployment, in the order it broke it.

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
Laravel's writable paths to `/tmp` before the framework boots.

**Detect the read-only filesystem by probing it, not by reading `VERCEL`.** That env var
only exists when the project opts into exposing system environment variables. If you gate
on it and it is absent, Laravel boots against a read-only storage path and dies with
`Target class [view] does not exist` — an error that points nowhere near the real cause.

```php
<?php
// api/index.php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

if (! is_writable(__DIR__.'/../storage/framework')) {
    $storagePath = '/tmp/storage';

    foreach ([
        $storagePath.'/app/public',
        $storagePath.'/framework/cache/data',
        $storagePath.'/framework/sessions',
        $storagePath.'/framework/views',
        $storagePath.'/logs',
        '/tmp/bootstrap-cache',
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
    "regions": ["sfo1"],
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

**Set `regions` to match your database.** Vercel defaults to `iad1` (Washington DC). A
database in another region adds a round trip per query, and a catalog page can easily make
six.

## Step 3 — Keep `public/index.php` out of the upload

With `outputDirectory: "public"`, everything in `public/` is served as a static file —
including `public/index.php`. Requesting `/index.php` would return the **PHP source in
plain text**, because Vercel does not execute it; the front controller is now
`api/index.php`.

This is a real source-code leak and it is invisible from the homepage.

**A `.vercelignore` disables `.gitignore`.** Once the file exists, Vercel stops applying
`.gitignore`, so everything local is uploaded unless listed. That includes `.env` — its
secrets ship with the bundle, and any variable the dashboard does not set is shadowed by
the local value. This is silent: the app keeps working because Laravel loads `.env`
immutably and platform variables win, right up until one is missing from the dashboard and
the app quietly reads your laptop's config in production.

```
# .vercelignore
.env
.env.*
!.env.example

public/index.php
public/hot
public/storage

# See step 5: the manifest is built at runtime, never shipped.
bootstrap/cache/*.php

node_modules
tests
storage/logs/*.log
docker-compose.yml
```

`.vercelignore` only affects what is uploaded to Vercel — the repo and local environment
keep using `public/index.php` normally.

Verify after deploying: `curl -s https://your-app.vercel.app/index.php | head -c 20`
must not start with `<?php`.

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

Regenerate with `--with-form` if the app uses the `.form()` helpers
(`Controller.update.form(...)`, which is the Inertia `<Form>` idiom). Without the flag
those variants are missing and `vue-tsc` reports type errors that look unrelated.

Committing generated code has a knock-on effect: add those three paths to
`.prettierignore` as well, or `format:check` will fail on ~75 files that are not yours.
ESLint's default Laravel config already ignores them.

### `package-lock.json` generated on Windows can break `npm ci` on Linux

`npm install` on Windows can omit optional dependencies that only apply to Linux — the
platform-specific Rollup and `@emnapi` binaries. The lockfile then looks fine locally and
fails in CI and on Vercel, both of which run `npm ci` on Linux.

Check before deploying:

```bash
grep -c "linux-x64-gnu" package-lock.json   # 0 means the lock is incomplete
```

If it comes back empty, regenerate the lock inside a Linux container:

```bash
docker run --rm -v "$PWD":/app -w /app node:24 npm install --package-lock-only
```

## Step 5 — The package manifest, and why it must be built at runtime

Two failures come from the same place, and they look nothing alike:

- `Class "Laravel\Pail\PailServiceProvider" not found` — the manifest shipped from your
  machine lists dev-only providers. **Vercel installs with `--no-dev` and does not run
  Composer scripts**, so it never regenerates the manifest.
- `Target class [view] does not exist` — you excluded `bootstrap/cache/*.php` to fix the
  first problem, which removed the directory entirely, so *no* providers got registered.

Do not ship a manifest at all. Laravel reads the cache paths from env vars, so point them
at the writable `/tmp`:

```
APP_PACKAGES_CACHE=/tmp/bootstrap-cache/packages.php
APP_SERVICES_CACHE=/tmp/bootstrap-cache/services.php
```

The directory is created by the front controller in step 1. Laravel then builds a correct,
dev-free manifest on the first request. This also makes **git-triggered deployments** work,
which shipping a manifest never would, since `bootstrap/cache` is gitignored.

## Step 6 — Trust the proxy, or every asset is blocked

Symptom: the page loads, the HTML is correct, and the screen is **blank** with dozens of
console errors reading `Mixed Content: ... requested an insecure script`.

Vercel terminates TLS and forwards to the function over plain HTTP. Without trusting the
forwarded headers, Laravel builds `http://` asset URLs on an `https://` page and the
browser blocks every script and stylesheet.

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR
        | Request::HEADER_X_FORWARDED_HOST
        | Request::HEADER_X_FORWARDED_PORT
        | Request::HEADER_X_FORWARDED_PROTO
        | Request::HEADER_X_FORWARDED_AWS_ELB);

    // ...
})
```

The proxy IP is not stable, so every hop is trusted and the platform is relied on to set
the headers. Setting `APP_URL` alone does **not** fix this — asset URLs follow the request
scheme.

## Step 7 — Free external MySQL

**Aiven for MySQL** free tier: 1 GB disk, 1 GB RAM, real MySQL (not "compatible"),
backups included, no credit card, no time limit.

- Sign up at <https://console.aiven.io/signup>
- **Create service → MySQL** (the grid puts PostgreSQL and Kafka next to it — easy to misclick)
- Service tier **Free**, plan `Free-1-1gb`
- Leave the IP allowlist at `0.0.0.0/0`: Vercel functions have no fixed IP on Hobby.
  Password plus required TLS is the protection.

Limits that matter: **76 max connections**, and the service powers off after prolonged
inactivity. Prefer `SESSION_DRIVER=cookie` so sessions do not consume connections.

**One MySQL service per organization on the free tier.** A second project cannot get its
own. Share the service instead and give each app its own database — `avnadmin` can create
them over SQL, verified:

```sql
CREATE DATABASE `my_second_app`;
```

Then point that project's `DB_DATABASE` at the new name, reusing the same host, port, user,
password and CA certificate. Migrations only touch their own schema, so the projects stay
isolated. Two caveats: the 76 connections are shared across every app on the service, and
`avnadmin` is the service admin, so each app technically has rights over the others' data.
Fine for portfolio demos, not for anything real.

Aiven requires TLS. Download the CA certificate, commit it (it is a public certificate,
not a key), and point `MYSQL_ATTR_SSL_CA` at it. Laravel's `config/database.php` already
reads that variable; make the path resolve relative to the project so it works both
locally and on the platform:

```php
'options' => extension_loaded('pdo_mysql') ? array_filter([
    Mysql::ATTR_SSL_CA => is_string($ca = env('MYSQL_ATTR_SSL_CA')) && $ca !== ''
        ? (str_starts_with($ca, '/') || preg_match('/^[A-Za-z]:/', $ca) === 1 ? $ca : base_path($ca))
        : null,
]) : [],
```

## Step 8 — Environment variables

Laravel's defaults assume a writable filesystem and a long-lived process:

| Variable | Value | Why |
| --- | --- | --- |
| `SESSION_DRIVER` | `cookie` | No shared disk or state across instances; also avoids burning the database's connection limit |
| `CACHE_STORE` | `array` | File cache needs to write to disk |
| `LOG_CHANNEL` | `stderr` | Logs go to the Vercel panel; `storage/logs` is useless |
| `QUEUE_CONNECTION` | `sync` | No worker process running |
| `APP_PACKAGES_CACHE` | `/tmp/bootstrap-cache/packages.php` | See step 5 |
| `APP_SERVICES_CACHE` | `/tmp/bootstrap-cache/services.php` | See step 5 |
| `MYSQL_ATTR_SSL_CA` | `certs/aiven-ca.pem` | Managed MySQL requires TLS |
| `APP_KEY` | `base64:…` | Generate with `php artisan key:generate --show` |
| `APP_DEBUG` | `false` | Set `true` only while debugging the deploy; lower it before sharing the URL |

Load them from the terminal, values via stdin so they do not land in shell history:

```bash
printf '%s' "cookie" | vercel env add SESSION_DRIVER production
printf '%s' "array"  | vercel env add CACHE_STORE production
```

Variables apply to the **next** deploy, not the running one. Redeploy after setting them.

## Step 9 — Deploy and migrate

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

### Reading errors when the deploy fails

`vercel logs` truncates the message to terminal width, which is useless for a PHP stack
trace. The fastest way to see the real exception is to make the function print it, then
remove the block once fixed:

```php
try {
    $app->handleRequest(Request::capture());
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo get_class($e).': '.$e->getMessage()."\n\n".$e->getTraceAsString();
}
```

## Step 10 — Fix CI at the same time

The same stack breaks GitHub Actions in ways unrelated to Vercel. Check all of these:

- **PHP version vs. the lockfile.** If you develop on PHP 8.5, `composer.lock` can pin
  Symfony 8.1, which requires PHP >= 8.4.1. A workflow pinned to `8.3` fails on install
  with a wall of "requires php >=8.4.1" problems. Set `php-version` to something the lock
  actually satisfies.
- **Do not run `composer setup` in CI.** The starter kit's script runs
  `php artisan migrate --force`, which needs a database that CI does not have. Use
  explicit steps instead; they also attribute failures better in the Actions UI.
- **No database service needed** if `phpunit.xml` uses SQLite in memory — but see the
  portability warning below.
- **PHPStan memory.** `phpstan analyse` crashes at PHP's default 128M. Add
  `--memory-limit=1G` to the composer script rather than relying on the runner's php.ini.
- **Generated code breaks `format:check`.** See step 4.

```yaml
- name: Install PHP dependencies
  run: composer install --no-interaction --prefer-dist --no-progress

- name: Prepare environment
  run: |
    cp .env.example .env
    php artisan key:generate

- name: Install and build front-end
  run: |
    npm ci
    npm run build

- name: Check PHP formatting
  run: composer lint:check

- name: Analyse PHP types
  run: composer types:check

- name: Check front-end
  run: |
    npm run lint:check
    npm run format:check
    npm run types:check

- name: Run tests
  run: php artisan test
```

### Portability trap: MySQL-only SQL fails on SQLite

If production is MySQL and tests run on SQLite, SQL that MySQL tolerates can abort in CI.
The one that bit here: filtering a `SELECT` alias with `HAVING` on a query that has no
`GROUP BY`.

```php
// MySQL accepts this. SQLite: "HAVING clause on a non-aggregate query".
->havingRaw('current_stock <= low_stock_threshold');
```

It also silently breaks `count()`, which returns a per-group count. Move the comparison
into a `WHERE` over the same subquery:

```php
->whereRaw(
    '(select coalesce(sum(case when type = ? then quantity else -quantity end), 0)'
    .' from stock_movements where stock_movements.product_id = products.id)'
    .' <= products.low_stock_threshold',
    [StockMovementType::Entrada->value],
);
```

Either run CI against the same engine as production, or keep the SQL portable. Do not
discover this from a red build after the code is already deployed.

## Step 11 — Checklist before sharing the URL

- [ ] `APP_DEBUG=false`, and redeployed after changing it
- [ ] `/index.php` does not return source code
- [ ] The page is not blank — open it in a browser and check the console for mixed content
- [ ] Login and session work (log in, navigate, reload)
- [ ] Vite assets load over `https://` — no 404s and no blocked requests under `/build/`
- [ ] Migrations and seeders run against the production database
- [ ] CI is green on the deployed commit
- [ ] Documented that file uploads do not persist, if the app has them
