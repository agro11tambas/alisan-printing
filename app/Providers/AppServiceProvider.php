<?php

namespace App\Providers;

use App\Services\WebsiteRevalidator;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
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
        // Singleton supaya beberapa perubahan dalam satu request digabung jadi
        // satu panggilan revalidate ke website.
        $this->app->singleton(WebsiteRevalidator::class);
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

        $this->detectLazyLoading();
    }

    /**
     * Sebutkan relasi mana yang di-load satu per satu (N+1).
     *
     * Dinyalakan lewat DETECT_LAZY_LOADING=true, dan sengaja hanya MENCATAT,
     * tidak melempar exception, supaya aman dinyalakan sebentar di produksi
     * untuk mencari sumber query_count yang tinggi. Matikan lagi setelah
     * datanya terkumpul: pencatatannya sendiri menambah beban.
     */
    private function detectLazyLoading(): void
    {
        if (! config('app.detect_lazy_loading')) {
            return;
        }

        Model::preventLazyLoading();

        Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): void {
            Log::channel('performance')->warning('performance.lazy_loading', [
                'model' => $model::class,
                'relation' => $relation,
                'path' => request()->path(),
            ]);
        });
    }
}