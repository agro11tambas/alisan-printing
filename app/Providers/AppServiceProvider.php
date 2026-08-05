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
            // Ambang ini kumulatif: callback jalan sekali saat total waktu query
            // pada satu request melewati 1 detik. $event adalah query yang
            // melewati ambang, belum tentu query paling lambatnya.
            DB::whenQueryingForLongerThan(1000, function (Connection $connection, QueryExecuted $event): void {
                Log::channel('performance')->warning('performance.query_budget_exceeded', [
                    'connection' => $connection->getName(),
                    'query_at_threshold' => $event->sql,
                    'query_time_ms' => $event->time,
                ]);
            });
        }
    }
}