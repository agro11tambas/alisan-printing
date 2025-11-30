<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Jika session user sudah hilang / expired
        if (!Auth::check()) {
            // Simpan pesan notifikasi
            session()->flash('logout_notice', 'Sesi Anda telah berakhir. Silakan login kembali.');

            // Redirect ke login
            return redirect('/login');
        }

        return $next($request);
    }
}
