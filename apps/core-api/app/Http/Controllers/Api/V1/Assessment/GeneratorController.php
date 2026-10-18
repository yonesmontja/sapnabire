<?php

namespace App\Http\Controllers\Api\V1\Assessment;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\Assessment\AssessmentClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Gateway AI Soal Generator: Next.js → Laravel (validasi, otorisasi, audit) → Django (generasi).
 * Next.js tidak pernah bicara langsung dengan Django.
 */
class GeneratorController extends Controller
{
    public function __construct(private readonly AssessmentClient $assessment) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'learning_objective_id' => ['nullable', 'string', 'size:26'],
            'subject' => ['required', 'string', 'max:100'],
            'grade' => ['required', 'string', 'max:50'],
            'learning_objective' => ['required', 'string'],
            'material' => ['required', 'string', 'max:60000'],
            'question_type' => ['required', Rule::in(['multiple_choice', 'complex_multiple_choice', 'true_false', 'short_answer', 'essay'])],
            'options_count' => ['nullable', 'integer', 'between:3,5'],
            'total' => ['required', 'integer', 'between:1,500'],
            'level_distribution' => ['required_without:tier_distribution', 'array'],
            'level_distribution.*' => ['numeric', 'min:0'],
            'tier_distribution' => ['required_without:level_distribution', 'array'],
            'tier_distribution.*' => ['numeric', 'min:0'],
        ]);

        // Header Idempotency-Key dari frontend mencegah job ganda saat tombol diklik dua kali / retry jaringan.
        $data['idempotency_key'] = $request->header('Idempotency-Key') ?? hash('sha256', $request->user()->id.json_encode($data));

        $response = $this->assessment->createGenerationJob($request->user(), $data);

        if ($response->successful()) {
            ActivityLog::record($request, 'ai_generator', 'job_requested', null, [
                'job_id' => $response->json('id'),
                'total' => $data['total'],
            ]);
        }

        return response()->json($response->json(), $response->status());
    }

    public function show(Request $request, string $job): JsonResponse
    {
        $response = $this->assessment->generationJob($request->user(), $job);

        return response()->json($response->json(), $response->status());
    }

    public function questions(Request $request, string $job): JsonResponse
    {
        $response = $this->assessment->generationJobQuestions($request->user(), $job);

        return response()->json($response->json(), $response->status());
    }
}
