<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogSlowRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        // Diukur di sini, sebelum request diproses: ini biaya boot framework,
        // bukan biaya halamannya.
        $bootstrapMs = $this->bootstrapMs();

        $startedAt = hrtime(true);
        $queryCount = 0;
        $databaseTimeMs = 0.0;
        $slowest = [];
        $shapes = [];

        DB::listen(function (QueryExecuted $query) use (&$queryCount, &$databaseTimeMs, &$slowest, &$shapes): void {
            $queryCount++;
            $databaseTimeMs += $query->time;

            $sql = $this->shorten($query->sql);

            // Query paling lambat: cukup simpan lima teratas supaya memori tidak
            // ikut membengkak di request yang menembak ribuan query.
            $slowest[] = ['sql' => $sql, 'ms' => round($query->time, 2)];
            if (count($slowest) > 25) {
                usort($slowest, fn ($a, $b) => $b['ms'] <=> $a['ms']);
                $slowest = array_slice($slowest, 0, 5);
            }

            // Query yang bentuknya sama dan diulang-ulang: itu tanda N+1.
            // Dibatasi 200 bentuk supaya tidak jadi beban sendiri.
            $shape = preg_replace('/\d+/', '?', $sql);
            if (isset($shapes[$shape]) || count($shapes) < 200) {
                $shapes[$shape] = ($shapes[$shape] ?? 0) + 1;
            }
        });

        $response = $next($request);
        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
        $thresholdMs = (float) config('app.slow_request_log_ms', 1000);

        $this->watchWorkAfterResponse($request, $durationMs, $thresholdMs);

        if ($durationMs >= $thresholdMs) {
            usort($slowest, fn ($a, $b) => $b['ms'] <=> $a['ms']);
            arsort($shapes);

            Log::channel('performance')->warning('performance.slow_request', [
                'method' => $request->method(),
                'path' => $request->path(),
                'route' => $request->route()?->getName(),
                'status' => $response->getStatusCode(),
                'duration_ms' => round($durationMs, 2),
                'database_ms' => round($databaseTimeMs, 2),
                'application_ms' => round(max(0, $durationMs - $databaseTimeMs), 2),
                'bootstrap_ms' => $bootstrapMs,
                'load_avg' => $this->loadAverage(),
                'query_count' => $queryCount,
                // Query paling lambat di request ini.
                'slowest_queries' => array_slice($slowest, 0, 5),
                // Bentuk query yang paling sering diulang. Angka tinggi di sini
                // berarti N+1: satu query yang dijalankan ratusan kali.
                'repeated_queries' => array_slice(
                    array_map(
                        fn ($shape, $count) => ['sql' => $shape, 'count' => $count],
                        array_keys($shapes),
                        array_values($shapes)
                    ),
                    0,
                    5
                ),
                'user_id' => $request->user()?->getAuthIdentifier(),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1_048_576, 2),
            ]);
        }

        return $response;
    }

    /**
     * Catat pekerjaan yang jalan SETELAH response terkirim.
     *
     * Ini titik buta yang sempat menyesatkan penelusuran berhari-hari pada
     * Agustus 2026. Middleware ini berhenti mengukur begitu $next() kembali,
     * padahal Laravel masih menjalankan terminating callback sesudahnya —
     * dan selama itu proses PHP tetap terpakai penuh. Akibatnya sebuah request
     * bisa tercatat "cepat" di sini sementara access log web server mencatatnya
     * puluhan detik, karena access log menghitung sampai prosesnya benar-benar
     * bebas.
     *
     * register_shutdown_function() jalan paling akhir, sesudah semua terminating
     * callback selesai, jadi selisihnya terlihat.
     */
    private function watchWorkAfterResponse(Request $request, float $responseMs, float $thresholdMs): void
    {
        $path = $request->path();
        $route = $request->route()?->getName();

        register_shutdown_function(function () use ($path, $route, $responseMs, $thresholdMs): void {
            if (! defined('LARAVEL_START')) {
                return;
            }

            $totalMs = (microtime(true) - LARAVEL_START) * 1000;
            $afterResponseMs = $totalMs - $responseMs;

            // Yang menarik cuma pekerjaan sesudah response. Request yang memang
            // lambat sedari awal sudah dicatat performance.slow_request.
            if ($afterResponseMs < $thresholdMs) {
                return;
            }

            Log::channel('performance')->warning('performance.work_after_response', [
                'path' => $path,
                'route' => $route,
                // Waktu sampai response terkirim ke pengguna.
                'response_ms' => round($responseMs, 2),
                // Waktu yang dihabiskan SESUDAH itu, saat proses PHP masih
                // terpakai tapi pengguna sudah dapat halamannya.
                'after_response_ms' => round($afterResponseMs, 2),
                // Angka inilah yang dilihat access log web server.
                'total_ms' => round($totalMs, 2),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1_048_576, 2),
            ]);
        });
    }

    /**
     * SQL dipendekkan sebelum dicatat: log ini untuk mengenali query mana yang
     * berat, bukan untuk menyimpan isi query lengkap.
     */
    private function shorten(string $sql): string
    {
        $sql = preg_replace('/\s+/', ' ', trim($sql));

        return mb_strlen($sql) > 300 ? mb_substr($sql, 0, 300).' …' : $sql;
    }

    /**
     * Waktu dari PHP mulai (public/index.php) sampai middleware ini jalan:
     * autoload Composer, boot framework, dan baca config.
     *
     * Kalau angka ini yang membengkak saat ERP tersendat, penyebabnya bukan
     * query atau kode halaman, melainkan disk/CPU server yang sedang sibuk.
     */
    private function bootstrapMs(): ?float
    {
        if (! defined('LARAVEL_START')) {
            return null;
        }

        return round((microtime(true) - LARAVEL_START) * 1000, 2);
    }

    /**
     * Beban server 1 menit terakhir. Di shared hosting angka ini ikut naik
     * karena akun lain, jadi berguna untuk memisahkan "aplikasi kita berat"
     * dari "servernya memang sedang penuh".
     */
    private function loadAverage(): ?float
    {
        if (! function_exists('sys_getloadavg')) {
            return null;
        }

        $load = sys_getloadavg();

        return is_array($load) && isset($load[0]) ? round((float) $load[0], 2) : null;
    }
}
