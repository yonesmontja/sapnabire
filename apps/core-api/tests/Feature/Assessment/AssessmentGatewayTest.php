<?php

namespace Tests\Feature\Assessment;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssessmentGatewayTest extends TestCase
{
    use RefreshDatabase;

    private const JOB_ID = '6f1c2a8e-3b7d-4c55-9a10-2f4e8d9b1c00';

    private function payload(): array
    {
        return [
            'subject' => 'IPA', 'grade' => 'Kelas 5', 'learning_objective' => 'Menjelaskan siklus air.',
            'material' => 'Siklus air: evaporasi, kondensasi, presipitasi.', 'question_type' => 'multiple_choice',
            'total' => 40, 'tier_distribution' => ['LOTS' => 30, 'MOTS' => 40, 'HOTS' => 30],
        ];
    }

    public function test_teacher_request_is_forwarded_with_scoped_service_jwt(): void
    {
        Http::fake(['assessment.test/v1/generator/jobs' => Http::response(['id' => self::JOB_ID, 'status' => 'queued'], 202)]);
        $user = User::factory()->create(['school_id' => School::factory()->create()->id, 'role' => UserRole::Teacher]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/assessment/generator/jobs', $this->payload(), ['Idempotency-Key' => 'abc-123'])
            ->assertStatus(202)
            ->assertJsonPath('id', self::JOB_ID);

        Http::assertSent(function (HttpRequest $request) use ($user) {
            $claims = JWT::decode(str_replace('Bearer ', '', $request->header('Authorization')[0]), new Key(config('services.assessment.jwt_secret'), 'HS256'));

            return $claims->aud === 'eduerp-assessment'
                && $claims->school_id === $user->school_id
                && in_array('assessment:generate', $claims->scopes, true)
                && $claims->exp - $claims->iat <= 300
                && $request['idempotency_key'] === 'abc-123';
        });
        $this->assertDatabaseHas('activity_logs', ['module' => 'ai_generator', 'event' => 'job_requested']);
    }

    public function test_student_cannot_use_generator(): void
    {
        Http::fake();
        Sanctum::actingAs(User::factory()->create(['school_id' => School::factory()->create()->id, 'role' => UserRole::Student]));

        $this->postJson('/api/v1/assessment/generator/jobs', $this->payload())->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_callback_requires_valid_signature(): void
    {
        $school = School::factory()->create();
        $body = json_encode(['event' => 'generation.finished', 'data' => [
            'job_id' => self::JOB_ID, 'school_id' => $school->id, 'status' => 'completed', 'questions' => 40,
        ]]);
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, config('services.assessment.jwt_secret'));
        $send = fn (string $sig, string $ts) => $this->call('POST', '/api/internal/assessment/callbacks', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_EDUERP_TIMESTAMP' => $ts, 'HTTP_X_EDUERP_SIGNATURE' => $sig,
        ], $body);

        $send('tampered', $timestamp)->assertUnauthorized();
        $send($signature, (string) (time() - 3600))->assertUnauthorized();
        $send($signature, $timestamp)->assertNoContent();
        $this->assertDatabaseHas('activity_logs', ['school_id' => $school->id, 'event' => 'generation.finished']);
    }
}
