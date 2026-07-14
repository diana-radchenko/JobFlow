<?php

namespace App\Http\Controllers;

use App\Ai\Agents\ResumeScoreAgent;
use App\Data\ResumeScoreContextData;
use App\Models\Resume;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResumeScoreController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resume_id' => ['required', 'integer', 'exists:resumes,id'],
            'job_title' => ['required', 'string', 'max:255'],
            'job_company' => ['required', 'string', 'max:255'],
            'job_salary' => ['nullable', 'string', 'max:255'],
            'job_tags' => ['nullable', 'array'],
            'job_tags.*' => ['string', 'max:255'],
        ]);

        $resume = Resume::findOrFail($validated['resume_id']);

        $this->authorize('view', $resume);

        $context = ResumeScoreContextData::fromResume($resume, [
            'title' => $validated['job_title'],
            'company' => $validated['job_company'],
            'salary' => $validated['job_salary'] ?? null,
            'tags' => $validated['job_tags'] ?? [],
        ]);

        try {
            $response = (new ResumeScoreAgent($context->resumeContext(), $context->jobContext()))
                ->prompt('Score this resume against the job and provide recommendations.', model: 'gpt-4o');
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'AI service error. Check your OpenAI connection and try again.',
            ], 422);
        }

        return response()->json([
            'score' => $response['score'],
            'summary' => $response['summary'],
            'highlights' => $response['highlights'],
            'additions' => $response['additions'],
            'removals' => $response['removals'],
        ]);
    }
}
