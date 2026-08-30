<?php

namespace App\Http\Controllers;

use App\Ai\Agents\InterviewPrepAgent;
use App\Data\InterviewContextData;
use App\Models\Resume;
use App\Models\User;
use App\Models\WorkJob;
use App\Services\InterviewVoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InterviewPrepController extends Controller
{
    public function __construct(private InterviewVoice $voice) {}

    private const TYPES = ['behavioral', 'technical', 'case-study', 'resume-based'];

    private const COMPLEXITIES = ['beginner', 'intermediate', 'advanced'];

    public function show(Request $request): Response
    {
        [$validated, $resume, $job] = $this->validatedContext($request);

        return Inertia::render('Interview/Prep', [
            'context' => [
                ...$validated,
                'resume_title' => $resume->title,
                'job_title' => $job?->title,
                'company' => $job?->company,
            ],
        ]);
    }

    public function guidance(Request $request): JsonResponse
    {
        [$validated, $resume, $job] = $this->validatedContext($request);
        $input = $request->validate([
            'practice_answer' => ['nullable', 'string', 'max:10000'],
        ]);

        $context = InterviewContextData::fromResume($resume, $job);
        $agent = new InterviewPrepAgent(
            $validated['type'],
            $validated['complexity'],
            $context->resumeContext(),
            $context->jobContext(),
        );

        $prompt = empty($input['practice_answer'])
            ? 'Create a focused preparation plan. Include likely themes, three preparation priorities, and one concise practice question.'
            : "Coach me on this practice answer. Identify what is strong, what is missing, and give a better structure without scoring it:\n\n{$input['practice_answer']}";

        try {
            $response = $agent->prompt($prompt, model: config('ai.model'));
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => "We couldn't prepare your interview guidance.",
            ], 422);
        }

        return response()->json(['guidance' => (string) $response]);
    }

    public function audio(Request $request): HttpResponse|JsonResponse
    {
        $this->validateVoiceContext($request);
        $validated = $request->validate($this->voice->speechRules());

        return $this->voice->audio($validated['content']);
    }

    public function transcribe(Request $request): JsonResponse
    {
        $this->validateVoiceContext($request);
        $validated = $request->validate($this->voice->transcriptionRules());

        return $this->voice->transcribe($validated['audio']);
    }

    private function validateVoiceContext(Request $request): void
    {
        $this->validatedContext($request);
        $request->validate(['mode' => ['required', Rule::in(['live'])]]);
    }

    /**
     * @return array{0: array<string, mixed>, 1: Resume, 2: WorkJob|null}
     */
    private function validatedContext(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validate([
            'type' => ['required', Rule::in(self::TYPES)],
            'complexity' => ['required', Rule::in(self::COMPLEXITIES)],
            'mode' => ['required', Rule::in(['text', 'live'])],
            'resume_id' => ['required', 'integer', Rule::exists('resumes', 'id')->where('user_id', $user->id)],
            'work_job_id' => ['nullable', 'integer', Rule::exists('user_work_job_applications', 'work_job_id')->where('user_id', $user->id)],
        ]);

        $resume = $user->resumes()->findOrFail($validated['resume_id']);
        $job = isset($validated['work_job_id']) ? WorkJob::findOrFail($validated['work_job_id']) : null;

        return [$validated, $resume, $job];
    }
}
