<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\LoginRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $field = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::where($field, $request->login)->first();

        if (! $user || ! $user->is_active || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['login' => 'Username/email atau password salah.']);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $request->setUserResolver(fn () => $user);
        ActivityLog::record($request, 'auth', 'login', $user);

        // Ability token dibatasi per peran; Next.js menyimpannya di cookie httpOnly (lihat docs/03-api-gateway.md).
        $token = $user->createToken($request->device_name ?? 'web', [$user->role->value], now()->addHours(12));

        return response()->json([
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil keluar.']);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role->value,
            'school_id' => $user->school_id,
        ];
    }
}
