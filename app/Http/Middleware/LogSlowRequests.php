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
                'query_count' => $queryCount,
                'user_id' => $request->user()?->getAuthIdentifier(),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1_048_576, 2),
            ]);
        }

        return $response;
    }
}

