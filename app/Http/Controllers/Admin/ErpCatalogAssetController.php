<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ErpCatalogBlobs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
    private const PETA = ErpCatalogBlobs::PETA_SLUG;

    public function show(Request $request, string $slug, ErpCatalogBlobs $blobs)
    {
        $kunci = self::PETA[$slug] ?? null;

        abort_if($kunci === null, 404);

        // Idealnya blob sudah ada di cache: halaman yang memuat file ini baru
        // saja membangunnya. Kalau ternyata tidak ada (cache dibersihkan, TTL
        // habis, deploy baru, atau cache store beda proses), file ini WAJIB
        // tetap membangunnya sendiri.
        //
        // Versi sebelumnya menjawab kondisi itu dengan skrip "reload sekali".
        // Skrip itu tidak mendefinisikan window.__erpCatalog, jadi begitu
        // reload-nya tidak menolong, baris pertama skrip halaman input order
        // (`window.__erpCatalog['...']`) melempar TypeError dan seluruh blok
        // <script> halaman itu berhenti — semua tombolnya mati tanpa pesan.
        // Membangun ulang di sini memang membuat satu request jadi lambat;
        // itu jauh lebih murah daripada halaman yang tidak bisa dipakai.
        try {
            $json = $blobs->json($kunci);
        } catch (\Throwable $e) {
            Log::error('Gagal membangun blob katalog ERP', [
                'kunci' => $kunci,
                'message' => $e->getMessage(),
            ]);

            $json = null;
        }

        $nama = json_encode($slug);

        // Bahkan saat gagal, window.__erpCatalog harus tetap ada dan berisi
        // array kosong untuk slug ini, supaya halaman pemakainya tidak mati
        // total dan bisa menampilkan pesan yang benar.
        if ($json === null) {
            return $this->js(
                'window.__erpCatalog = window.__erpCatalog || {};'
                ."window.__erpCatalog[{$nama}] = [];"
                ."window.__erpCatalogGagal = true;",
                cacheable: false
            );
        }

        return $this->js(
            'window.__erpCatalog = window.__erpCatalog || {};'
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
