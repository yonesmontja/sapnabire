<?php

namespace App\Services\Assessment;

use App\Enums\UserRole;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Klien Laravel → Django. Setiap request membawa Service JWT berumur pendek
 * yang memuat konteks tenant (school_id) dan scope sesuai peran user.
 */
class AssessmentClient
{
    private const ROLE_SCOPES = [
        'teacher' => ['assessment:generate', 'assessment:analyze'],
        'principal' => ['assessment:generate', 'assessment:analyze'],
        'school_admin' => ['assessment:generate', 'assessment:analyze'],
        'super_admin' => ['assessment:generate', 'assessment:analyze'],
    ];

    public function __construct(private readonly array $config) {}

    public function serviceToken(User $user): string
    {
        $now = time();

        return JWT::encode([
            'iss' => $this->config['jwt_issuer'],
            'aud' => $this->config['jwt_audience'],
            'sub' => (string) $user->getKey(),
            'school_id' => $user->school_id,
            'role' => $user->role->value,
            'scopes' => self::ROLE_SCOPES[$user->role->value] ?? [],
            'iat' => $now,
            'exp' => $now + $this->config['jwt_ttl'],
        ], $this->config['jwt_secret'], 'HS256');
    }

    public function createGenerationJob(User $user, array $payload): Response
    {
        return $this->http($user)->post('/v1/generator/jobs', $payload);
    }

    public function generationJob(User $user, string $jobId): Response
    {
        return $this->http($user)->get("/v1/generator/jobs/{$jobId}");
    }

    public function generationJobQuestions(User $user, string $jobId): Response
    {
        return $this->http($user)->get("/v1/generator/jobs/{$jobId}/questions");
    }

    public function analyzeItems(User $user, array $payload): Response
    {
        return $this->http($user)->post('/v1/analysis/item-analysis', $payload);
    }

    public static function canUse(User $user): bool
    {
        return $user->hasRole(UserRole::Teacher, UserRole::Principal, UserRole::SchoolAdmin, UserRole::SuperAdmin);
    }

    private function http(User $user): PendingRequest
    {
        return Http::baseUrl($this->config['base_url'])
            ->withToken($this->serviceToken($user))
            ->acceptJson()
            ->timeout($this->config['timeout'])
            ->retry(2, 200, throw: false);
    }
}
