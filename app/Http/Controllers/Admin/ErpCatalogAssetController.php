<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ErpCatalogPayload;
use Illuminate\Http\Request;

/**
 * Sajikan katalog produk sebagai file JavaScript tersendiri.
 *
 * Sebelumnya halaman input order menanam seluruh katalog ke dalam HTML-nya
 * (`const products = {!! $productsJson !!}`). Laporan waktu muat dari browser
 * pengguna 3 September 2026 menunjukkan akibatnya: halaman order menghabiskan
 * 672–975 ms hanya untuk mengunduh HTML, sementara halaman lain 10–127 ms.
 * Dan karena tertanam di HTML, katalog itu diunduh ULANG setiap kali kasir
 * membuka form order, padahal isinya sama persis.
 *
 * Sebagai file terpisah, katalog:
 *   - diunduh sekali, lalu dipakai ulang dari cache browser di semua halaman
 *   - tidak lagi menggelembungkan HTML tiap halaman
 *
 * URL-nya memuat cap versi katalog, jadi boleh di-cache selamanya: begitu ada
 * produk atau harga yang berubah, cap versinya berubah, URL-nya ikut berubah,
 * dan browser mengambil yang baru. Tidak ada risiko harga basi.
 */
class ErpCatalogAssetController extends Controller
{
    /**
     * Slug URL -> kunci blob. Sengaja daftar putih: tanpa ini, alamat ini bisa
     * dipakai membaca isi cache mana pun dengan menebak nama kunci.
     */
    private const PETA = [
        'sale-list-create-products' => 'sale-list:create:products',
        'sale-list-create-bundles'  => 'sale-list:create:bundles',
    ];

    public function show(Request $request, string $slug)
    {
        $kunci = self::PETA[$slug] ?? null;

        abort_if($kunci === null, 404);

        $json = app(ErpCatalogPayload::class)->readJson($kunci);

        // Blob belum ada di cache. Ini semestinya tidak terjadi: halaman yang
        // memuat file ini baru saja membangunnya. Kalau tetap terjadi (cache
        // dibersihkan tepat di antara keduanya), muat ulang halaman sekali
        // supaya blobnya dibangun lagi — sekali, tidak berulang.
        if ($json === null) {
            return $this->js(
                "if (!sessionStorage.getItem('erpCatalogReload')) {"
                ."sessionStorage.setItem('erpCatalogReload','1');location.reload();}",
                cacheable: false
            );
        }

        $nama = json_encode($slug);

        return $this->js(
            "window.__erpCatalog = window.__erpCatalog || {};"
            ."window.__erpCatalog[{$nama}] = {$json};",
            cacheable: true
        );
    }

    private function js(string $isi, bool $cacheable)
    {
        return response($isi, 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            // Aman disimpan selamanya karena URL-nya memuat cap versi katalog.
            'Cache-Control' => $cacheable
                ? 'public, max-age=31536000, immutable'
                : 'no-store',
        ]);
    }
}
