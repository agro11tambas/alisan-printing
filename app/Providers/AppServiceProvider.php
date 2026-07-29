<?php

namespace App\Providers;

use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->isProduction()) {
            DB::whenQueryingForLongerThan(1000, function (Connection $connection, QueryExecuted $event): void {
                Log::warning('Slow database request detected.', [
                    'connection' => $connection->getName(),
                    'query' => $event->sql,
                    'query_time_ms' => $event->time,
                ]);
            });
        }
    }
}