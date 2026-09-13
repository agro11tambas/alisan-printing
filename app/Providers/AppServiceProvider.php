<?php

namespace App\Providers;

use App\Services\WebsiteRevalidator;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->batasiKatalogPublik();
    }

    /**
     * Batasi laju endpoint katalog publik.
     *
     * Kenapa: /api/v1/ecommerce/products, /products/{slug}, dan /categories
     * terbuka tanpa batas apa pun, dan dilayani oleh kolam worker PHP yang SAMA
     * dengan ERP. Satu crawler yang menyapu katalog cukup untuk menghabiskan
     * worker, dan seluruh halaman ERP mengantre di belakangnya — yang dirasakan
     * pengguna sebagai buffering di semua modul, sesekali, tanpa pola.
     *
     * Log klien 10 September 2026 menunjukkan bentuk itu persis: queue_ms ~30
     * detik (menunggu giliran dilayani) sementara ttfb hanya 119-213 ms begitu
     * gilirannya tiba. Server tidak lambat; ERP-nya menunggu.
     *
     * Angkanya sengaja longgar. Website Next.js merender di sisi server, jadi
     * permintaannya datang dari SATU alamat IP — batas yang ketat akan mematikan
     * storefront, bukan crawler-nya. 300/menit menahan penyapuan massal tanpa
     * menyentuh lalu lintas normal. Bisa disetel lewat ECOMMERCE_API_RATE_LIMIT
     * tanpa mengubah kode.
     */
    private function batasiKatalogPublik(): void
    {
        RateLimiter::for('katalog-publik', function (Request $request) {
            $perMenit = (int) config('services.website.api_rate_limit', 300);

            // 0 = matikan pembatasan, untuk keadaan darurat.
            if ($perMenit <= 0) {
                return Limit::none();
            }

            return Limit::perMinute($perMenit)
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Terlalu banyak permintaan. Coba lagi sebentar lagi.',
                    ], 429, ['Retry-After' => '60']);
                });
        });
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