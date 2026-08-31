<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Terima hasil pengukuran dari browser pengguna.
 *
 * Kenapa ini ada: seluruh instrumen lain di aplikasi ini mengukur dari DALAM
 * server. LogSlowRequests mencatat waktu PHP, log performa mencatat query, dan
 * semuanya konsisten menunjukkan angka di bawah satu detik. Tapi pengguna di
 * lokasi client melaporkan halaman yang sama butuh puluhan detik, dan access
 * log web server membenarkan itu.
 *
 * Selisih itu terjadi di jalur yang tidak bisa diukur dari server: DNS, TLS,
 * CDN, jaringan client, dan waktu browser merender halaman. Satu-satunya pihak
 * yang bisa mengukurnya adalah browser pengguna itu sendiri. Endpoint ini
 * menampung laporannya.
 *
 * Sengaja hanya menerima angka, bukan isi halaman: tidak ada data pesanan atau
 * pelanggan yang ikut terkirim.
 */
class ClientTimingController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'path'          => 'required|string|max:255',
            'total_ms'      => 'required|numeric|min:0|max:1800000',
            'dns_ms'        => 'nullable|numeric|min:0|max:1800000',
            'tcp_ms'        => 'nullable|numeric|min:0|max:1800000',
            'tls_ms'        => 'nullable|numeric|min:0|max:1800000',
            'ttfb_ms'       => 'nullable|numeric|min:0|max:1800000',
            'download_ms'   => 'nullable|numeric|min:0|max:1800000',
            'dom_ready_ms'  => 'nullable|numeric|min:0|max:1800000',
            'resource_count' => 'nullable|integer|min:0|max:2000',
            'slowest_resource' => 'nullable|string|max:300',
            'slowest_resource_ms' => 'nullable|numeric|min:0|max:1800000',
            'connection'    => 'nullable|string|max:50',
            'downlink_mbps' => 'nullable|numeric|min:0|max:10000',
        ]);

        // Ambangnya dari config supaya bisa diturunkan sementara lewat
        // CLIENT_TIMING_LOG_MS saat menelusuri, tanpa mengubah kode.
        if ((float) $data['total_ms'] < (float) config('app.client_timing_log_ms', 3000)) {
            return response()->noContent();
        }

        Log::channel('performance')->warning('performance.client_timing', $data + [
            'user_id' => $request->user()?->getAuthIdentifier(),
            'ip' => $request->ip(),
        ]);

        return response()->noContent();
    }
}
