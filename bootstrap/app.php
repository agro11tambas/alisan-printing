<?php

use App\Http\Middleware\CheckPermission;
use Illuminate\Foundation\Application;
use App\Http\Middleware\LogSlowRequests;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\SubPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(LogSlowRequests::class);

        $middleware->alias([
            'permission' => CheckPermission::class,
            'subpermission' => SubPermissionMiddleware::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'web.auth' => \App\Http\Middleware\isLogin::class,
            'check.session' => \App\Http\Middleware\CheckSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        });

        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Session expired, silakan login kembali.',
                    'redirect' => '/login',
                ], 419);
            }

            return redirect()->route('login')
                ->with('logout_notice', 'Sesi Anda telah berakhir. Silakan login kembali.');
        });
    })->create();
