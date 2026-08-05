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

        DB::listen(function (QueryExecuted $query) use (&$queryCount, &$databaseTimeMs): void {
            $queryCount++;
            $databaseTimeMs += $query->time;
        });

        $response = $next($request);
        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
        $thresholdMs = (float) config('app.slow_request_log_ms', 1000);

        if ($durationMs >= $thresholdMs) {
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
                'user_id' => $request->user()?->getAuthIdentifier(),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1_048_576, 2),
            ]);
        }

        return $response;
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
