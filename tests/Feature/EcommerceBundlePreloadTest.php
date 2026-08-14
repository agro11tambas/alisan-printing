<?php

namespace Tests\Feature;

use App\Services\EcommercePricingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Katalog toko online memanggil bundleForPair() untuk tiap kombinasi varian,
 * dan tiap panggilan menembak satu query berisi lima eager load. Satu request
 * /api/v1/ecommerce/products tercatat 656 query di produksi pada 5 Agustus 2026,
 * dan endpoint itu dipanggil terus-menerus oleh pengunjung website sehingga
 * ikut membebani ERP.
 *
 * preloadBundlesForPairs() memuat semuanya sekaligus. Test ini menjaga agar
 * hasilnya tetap sama dan tidak ada query susulan per pasangan.
 */
class EcommerceBundlePreloadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
        $this->seedBundles();
    }

    protected function tearDown(): void
    {
        $tables = [
            'product_bundle_unit_prices',
            'product_bundle_unit_conversions',
            'product_bundle_items',
            'product_bundles',
            'products',
            'product_units',
            'price_modes',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_preloading_resolves_every_pair_without_further_queries(): void
    {
        $service = new EcommercePricingService();

        $service->preloadBundlesForPairs([[1, 2], [3, 4]]);

        $queries = $this->countQueries(function () use ($service) {
            $this->assertNotNull($service->bundleForPair(1, 2));
            $this->assertNotNull($service->bundleForPair(3, 4));
        });

        $this->assertSame(0, $queries, "Masih ada {$queries} query susulan setelah preload.");
    }

    /**
     * Pasangan tanpa bundle harus ditandai juga, kalau tidak setiap
     * pemanggilan berikutnya akan menanyakannya lagi ke database.
     */
    public function test_a_pair_without_a_bundle_is_remembered_as_empty(): void
    {
        $service = new EcommercePricingService();

        $service->preloadBundlesForPairs([[1, 99]]);

        $queries = $this->countQueries(function () use ($service) {
            $this->assertNull($service->bundleForPair(1, 99));
            $this->assertNull($service->bundleForPair(1, 99));
        });

        $this->assertSame(0, $queries, 'Pasangan tanpa bundle masih ditanyakan ulang ke database.');
    }

    public function test_it_returns_the_same_bundle_as_the_unpreloaded_path(): void
    {
        $withPreload = new EcommercePricingService();
        $withPreload->preloadBundlesForPairs([[1, 2]]);

        $withoutPreload = new EcommercePricingService();

        $this->assertSame(
            $withoutPreload->bundleForPair(1, 2)?->id,
            $withPreload->bundleForPair(1, 2)?->id,
            'Bundle hasil preload berbeda dengan hasil query satu per satu.'
        );
    }

    /**
     * Preload harus mencocokkan pasangan persis, bukan "primary ada di daftar A
     * DAN secondary ada di daftar B". Dengan pencocokan longgar, meminta
     * (1,2) dan (3,4) ikut menarik bundle (1,4) — dan di endpoint katalog,
     * daftar A dan B berisi hampir seluruh produk sehingga hampir semua bundle
     * ikut terhidrasi lengkap dengan eager load-nya.
     */
    public function test_preloading_does_not_hydrate_pairs_that_were_not_asked_for(): void
    {
        DB::table('product_bundles')->insert(['id' => 30, 'name' => 'Gelas + Segel']);
        DB::table('product_bundle_items')->insert([
            ['bundle_id' => 30, 'product_id' => 1, 'role' => 'primary'],
            ['bundle_id' => 30, 'product_id' => 4, 'role' => 'secondary'],
        ]);

        $service = new EcommercePricingService();

        $service->preloadBundlesForPairs([[1, 2], [3, 4]]);

        $queries = $this->countQueries(function () use ($service) {
            $service->bundleForPair(1, 4);
        });

        $this->assertGreaterThan(
            0,
            $queries,
            'Bundle (1,4) ikut dimuat padahal tidak diminta: preload masih memakai perkalian silang.'
        );
    }

    public function test_a_pair_not_included_in_the_preload_still_works(): void
    {
        $service = new EcommercePricingService();

        $service->preloadBundlesForPairs([[1, 2]]);

        $this->assertNotNull(
            $service->bundleForPair(3, 4),
            'Pasangan di luar daftar preload harus tetap bisa diambil lewat query.'
        );
    }

    private function countQueries(callable $callback): int
    {
        $count = 0;

        DB::listen(function () use (&$count): void {
            $count++;
        });

        $callback();

        return $count;
    }

    private function createSchema(): void
    {
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_bundles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('base_unit_id')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('sale_price', 15, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_bundle_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bundle_id');
            $table->unsignedBigInteger('product_id');
            $table->string('role')->nullable();
            $table->integer('quantity')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        // Dibutuhkan eager load 'unitConversions.prices.priceMode'.
        Schema::create('price_modes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_bundle_unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_bundle_id');
            $table->unsignedBigInteger('unit_id');
            $table->decimal('conversion_value', 15, 2)->default(1);
            $table->decimal('sale_price', 15, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('product_bundle_unit_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_bundle_unit_conversion_id');
            $table->unsignedBigInteger('price_mode_id');
            $table->decimal('fixed_cost', 15, 2)->default(0);
            $table->decimal('margin', 15, 2)->default(0);
            $table->decimal('sale_price', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    private function seedBundles(): void
    {
        DB::table('products')->insert([
            ['id' => 1, 'name' => 'Gelas'],
            ['id' => 2, 'name' => 'Tutup'],
            ['id' => 3, 'name' => 'Botol'],
            ['id' => 4, 'name' => 'Segel'],
        ]);

        DB::table('product_bundles')->insert([
            ['id' => 10, 'name' => 'Gelas + Tutup'],
            ['id' => 20, 'name' => 'Botol + Segel'],
        ]);

        DB::table('product_bundle_items')->insert([
            ['bundle_id' => 10, 'product_id' => 1, 'role' => 'primary'],
            ['bundle_id' => 10, 'product_id' => 2, 'role' => 'secondary'],
            ['bundle_id' => 20, 'product_id' => 3, 'role' => 'primary'],
            ['bundle_id' => 20, 'product_id' => 4, 'role' => 'secondary'],
        ]);
    }
}
