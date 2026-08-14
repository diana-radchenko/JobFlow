<?php

namespace App\Http\Controllers;

use App\Ai\Agents\ResumeSalaryAnalysisAgent;
use App\Data\ResumeScoreContextData;
use App\Models\Resume;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResumeSalaryAnalysisController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resume_id' => ['required', 'integer', 'exists:resumes,id'],
        ]);

        $resume = Resume::findOrFail($validated['resume_id']);

        $this->authorize('view', $resume);

        $context = ResumeScoreContextData::fromResume($resume, job: []);

        try {
            $response = (new ResumeSalaryAnalysisAgent($context->resumeContext()))
                ->prompt('Analyze this resume and estimate the salary it can command.', model: 'gpt-4o');
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'AI service error. Check your OpenAI connection and try again.',
            ], 422);
        }

        return response()->json([
            'strengths' => $response['strengths'],
            'weaknesses' => $response['weaknesses'],
            'expectedSalaryMin' => $response['expectedSalaryMin'],
            'expectedSalaryMax' => $response['expectedSalaryMax'],
            'salaryRationale' => $response['salaryRationale'],
        ]);
    }
}
