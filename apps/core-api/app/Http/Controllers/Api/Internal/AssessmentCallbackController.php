<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AssessmentCallbackController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $payload = $request->validate([
            'event' => ['required', 'string'],
            'data.job_id' => ['required', 'uuid'],
            'data.school_id' => ['required', 'string', 'size:26', 'exists:schools,id'],
            'data.status' => ['required', 'string'],
            'data.questions' => ['required', 'integer'],
        ]);

        ActivityLog::create([
            'school_id' => $payload['data']['school_id'],
            'module' => 'ai_generator',
            'event' => $payload['event'],
            'properties' => $payload['data'],
            'ip_address' => $request->ip(),
        ]);

        // Fase berikutnya: broadcast ke Next.js (Laravel Reverb) agar progress bar guru ter-update realtime.

        return response()->noContent();
    }
}
