<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SubPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, $subPermissionSlug)
    {
        $user = Auth::user();

        // kalau belum login
        if (!$user) {
            abort(403, 'Unauthorized');
        }

        // cek sub permission
        if (!$user->hasSubPermission($subPermissionSlug)) {
            abort(403, 'Anda tidak punya akses ke halaman ini');
        }

        return $next($request);
    }
}
