# Production deployment and performance

## Required environment

Never deploy the local `.env` unchanged. Start from `.env.production.example`. At minimum use:

```dotenv
APP_ENV=production
APP_DEBUG=false
DEBUGBAR_ENABLED=false
LOG_CHANNEL=daily
LOG_LEVEL=warning
LOG_DAILY_DAYS=14
```

### Why `DEBUGBAR_ENABLED=false` is set explicitly

Laravel Debugbar's own default is `env('DEBUGBAR_ENABLED', null)`, and `null`
means "follow `APP_DEBUG`". Production currently runs `APP_DEBUG=false`, so
Debugbar is off — but that safety depends entirely on nobody ever flipping
`APP_DEBUG` to true to chase a bug. If that happened, every request would be
profiled with full backtraces and dumped as a ~100 KB JSON file into
`storage/debugbar`, which would slow the whole application to a crawl.

`config/debugbar.php` is now committed with `'enabled' => env('DEBUGBAR_ENABLED', false)`,
so the two switches are independent. Keep it that way.

### Performance logging must not follow `LOG_LEVEL`

Production runs `LOG_LEVEL=error`. Both slow-request instrumentations write at
`warning` level, which is *below* `error`, so from the moment `LOG_LEVEL=error`
was set every one of those records was silently discarded. During the slowdown
on 5 August 2026 the application produced no diagnostic output at all, and the
only available evidence was the web server access log.

`App\Http\Middleware\LogSlowRequests` and the slow-query handler in
`AppServiceProvider` now write to the dedicated `performance` channel, which is
pinned to level `debug` and rotates into `storage/logs/performance-*.log`
independently of `LOG_LEVEL`.

For one application server, `CACHE_STORE=file` and `SESSION_DRIVER=file` avoid two database operations on most requests. For multiple application servers, use Redis for both instead. Do not use file-backed state across multiple servers.

Keep `QUEUE_CONNECTION=database` only when a continuously running queue worker is configured. Redis is preferred when available.

## Deploy commands

Run from the application directory after uploading the new release:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan optimize:clear
php artisan migrate --force
php artisan optimize
php artisan queue:restart
```

The repository includes `scripts/deploy-production.sh` for the same sequence.

`composer install --no-dev` matters: Debugbar is a dev dependency, and installing
dev dependencies on the server is what makes it available to run at all.

`php artisan optimize` must complete successfully. It caches configuration, events, routes, and Blade views. Run it again after every `.env`, config, route, or view deployment.

## Web server

- Point the document root to the Laravel `public/` directory.
- Enable PHP OPcache. Recommended starting values:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
```

With `opcache.validate_timestamps=0`, restart PHP-FPM after each deployment.

The included `public/.htaccess` enables compression and browser caching when the corresponding Apache modules are available. For Nginx, configure equivalent gzip/Brotli and static-file cache headers.

## Queue and scheduler

Run a persistent queue worker under Supervisor/systemd:

```sh
php artisan queue:work --sleep=1 --tries=3 --timeout=120
```

Run the scheduler every minute:

```cron
* * * * * cd /path/to/application && php artisan schedule:run >> /dev/null 2>&1
```

## Diagnosing intermittent slowness

Start with the built-in check, which reports the settings that have actually
caused outages here (debug mode, Debugbar, missing caches, oversized log and
Debugbar directories):

```sh
php artisan app:check-production
```

Production requests whose cumulative database time exceeds one second are logged as `Slow database request detected.` Check `storage/logs` for those entries. Also monitor PHP-FPM worker saturation, MySQL slow queries, CPU, RAM, and disk I/O on the server.

`App\Http\Middleware\LogSlowRequests` logs any request slower than
`SLOW_REQUEST_LOG_MS` (default 1000 ms) to `storage/logs/performance-*.log` as
`performance.slow_request`, including `query_count`, `database_ms`, and
`application_ms`. That split is what tells you where the time actually went:

- high `database_ms` with a **low** `query_count` — one heavy query; find it in
  the same file under `performance.query_budget_exceeded`, or in the MySQL slow
  query log, and check its `EXPLAIN` for a missing index
- high `database_ms` with a **high** `query_count` — N+1; the endpoint is missing
  an eager load
- high `application_ms` with low `database_ms` — PHP-side work: rendering, or a
  blocking outbound HTTP call
- **all three low but the access log says the request took minutes** — the request
  spent its time queueing before PHP ever started. On shared hosting that means
  the concurrent-process limit was saturated by other slow requests. Fix the
  slow endpoints; the queue drains by itself.

That last case is the one to watch for here, because a page in this ERP fires
several data endpoints in parallel: a handful of genuinely slow requests is
enough to block every other request behind them.

## Log housekeeping

`LOG_STACK=single` never rotates. Switch to `LOG_STACK=daily` with
`LOG_DAILY_DAYS=14`, and truncate `storage/logs/laravel.log` once if it has
already grown large.

`storage/debugbar` should be empty in production. If it is not, Debugbar ran at
some point and the dumps are safe to delete:

```sh
rm -rf storage/debugbar/*.json
```