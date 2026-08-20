<?php

namespace Tests\Feature;

use App\Exceptions\CatalogCacheWarmingException;
use App\Services\EcommerceCatalogCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EcommerceCatalogCacheTest extends TestCase
{
    private function cache(): EcommerceCatalogCache
    {
        return new EcommerceCatalogCache;
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.website.catalog_cache_ttl' => 300,
            'services.website.catalog_cache_store' => 'array',
            'services.website.catalog_cache_defer_rebuild' => false,
        ]);

        Cache::store('array')->clear();
    }

    public function test_hasil_pertama_dipakai_ulang_tanpa_membangun_ulang(): void
    {
        $builds = 0;
        $build = function () use (&$builds) {
            $builds++;

            return ['katalog'];
        };

        $this->assertSame(['katalog'], $this->cache()->remember('products:index', $build));
        $this->assertSame(['katalog'], $this->cache()->remember('products:index', $build));
        $this->assertSame(['katalog'], $this->cache()->remember('products:index', $build));

        $this->assertSame(1, $builds);
    }

    public function test_nilai_null_ikut_disimpan(): void
    {
        $builds = 0;
        $build = function () use (&$builds) {
            $builds++;

            return null;
        };

        $this->assertNull($this->cache()->remember('products:show:tidak-ada', $build));
        $this->assertNull($this->cache()->remember('products:show:tidak-ada', $build));

        $this->assertSame(1, $builds);
    }

    public function test_salinan_lama_disajikan_saat_penanda_segar_habis_dan_ada_yang_membangun(): void
    {
        $this->cache()->remember('products:index', fn () => ['lama']);

        $store = Cache::store('array');
        $freshKey = collect($this->keys($store))->first(fn ($k) => str_contains($k, ':fresh:v'));
        $this->assertNotNull($freshKey, 'penanda segar harus ada');

        // Tiru masa segar yang habis, sementara request lain memegang lock.
        $store->forget($freshKey);
        $base = strstr($freshKey, ':fresh:v', true);
        $otherRequestLock = $store->getStore()->lock($base.':lock', 120);
        $this->assertTrue($otherRequestLock->get());

        $builds = 0;
        $value = $this->cache()->remember('products:index', function () use (&$builds) {
            $builds++;

            return ['baru'];
        });

        $this->assertSame(['lama'], $value, 'harus menyajikan salinan lama, bukan menunggu');
        $this->assertSame(0, $builds, 'tidak boleh ikut membangun ulang');

        $otherRequestLock->release();
    }

    public function test_flush_membuat_katalog_dibangun_ulang(): void
    {
        $this->assertSame(['v1'], $this->cache()->remember('products:index', fn () => ['v1']));

        $this->cache()->flush();

        $this->assertSame(['v2'], $this->cache()->remember('products:index', fn () => ['v2']));
    }

    public function test_flush_tetap_menyediakan_payload_lama_selama_rebuild(): void
    {
        $this->assertSame(['v1'], $this->cache()->remember('products:index', fn () => ['v1']));
        $this->cache()->flush();

        $store = Cache::store('array');
        $lock = $store->getStore()->lock('ecommerce:catalog:products:index:lock', 900);
        $this->assertTrue($lock->get());

        $builds = 0;
        $value = $this->cache()->remember('products:index', function () use (&$builds) {
            $builds++;

            return ['v2'];
        });

        $this->assertSame(['v1'], $value);
        $this->assertSame(0, $builds);
        $lock->release();
    }

    public function test_cache_dingin_hanya_menjadwalkan_satu_rebuild_dan_request_lain_gagal_cepat(): void
    {
        config(['services.website.catalog_cache_defer_rebuild' => true]);

        $cache = new class extends EcommerceCatalogCache
        {
            public array $callbacks = [];

            protected function afterResponse(\Closure $callback): void
            {
                $this->callbacks[] = $callback;
            }
        };

        $builds = 0;
        $builder = function () use (&$builds) {
            $builds++;

            return ['katalog'];
        };

        try {
            $cache->remember('products:index', $builder);
            $this->fail('Cache dingin harus merespons cepat sambil menjadwalkan rebuild.');
        } catch (CatalogCacheWarmingException) {
            $this->assertCount(1, $cache->callbacks);
        }

        $this->expectException(CatalogCacheWarmingException::class);
        try {
            $cache->remember('products:index', $builder);
        } finally {
            $this->assertSame(0, $builds);
            $this->assertCount(1, $cache->callbacks);
            ($cache->callbacks[0])();
            $this->assertSame(1, $builds);
            $this->assertSame(['katalog'], $cache->remember('products:index', $builder));
        }
    }

    public function test_ttl_nol_mematikan_cache(): void
    {
        config(['services.website.catalog_cache_ttl' => 0]);

        $builds = 0;
        $build = function () use (&$builds) {
            $builds++;

            return ['katalog'];
        };

        $this->cache()->remember('products:index', $build);
        $this->cache()->remember('products:index', $build);

        $this->assertSame(2, $builds);
    }

    /** @return array<int, string> */
    private function keys($store): array
    {
        $reflection = new \ReflectionProperty($store->getStore(), 'storage');
        $reflection->setAccessible(true);

        return array_keys($reflection->getValue($store->getStore()));
    }
}
