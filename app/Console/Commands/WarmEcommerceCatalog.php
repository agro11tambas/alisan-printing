<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\Public\EcommerceProductCategoryController;
use App\Http\Controllers\Api\Public\EcommerceProductController;
use App\Services\EcommerceCatalogCache;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Bangun ulang cache katalog dari CLI, bukan dari request web.
 *
 * Alasannya kapasitas proses, bukan kecepatan. Membangun katalog penuh makan
 * waktu menit-menitan; selama itu satu proses PHP di web server terpakai penuh.
 * Di shared hosting jatah prosesnya sedikit, jadi satu rebuild saja sudah
 * mengurangi kapasitas yang tersisa untuk halaman ERP — request lain mengantre
 * di depan PHP dan tercatat lambat di access log padahal kodenya sendiri cepat.
 *
 * Dengan command ini yang dijadwalkan, request web tidak pernah lagi membangun
 * katalog: mereka selalu dilayani dari salinan yang sudah jadi.
 */
class WarmEcommerceCatalog extends Command
{
    protected $signature = 'catalog:warm';

    protected $description = 'Bangun ulang cache katalog e-commerce (produk & kategori) dari CLI';

    public function handle(EcommerceCatalogCache $cache): int
    {
        // Bangun di proses ini juga, jangan ditunda ke terminating: di CLI tidak
        // ada response yang perlu didahulukan.
        config(['services.website.catalog_cache_defer_rebuild' => false]);

        $targets = [
            'products:index:json' => fn () => app(EcommerceProductController::class)->index(),
            'categories:index:flat:json' => fn () => app(EcommerceProductCategoryController::class)
                ->index(new Request()),
            'categories:index:tree:json' => fn () => app(EcommerceProductCategoryController::class)
                ->index(new Request(['tree' => 1])),
        ];

        $failed = 0;

        foreach ($targets as $key => $build) {
            $startedAt = microtime(true);

            // Penanda segarnya dibuang supaya remember() benar-benar membangun
            // ulang. Salinan lamanya sengaja dibiarkan: request web tetap
            // terlayani selama proses ini berjalan.
            $cache->stale($key);

            try {
                $build();

                $this->info(sprintf('%s dibangun dalam %.1f detik', $key, microtime(true) - $startedAt));
            } catch (Throwable $e) {
                $failed++;

                $this->error(sprintf('%s gagal: %s', $key, $e->getMessage()));

                Log::channel('performance')->error('performance.catalog_warm_failed', [
                    'key' => $key,
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
