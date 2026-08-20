<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Jalankan semua endpoint daftar ERP yang aman dibaca melalui controllernya.
 *
 * Route middleware sengaja tidak dijalankan: command ini memakai user hanya
 * agar filter berbasis role tetap sama, tanpa membuat session HTTP palsu.
 * Target dibatasi ke GET /erp/.../data*, atau .../summary tanpa parameter,
 * sehingga command tidak pernah memanggil endpoint tulis, export, atau cetak.
 */
class BenchmarkListQueries extends Command
{
    protected $signature = 'app:benchmark-lists
        {--rows=15 : Jumlah baris yang diminta dari setiap daftar}
        {--user= : ID user untuk filter berbasis role (default: Owner pertama)}
        {--threshold=1000 : Ambang lambat dalam milidetik}
        {--json : Keluarkan hasil lengkap sebagai JSON}';

    protected $description = 'Audit read-only seluruh endpoint data/summary ERP, termasuk controller dan render JSON';

    public function __construct(private readonly Router $router)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $rows = max(1, min(100, (int) $this->option('rows')));
        $threshold = max(1, (int) $this->option('threshold'));
        $user = $this->benchmarkUser();

        if (! $user) {
            $this->error('Tidak ada user untuk menjalankan filter endpoint ERP. Gunakan --user=ID.');

            return self::FAILURE;
        }

        $routes = $this->benchmarkRoutes();
        if ($routes === []) {
            $this->error('Tidak ada endpoint data/summary ERP yang ditemukan.');

            return self::FAILURE;
        }

        $previousRequest = app()->bound('request') ? app('request') : null;
        $guard = Auth::guard();
        $previousUser = $guard->user();
        $guard->setUser($user);
        $results = [];

        try {
            foreach ($routes as $route) {
                $results[] = $this->measureRoute($route, $user, $rows, $threshold);
            }
        } finally {
            DB::connection()->disableQueryLog();
            if ($previousRequest) {
                app()->instance('request', $previousRequest);
            }
            if ($previousUser) {
                $guard->setUser($previousUser);
            } else {
                $guard->forgetUser();
            }
        }

        usort($results, fn (array $a, array $b) => $b['duration_ms'] <=> $a['duration_ms']);

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'generated_at' => now()->toIso8601String(),
                'user_id' => $user->getKey(),
                'rows' => $rows,
                'threshold_ms' => $threshold,
                'endpoints' => $results,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->renderTable($results, $user, $rows, $threshold);
        }

        return collect($results)->contains(fn (array $result) => $result['error'] !== null)
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function benchmarkUser(): ?User
    {
        $requestedId = $this->option('user');
        if ($requestedId !== null && $requestedId !== '') {
            return User::query()->find($requestedId);
        }

        return User::query()->where('role', 'Owner')->first()
            ?? User::query()->first();
    }

    /** @return list<Route> */
    private function benchmarkRoutes(): array
    {
        $routes = [];
        foreach ($this->router->getRoutes()->getRoutes() as $route) {
            $uri = $route->uri();
            $lastSegment = Str::afterLast($uri, '/');

            if (! in_array('GET', $route->methods(), true)
                || ! Str::startsWith($uri, 'erp/')
                || Str::contains($uri, '{')
                || ! preg_match('/^(data.*|summary)$/', $lastSegment)) {
                continue;
            }
            $routes[] = $route;
        }

        usort($routes, fn (Route $a, Route $b) => $a->uri() <=> $b->uri());

        return $routes;
    }

    /** @return array<string, int|float|string|null> */
    private function measureRoute(Route $originalRoute, User $user, int $rows, int $threshold): array
    {
        $route = clone $originalRoute;
        $request = Request::create('/'.$route->uri(), 'GET', [
            'draw' => 1,
            'start' => 0,
            'length' => $rows,
            'search' => ['value' => '', 'regex' => false],
        ]);
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => $user);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        app()->instance('request', $request);

        $connection = DB::connection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();
        $startedAt = hrtime(true);
        $error = null;
        $status = 200;
        $bytes = 0;

        try {
            $response = $route->run();
            $response = $response instanceof Response
                ? $response
                : app(ResponseFactory::class)->make($response);
            $status = $response->getStatusCode();
            $bytes = strlen((string) $response->getContent());
        } catch (Throwable $e) {
            $status = 500;
            $error = $e::class.': '.$e->getMessage();
        }

        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
        $queries = $connection->getQueryLog();
        $connection->disableQueryLog();
        $connection->flushQueryLog();
        $databaseMs = array_sum(array_column($queries, 'time'));
        $module = explode('/', $route->uri())[1] ?? 'erp';

        return [
            'module' => $module,
            'uri' => '/'.$route->uri(),
            'status' => $status,
            'duration_ms' => round($durationMs, 2),
            'database_ms' => round($databaseMs, 2),
            'application_ms' => round(max(0, $durationMs - $databaseMs), 2),
            'queries' => count($queries),
            'bytes' => $bytes,
            'result' => $error !== null ? 'ERROR' : ($durationMs >= $threshold ? 'LAMBAT' : 'OK'),
            'error' => $error,
        ];
    }

    /** @param list<array<string, int|float|string|null>> $results */
    private function renderTable(array $results, User $user, int $rows, int $threshold): void
    {
        $this->info(sprintf(
            'Audit %d endpoint ERP read-only sebagai %s (#%s), %d baris per endpoint.',
            count($results), $user->name, $user->getKey(), $rows
        ));
        $this->line("Ambang lambat: {$threshold} ms. Hasil diurutkan dari yang paling lama.");
        $this->newLine();

        $this->table(
            ['Hasil', 'Endpoint', 'Total', 'DB', 'App', 'Query', 'Ukuran', 'HTTP'],
            array_map(fn (array $result) => [
                $result['result'],
                $result['uri'],
                $this->formatMs((float) $result['duration_ms']),
                $this->formatMs((float) $result['database_ms']),
                $this->formatMs((float) $result['application_ms']),
                $result['queries'],
                number_format((int) $result['bytes'] / 1024, 1, ',', '.').' KB',
                $result['status'],
            ], $results)
        );

        $errors = array_values(array_filter($results, fn (array $result) => $result['error'] !== null));
        $slow = array_values(array_filter(
            $results,
            fn (array $result) => $result['error'] === null && $result['duration_ms'] >= $threshold
        ));

        $this->newLine();
        $this->line(sprintf(
            'Ringkasan: %d OK, %d lambat, %d error.',
            count($results) - count($slow) - count($errors), count($slow), count($errors)
        ));
        foreach ($errors as $result) {
            $this->error($result['uri'].' — '.$result['error']);
        }
    }

    private function formatMs(float $ms): string
    {
        return $ms >= 1000
            ? number_format($ms / 1000, 2, ',', '.').' s'
            : number_format($ms, 0, ',', '.').' ms';
    }
}
