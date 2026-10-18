<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifikasi webhook dari Django: HMAC-SHA256 atas "<timestamp>.<raw body>", toleransi 5 menit (anti replay).
 */
class VerifyServiceSignature
{
    private const TOLERANCE_SECONDS = 300;

    public function handle(Request $request, Closure $next): Response
    {
        $timestamp = (string) $request->header('X-EduERP-Timestamp');
        $signature = (string) $request->header('X-EduERP-Signature');
        $secret = (string) config('services.assessment.jwt_secret');

        abort_if($secret === '' || ! ctype_digit($timestamp) || abs(time() - (int) $timestamp) > self::TOLERANCE_SECONDS, 401, 'Signature kedaluwarsa.');

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);
        abort_unless(hash_equals($expected, $signature), 401, 'Signature tidak valid.');

        return $next($request);
    }
}
