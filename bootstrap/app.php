<?php

use App\Http\Middleware\CheckPermission;
use Illuminate\Foundation\Application;
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
        $middleware->alias([
            'permission' => CheckPermission::class,
            'subpermission' => SubPermissionMiddleware::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'web.auth' => \App\Http\Middleware\isLogin::class,
            'check.session' => \App\Http\Middleware\CheckSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            return redirect('/')
                ->with('error', 'Session expired, silakan login kembali.');
        });
    })->create();
