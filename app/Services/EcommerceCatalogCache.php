<?php

namespace App\Services;

use App\Exceptions\CatalogCacheWarmingException;
use Closure;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cache jawaban API katalog publik (/api/ecommerce/products).
 *
 * Endpoint itu membangun seluruh katalog dari nol tiap kali dipanggil: semua
 * produk aktif, tiap grup varian, tiap opsi beserta produk ERP dan seluruh
 * harga per unit, lalu tiap kombinasi beserta bundle-nya. Website Next.js
 * memanggilnya berkali-kali dan sering berbarengan, jadi beberapa salinan
 * katalog dibangun bersamaan di memori dan server ikut tersendat — request
 * lain (termasuk halaman ERP) menunggu di belakangnya.
 *
 * Isinya hanya berubah kalau admin menyimpan produk atau kategori, dan itu
 * sudah punya satu titik penanda: WebsiteRevalidator. Cache ini dikosongkan
 * dari sana, sehingga TTL cuma jaring pengaman, bukan sumber data basi.
 */
class EcommerceCatalogCache
{
    private const VERSION_KEY = 'ecommerce:catalog:version';

    /**
     * Umur salinan lama, terpisah dari masa segarnya.
     *
     * Sengaja panjang (default 7 hari). Kesegaran dikendalikan penanda `fresh`
     * dan nomor versi dari flush(), bukan umur salinan ini — salinan lama cuma
     * jaring pengaman supaya selalu ada yang bisa disajikan selagi katalog
     * dibangun ulang.
     *
     * Sebelumnya 12x TTL (1 jam). Efeknya: kalau website sepi satu jam,
     * salinannya hangus dan pengunjung berikutnya dapat 503, bukan halaman
     * produk. Log produksi 21 Agustus 2026 pukul 05:00 menunjukkan persis itu
     * terjadi di /api/v1/ecommerce/products.
     */
    private function staleTtl(int $ttl): int
    {
        return max($ttl * 12, (int) config('services.website.catalog_cache_stale_ttl', 604800));
    }

    public function remember(string $key, Closure $callback): mixed
    {
        $ttl = (int) config('services.website.catalog_cache_ttl', 300);

        // TTL 0 mematikan cache tanpa perlu ubah kode — untuk menelusuri
        // masalah data di produksi.
        if ($ttl <= 0) {
            return $callback();
        }

        $store = $this->store();
        $base = 'ecommerce:catalog:'.$key;
        $payloadKey = $base.':payload';
        $freshKey = $base.':fresh:v'.$this->version();

        $payload = $store->get($payloadKey);

        // Masih dalam masa segar: langsung pakai, tidak usah sentuh DB.
        if (is_array($payload) && $store->get($freshKey) !== null) {
            return $payload['value'];
        }

        // Kedaluwarsa. Hanya SATU request yang boleh membangun ulang katalog.
        // Tanpa ini, setiap kali TTL habis semua request website yang masuk
        // barengan membangun katalog penuh sendiri-sendiri, worker PHP-FPM
        // habis, dan seluruh halaman ERP ikut menunggu di belakangnya.
        $lock = $store->getStore() instanceof LockProvider
            ? $store->getStore()->lock($base.':lock', (int) config('services.website.catalog_cache_rebuild_lock', 900))
            : null;

        if ($lock === null) {
            return $this->build($store, $payloadKey, $freshKey, $ttl, $callback);
        }

        if (! $lock->get()) {
            // Jangan ikut membangun setelah timeout. Jalur lama membuat semua
            // request cache-dingin membangun katalog besar secara bersamaan.
            if (is_array($payload)) {
                return $payload['value'];
            }

            throw new CatalogCacheWarmingException;
        }

        if (! (bool) config('services.website.catalog_cache_defer_rebuild', true)) {
            try {
                return $this->build($store, $payloadKey, $freshKey, $ttl, $callback);
            } finally {
                $lock->release();
            }
        }

        // Bangun setelah response terkirim supaya request pemicu refresh tidak
        // menjadi satu-satunya pengguna yang menunggu bermenit-menit.
        $this->afterResponse(function () use ($store, $payloadKey, $freshKey, $ttl, $callback, $lock, $key): void {
            try {
                $this->build($store, $payloadKey, $freshKey, $ttl, $callback);
            } catch (Throwable $e) {
                Log::channel('performance')->error('performance.catalog_rebuild_failed', [
                    'key' => $key,
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);
            } finally {
                $lock->release();
            }
        });

        if (is_array($payload)) {
            return $payload['value'];
        }

        throw new CatalogCacheWarmingException;
    }

    private function build(Repository $store, string $payloadKey, string $freshKey, int $ttl, Closure $callback): mixed
    {
        $value = $callback();

        // Salinan disimpan jauh lebih lama dari penanda segarnya, supaya saat
        // TTL habis masih ada yang bisa disajikan selagi dibangun ulang.
        $store->put($payloadKey, ['value' => $value], $this->staleTtl($ttl));
        $store->put($freshKey, 1, $ttl);

        return $value;
    }

    /**
     * Seperti remember(), tapi yang disimpan adalah JSON yang sudah jadi.
     *
     * Ini bukan penghematan kecil. Menyimpan array PHP berarti tiap cache hit
     * harus unserialize() seluruh katalog jadi array bersarang di memori, lalu
     * json_encode() lagi jadi string untuk response. Untuk payload yang
     * ukurannya belasan MB, dua langkah itu makan detik-detikan CPU — per
     * request, bahkan saat cache-nya kena. Itu yang membuat /api/v1/ecommerce/products
     * tercatat 31–73 detik di produksi walau statusnya 200.
     *
     * Dengan JSON string, cache hit tinggal baca file lalu kirim byte-nya:
     * tidak ada array yang dibangun, tidak ada encode ulang.
     */
    public function rememberJson(string $key, Closure $callback): string
    {
        $json = $this->remember($key, function () use ($callback) {
            $encoded = json_encode($callback(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if ($encoded === false) {
                throw new \RuntimeException('Gagal encode katalog: '.json_last_error_msg());
            }

            return $encoded;
        });

        // Salinan lama dari versi sebelumnya bisa saja masih berupa array.
        return is_string($json) ? $json : (string) json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Tandai satu kunci sebagai tidak segar tanpa membuang salinannya.
     *
     * Dipakai command penghangat: request web tetap dilayani dari salinan lama
     * selagi CLI membangun yang baru.
     */
    public function stale(string $key): void
    {
        $this->store()->forget('ecommerce:catalog:'.$key.':fresh:v'.$this->version());
    }

    protected function afterResponse(Closure $callback): void
    {
        app()->terminating($callback);
    }

    /**
     * Naikkan nomor versi, bukan hapus kunci satu per satu: seluruh entri lama
     * jadi tidak terpakai sekaligus, dan file store tidak mendukung tag.
     */
    public function flush(): void
    {
        $store = $this->store();
        $current = $store->get(self::VERSION_KEY);

        // Saat kunci versi belum ada, version() sudah menganggapnya 1. Menaikkan
        // dari nol juga menghasilkan 1, jadi flush pertama setelah cache dibersihkan
        // tidak menginvalidasi apa pun dan perubahan admin baru muncul setelah TTL
        // habis. Mulai dari 2 supaya kunci lamanya benar-benar ditinggalkan.
        if ($current === null) {
            $store->forever(self::VERSION_KEY, 2);

            return;
        }

        if ($store->increment(self::VERSION_KEY) === false) {
            $store->forever(self::VERSION_KEY, (int) $current + 1);
        }
    }

    private function version(): int
    {
        return (int) $this->store()->get(self::VERSION_KEY, 1);
    }

    /**
     * Sengaja bukan store default. Default aplikasi ini `database`, dan kolom
     * `cache.value` bertipe mediumText (batas 16 MB) — payload katalog penuh
     * bisa mendekatinya, dan tiap cache hit berarti menarik megabyte lewat
     * MySQL. File store tidak punya dua masalah itu.
     */
    private function store(): Repository
    {
        return Cache::store((string) config('services.website.catalog_cache_store', 'file'));
    }
}
