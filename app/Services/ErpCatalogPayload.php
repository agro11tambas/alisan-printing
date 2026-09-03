<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Simpan blob katalog yang ditanam ke halaman ERP, dalam bentuk JSON jadi.
 *
 * Halaman order dan purchase menanam seluruh katalog produk ke HTML-nya
 * (`const products = @json($productsJson)`). Tanpa ini, tiap kali halaman
 * dibuka aplikasi mengulang seluruh rantai: query katalog, hidrasi model
 * Eloquent, petakan jadi array bersarang, lalu json_encode ke HTML. Untuk
 * katalog sebesar milik produksi, itu detik-detikan CPU per page load — dan
 * karena jatah proses PHP di shared hosting sedikit, request lain ikut
 * mengantre di belakangnya.
 *
 * Yang disimpan sengaja string JSON, bukan array: cache hit jadi sekadar baca
 * lalu cetak, tanpa membangun array dan tanpa encode ulang.
 *
 * Kesegarannya tidak mengandalkan TTL. Kunci cache memuat cap versi katalog
 * (waktu ubah terakhir + jumlah baris). Begitu ada produk, bundle, atau diskon
 * yang berubah, cap-nya berubah, kuncinya ikut berubah, dan blob dibangun
 * ulang. Jadi harga di layar input order tidak pernah basi.
 */
class ErpCatalogPayload
{
    /** @var array<string, string> */
    private array $memo = [];

    private ?string $version = null;

    /**
     * @param  Closure(): mixed  $build  Menghasilkan struktur yang mau di-encode.
     */
    public function json(string $key, Closure $build): string
    {
        $cacheKey = $this->cacheKey($key);

        // Beberapa halaman memakai blob yang sama dua kali dalam satu request.
        if (isset($this->memo[$cacheKey])) {
            return $this->memo[$cacheKey];
        }

        $ttl = (int) config('services.website.erp_payload_ttl', 3600);

        $json = Cache::remember($cacheKey, $ttl, function () use ($build) {
            $encoded = json_encode($build(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if ($encoded === false) {
                throw new \RuntimeException('Gagal encode payload katalog ERP: '.json_last_error_msg());
            }

            return $encoded;
        });

        return $this->memo[$cacheKey] = $json;
    }

    /**
     * Baca blob yang sudah tersimpan tanpa membangunnya kalau belum ada.
     *
     * Dipakai endpoint yang menyajikan katalog sebagai file JavaScript
     * tersendiri. Endpoint itu tidak boleh membangun katalog: halaman yang
     * memanggilnya baru saja membangunnya beberapa milidetik sebelumnya, dan
     * membangun ulang di request web adalah persis biaya yang mau dihindari.
     */
    public function readJson(string $key): ?string
    {
        $json = Cache::get($this->cacheKey($key));

        return is_string($json) ? $json : null;
    }

    /**
     * Satu-satunya tempat kunci cache dibentuk.
     *
     * json() dan readJson() HARUS memakai kunci yang sama persis. Sempat tidak:
     * readJson() melewatkan penanda "v2" dan akibatnya tidak pernah menemukan
     * blob yang baru saja ditulis json().
     *
     * v2: bentuk payload diskon berubah (apply_on jamak + daftar target).
     * Naikkan angkanya tiap kali struktur blob berubah, supaya cache lama tidak
     * ikut terpakai setelah deploy — cap versi katalog hanya mengikuti perubahan
     * data, bukan perubahan kode.
     */
    private function cacheKey(string $key): string
    {
        return 'erp:catalog-payload:v2:'.$key.':'.$this->version();
    }

    /**
     * Cap versi katalog.
     *
     * Dihitung sekali per request. Query-nya agregat murni (MAX + COUNT), jadi
     * biayanya tidak ikut tumbuh sebesar isi katalognya — beda jauh dengan
     * menarik seluruh baris beserta relasinya.
     */
    public function version(): string
    {
        if ($this->version !== null) {
            return $this->version;
        }

        $parts = [];

        foreach (['products', 'product_bundles', 'discounts', 'product_unit_conversions'] as $table) {
            $row = DB::table($table)
                ->selectRaw('COUNT(*) as jumlah, MAX(updated_at) as terakhir')
                ->first();

            $parts[] = ($row->jumlah ?? 0).'-'.($row->terakhir ?? '');
        }

        // Dipendekkan jadi hash supaya kunci cache tidak kepanjangan.
        return $this->version = substr(md5(implode('|', $parts)), 0, 12);
    }
}
