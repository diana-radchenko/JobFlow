<?php

namespace App\Http\Controllers;

use App\Ai\Agents\InterviewAgent;
use App\Data\InterviewContextData;
use App\Models\InterviewSession;
use App\Models\Resume;
use App\Models\User;
use App\Services\InterviewVoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InterviewSessionController extends Controller
{
    public function __construct(private InterviewVoice $voice) {}

    private const TOTAL_QUESTIONS = 6;

    private const START_PROMPT = 'Begin the mock interview with question 1. Return only the question.';

    private const FINAL_EVALUATION_PROMPT = 'The mock interview is complete. Evaluate the saved answers. Use exactly these Markdown level-two headings: ## Overall Assessment, ## Strengths, ## Areas to Improve, ## Recommendation. Use separate - bullets under Strengths and Areas to Improve, and paragraphs under Overall Assessment and Recommendation.';

    private const TYPES = ['behavioral', 'technical', 'case-study', 'resume-based'];

    private const COMPLEXITIES = ['beginner', 'intermediate', 'advanced'];

    public function store(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'type' => ['required', Rule::in(self::TYPES)],
            'complexity' => ['required', Rule::in(self::COMPLEXITIES)],
            'mode' => ['required', 'string', 'in:text,live'],
            'resume_id' => [
                'required',
                'integer',
                Rule::exists('resumes', 'id')->where('user_id', $user->id),
            ],
            'work_job_id' => [
                'nullable',
                'integer',
                Rule::exists('user_work_job_applications', 'work_job_id')->where('user_id', $user->id),
            ],
        ]);

        // Check if there is an active session
        $activeSession = InterviewSession::query()
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->where('mode', $validated['mode'])
            ->first();

        if ($activeSession) {
            return redirect()->route('interview-session.show', $activeSession);
        }

        $session = InterviewSession::create([
            'user_id' => $user->id,
            'resume_id' => $validated['resume_id'],
            'work_job_id' => $validated['work_job_id'] ?? null,
            'type' => $validated['type'],
            'complexity' => $validated['complexity'],
            'mode' => $validated['mode'],
            'status' => 'in_progress',
        ]);

        return redirect()->route('interview-session.show', $session);
    }

    public function show(Request $request, InterviewSession $session): Response|RedirectResponse
    {
        $this->authorizeOwner($request, $session);

        if ($session->status === 'completed') {
            return redirect()->route('interview-session.results', $session);
        }

        if ($session->status !== 'in_progress' || ! in_array($session->mode, ['text', 'live'], true)) {
            return redirect()->route('interview-preparation');
        }

        $messages = $this->visibleMessages($session);
        $session->loadMissing(['resume:id,title', 'workJob:id,title,company']);

        $component = $session->mode === 'live' ? 'Interview/Live' : 'Interview/Chat';

        return Inertia::render($component, [
            'session' => $session,
            'messages' => $messages,
            'context' => [
                'resume_title' => $session->resume?->title,
                'job_title' => $session->workJob?->title,
                'company' => $session->workJob?->company,
            ],
            'questionNumber' => $messages->where('role', 'assistant')->count(),
            'totalQuestions' => self::TOTAL_QUESTIONS,
        ]);
    }

    public function message(Request $request, InterviewSession $session): JsonResponse
    {
        $this->authorizeOwner($request, $session);

        return Cache::lock("interview-session:{$session->id}", 90)->get(function () use ($request, $session): JsonResponse {
            $session->refresh();

            if ($session->status === 'completed') {
                return $this->completionResponse($session);
            }

            return $this->processMessage($request, $session);
        }) ?: response()->json(['message' => 'An interview request is already in progress. Please try again.'], 409);
    }

    private function processMessage(Request $request, InterviewSession $session): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authorizeOwner($request, $session);
        $this->ensureInProgress($session);

        $validated = $request->validate([
            'intent' => ['nullable', Rule::in(['start', 'answer'])],
            'message' => ['nullable', 'string', 'max:10000', 'required_unless:intent,start'],
        ]);
        $intent = $validated['intent'] ?? 'answer';
        $questionNumber = $this->assistantMessageCount($session);

        if ($intent === 'start' && $questionNumber > 0) {
            $lastQuestion = $this->visibleMessages($session)->where('role', 'assistant')->last();

            return response()->json([
                'message' => $lastQuestion,
                'question_number' => $questionNumber,
                'total_questions' => self::TOTAL_QUESTIONS,
                'session_status' => $session->status,
            ]);
        }

        try {
            if ($intent === 'start') {
                $response = $this->promptInterviewAgent($user, $session, self::START_PROMPT, 0);
                $questionNumber = 1;
            } elseif ($questionNumber >= self::TOTAL_QUESTIONS) {
                DB::transaction(function () use ($session, $user, $validated): void {
                    DB::table('agent_conversation_messages')->insert([
                        'id' => (string) Str::uuid(),
                        'conversation_id' => $session->conversation_id,
                        'user_id' => $user->id,
                        'agent' => InterviewAgent::class,
                        'role' => 'user',
                        'content' => $validated['message'],
                        'attachments' => '[]',
                        'tool_calls' => '[]',
                        'tool_results' => '[]',
                        'usage' => '{}',
                        'meta' => '{}',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $session->update(['status' => 'completed', 'feedback_status' => 'pending']);
                });

                return $this->completionResponse($session);
            } else {
                $response = $this->promptInterviewAgent(
                    $user,
                    $session,
                    $validated['message'],
                    $questionNumber,
                );
                $questionNumber++;
            }
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => $intent === 'start'
                    ? "We couldn't prepare the interview question."
                    : "We couldn't get the next interview question.",
            ], 422);
        }

        return response()->json([
            'message' => [
                'role' => 'assistant',
                'content' => (string) $response,
            ],
            'question_number' => $questionNumber,
            'total_questions' => self::TOTAL_QUESTIONS,
            'session_status' => $session->status,
        ]);
    }

    public function audio(Request $request, InterviewSession $session): HttpResponse|JsonResponse
    {
        $this->authorizeOwner($request, $session);
        $this->ensureInProgress($session);

        $validated = $request->validate($this->voice->speechRules());

        return $this->voice->audio($validated['content']);
    }

    public function transcribe(Request $request, InterviewSession $session): JsonResponse
    {
        $this->authorizeOwner($request, $session);
        $this->ensureInProgress($session);

        $validated = $request->validate($this->voice->transcriptionRules());

        return $this->voice->transcribe($validated['audio']);
    }

    public function complete(Request $request, InterviewSession $session): RedirectResponse|JsonResponse
    {
        $this->authorizeOwner($request, $session);

        if ($session->status !== 'completed') {
            $ended = Cache::lock("interview-session:{$session->id}", 90)->get(function () use ($session): bool {
                $session->refresh();

                if ($session->status !== 'completed') {
                    $this->ensureInProgress($session);
                    $session->update(['status' => 'completed', 'feedback_status' => 'pending']);
                }

                return true;
            });

            if (! $ended) {
                return back()->withErrors(['interview' => 'Please wait for the current answer to finish, then end the interview.']);
            }
        }

        if ($request->expectsJson()) {
            return $this->completionResponse($session);
        }

        return $request->boolean('return_to_setup')
            ? redirect()->route('interview-preparation')
            : redirect()->route('interview-session.results', $session);
    }

    public function feedback(Request $request, InterviewSession $session): JsonResponse
    {
        $this->authorizeOwner($request, $session);
        abort_unless($session->status === 'completed' && in_array($session->mode, ['text', 'live'], true) && $session->application_id === null, 409);

        return Cache::lock("interview-session:{$session->id}", 90)->get(function () use ($request, $session): JsonResponse {
            $session->refresh();
            $result = $this->evaluationResult($session);

            if ($result !== null) {
                return response()->json(['feedback_status' => 'ready', 'result' => $result]);
            }

            $session->update(['feedback_status' => 'generating']);

            try {
                $agent = $this->makeInterviewAgent($request->user(), $session, $this->assistantMessageCount($session), true);
                $response = $this->promptWithAgent($agent, $request->user(), $session, self::FINAL_EVALUATION_PROMPT);
                $result = trim((string) $response);

                if ($result === '') {
                    throw new \RuntimeException('Empty interview evaluation response.');
                }

                $session->update(['feedback_status' => 'ready']);

                return response()->json(['feedback_status' => 'ready', 'result' => $result]);
            } catch (\Throwable $exception) {
                report($exception);
                $session->update(['feedback_status' => 'failed']);

                return response()->json([
                    'feedback_status' => 'failed',
                    'message' => "Your interview was saved, but we couldn't generate feedback yet.",
                ], 422);
            }
        }) ?: response()->json(['feedback_status' => 'generating'], 202);
    }

    public function results(Request $request, InterviewSession $session): Response|RedirectResponse
    {
        $this->authorizeOwner($request, $session);

        if ($session->status !== 'completed') {
            return redirect()->route('interview-session.show', $session);
        }

        $session->loadMissing(['resume:id,title', 'workJob:id,title,company']);

        return Inertia::render('Interview/Results', [
            'session' => $session,
            'result' => $this->evaluationResult($session),
            'context' => [
                'resume_title' => $session->resume?->title,
                'job_title' => $session->workJob?->title,
                'company' => $session->workJob?->company,
            ],
        ]);
    }

    public function destroy(Request $request, InterviewSession $session): RedirectResponse
    {
        $this->authorizeOwner($request, $session);

        abort_unless(
            $session->status === 'completed'
                && in_array($session->mode, ['text', 'live'], true)
                && $session->application_id === null,
            422,
            'Only completed AI interviews can be deleted.',
        );

        $conversationId = $session->conversation_id;
        $userId = $request->user()->id;

        DB::transaction(function () use ($conversationId, $session, $userId): void {
            $session->delete();

            if ($conversationId) {
                $this->deleteConversationIfUnused($conversationId, $userId);
            }
        });

        return back()->with('success', 'Interview deleted.');
    }

    private function makeInterviewAgent(
        User $user,
        InterviewSession $session,
        int $currentQuestion = 0,
        bool $finalEvaluation = false,
    ): InterviewAgent {
        $context = $session->resume
            ? InterviewContextData::fromResume($session->resume, $session->workJob)
            : InterviewContextData::fromUser($user);

        return new InterviewAgent(
            $session->type,
            $session->complexity,
            $context->resumeContext(),
            $context->jobContext(),
            $currentQuestion,
            self::TOTAL_QUESTIONS,
            $finalEvaluation,
        );
    }

    private function promptInterviewAgent(
        User $user,
        InterviewSession $session,
        string $prompt,
        int $currentQuestion,
    ): object {
        return $this->promptWithAgent(
            $this->makeInterviewAgent($user, $session, $currentQuestion),
            $user,
            $session,
            $prompt,
        );
    }

    private function promptWithAgent(
        InterviewAgent $agent,
        User $user,
        InterviewSession $session,
        string $prompt,
    ): object {
        if ($session->conversation_id) {
            return $agent->continue($session->conversation_id, as: $user)
                ->prompt($prompt, model: config('ai.model'), timeout: 60);
        }

        $response = $agent->forUser($user)->prompt($prompt, model: config('ai.model'), timeout: 60);

        $session->update([
            'conversation_id' => $response->conversationId,
        ]);

        return $response;
    }

    private function completionResponse(InterviewSession $session): JsonResponse
    {
        return response()->json([
            'message' => null,
            'question_number' => min($this->assistantMessageCount($session), self::TOTAL_QUESTIONS),
            'total_questions' => self::TOTAL_QUESTIONS,
            'session_status' => 'completed',
            'feedback_status' => $session->feedback_status,
            'results_url' => route('interview-session.results', $session),
        ]);
    }

    private function evaluationResult(InterviewSession $session): ?string
    {
        $messages = $this->visibleMessages($session)->where('role', 'assistant');
        $result = $messages->first(
            fn (array $message): bool => str_contains($message['content'], 'Overall Assessment')
                && str_contains($message['content'], 'Strengths')
                && str_contains($message['content'], 'Areas to Improve')
                && str_contains($message['content'], 'Recommendation'),
        );

        if ($result === null && in_array($session->feedback_status, [null, 'ready'], true)) {
            $result = $messages->last();
        }

        return filled($result['content'] ?? null) ? $result['content'] : null;
    }

    private function visibleMessages(InterviewSession $session)
    {
        if (! $session->conversation_id) {
            return collect();
        }

        return DB::table('agent_conversation_messages')
            ->where('conversation_id', $session->conversation_id)
            ->orderBy('created_at')
            ->get()
            ->reject(fn ($message) => $message->role === 'user' && $message->content === self::START_PROMPT)
            ->map(fn ($message) => [
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->values();
    }

    private function assistantMessageCount(InterviewSession $session): int
    {
        if (! $session->conversation_id) {
            return 0;
        }

        return DB::table('agent_conversation_messages')
            ->where('conversation_id', $session->conversation_id)
            ->where('role', 'assistant')
            ->count();
    }

    private function deleteConversationIfUnused(string $conversationId, int $userId): void
    {
        $conversationBelongsToUser = DB::table('agent_conversations')
            ->where('id', $conversationId)
            ->where('user_id', $userId)
            ->exists();

        if (! $conversationBelongsToUser
            || InterviewSession::where('conversation_id', $conversationId)->exists()
            || Resume::where('ai_conversation_id', $conversationId)->exists()) {
            return;
        }

        DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->delete();
        DB::table('agent_conversations')
            ->where('id', $conversationId)
            ->where('user_id', $userId)
            ->delete();
    }

    private function authorizeOwner(Request $request, InterviewSession $session): void
    {
        if ($session->user_id !== $request->user()->id) {
            abort(403);
        }
    }

    private function ensureInProgress(InterviewSession $session): void
    {
        abort_unless($session->status === 'in_progress', 409, 'This AI interview is not in progress.');
    }
}
