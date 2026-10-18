<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pemakaian di route: ->middleware('role:school_admin,principal').
 * super_admin selalu lolos.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $allowed = array_map(fn (string $role) => UserRole::from($role), $roles);

        abort_unless($user && $user->hasRole(UserRole::SuperAdmin, ...$allowed), 403, 'Peran Anda tidak memiliki akses ke fitur ini.');

        return $next($request);
    }
}
