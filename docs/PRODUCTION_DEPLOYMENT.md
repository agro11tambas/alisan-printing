# Production deployment and performance

## Required environment

Never deploy the local `.env` unchanged. At minimum use:

```dotenv
APP_ENV=production
APP_DEBUG=false
LOG_CHANNEL=daily
LOG_LEVEL=warning
LOG_DAILY_DAYS=14
```

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

Production requests whose cumulative database time exceeds one second are logged as `Slow database request detected.` Check `storage/logs` for those entries. Also monitor PHP-FPM worker saturation, MySQL slow queries, CPU, RAM, and disk I/O on the server.