<?php

namespace App\Services;

use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

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

    public function remember(string $key, Closure $callback): mixed
    {
        $ttl = (int) config('services.website.catalog_cache_ttl', 300);

        // TTL 0 mematikan cache tanpa perlu ubah kode — untuk menelusuri
        // masalah data di produksi.
        if ($ttl <= 0) {
            return $callback();
        }

        return $this->store()->remember(
            'ecommerce:catalog:v'.$this->version().':'.$key,
            $ttl,
            $callback
        );
    }

    /**
     * Naikkan nomor versi, bukan hapus kunci satu per satu: seluruh entri lama
     * jadi tidak terpakai sekaligus, dan file store tidak mendukung tag.
     */
    public function flush(): void
    {
        $store = $this->store();

        if ($store->increment(self::VERSION_KEY) === false) {
            $store->forever(self::VERSION_KEY, 1);
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
