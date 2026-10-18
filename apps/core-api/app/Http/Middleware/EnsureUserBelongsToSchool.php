<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Semua endpoint modul sekolah butuh konteks tenant. Akun tanpa sekolah / nonaktif ditolak.
 */
class EnsureUserBelongsToSchool
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user?->is_active, 403, 'Akun nonaktif.');
        abort_if($user->school_id === null, 409, 'Akun belum terhubung ke sekolah. Jalankan Setup Sekolah terlebih dahulu.');

        return $next($request);
    }
}
