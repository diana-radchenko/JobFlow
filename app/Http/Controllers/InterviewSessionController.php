<?php

namespace App\Http\Controllers;

use App\Ai\Agents\InterviewAgent;
use App\Data\InterviewContextData;
use App\Models\InterviewSession;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Ai\Audio;
use Laravel\Ai\Transcription;

class InterviewSessionController extends Controller
{
    private const TOTAL_QUESTIONS = 6;

    private const START_PROMPT = 'Begin the mock interview with question 1. Return only the question.';

    private const FINAL_EVALUATION_PROMPT = 'The mock interview is complete. Return the final result now using the required Overall Assessment, Strengths, Areas to Improve, and Recommendation headings.';

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
                $agent = $this->makeInterviewAgent($user, $session, $questionNumber, true);
                $prompt = "Candidate's answer to question {$questionNumber}:\n\n{$validated['message']}\n\n".self::FINAL_EVALUATION_PROMPT;
                $response = $this->promptWithAgent($agent, $user, $session, $prompt);
                $session->update(['status' => 'completed']);

                return response()->json([
                    'message' => null,
                    'question_number' => $questionNumber,
                    'total_questions' => self::TOTAL_QUESTIONS,
                    'session_status' => 'completed',
                    'results_url' => route('interview-session.results', $session),
                ]);
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

    public function audio(Request $request, InterviewSession $session)
    {
        /** @var User $user */
        $user = $request->user();

        $this->authorizeOwner($request, $session);
        $this->ensureInProgress($session);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:10000'],
        ]);

        $audio = Audio::of($validated['content'])
            ->female()
            ->instructions('Speak naturally as a calm, supportive technical interviewer. Keep a warm conversational tone and avoid sounding robotic.')
            ->generate();

        return response((string) $audio)
            ->header('Content-Type', $audio->mimeType() ?? 'audio/mpeg')
            ->header('Cache-Control', 'no-store');
    }

    public function transcribe(Request $request, InterviewSession $session)
    {
        /** @var User $user */
        $user = $request->user();

        $this->authorizeOwner($request, $session);
        $this->ensureInProgress($session);

        $validated = $request->validate([
            'audio' => ['required', 'file', 'max:20480'],
        ]);

        try {
            $transcript = Transcription::fromUpload($validated['audio'])
                ->language('en')
                ->generate('openai');
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Could not transcribe audio. Check your OpenAI connection and try again.',
            ], 422);
        }

        return response()->json([
            'text' => trim($transcript->text),
        ]);
    }

    public function complete(Request $request, InterviewSession $session)
    {
        /** @var User $user */
        $user = $request->user();

        $this->authorizeOwner($request, $session);

        if ($session->status === 'completed') {
            return redirect()->route('interview-session.results', $session);
        }

        $this->ensureInProgress($session);

        try {
            $agent = $this->makeInterviewAgent(
                $user,
                $session,
                $this->assistantMessageCount($session),
                true,
            );
            $this->promptWithAgent($agent, $user, $session, self::FINAL_EVALUATION_PROMPT);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'interview' => 'AI service error. Check your OpenAI connection and try again.',
            ]);
        }

        $session->update(['status' => 'completed']);

        return redirect()->route('interview-session.results', $session);
    }

    public function results(Request $request, InterviewSession $session): Response|RedirectResponse
    {
        $this->authorizeOwner($request, $session);

        if ($session->status !== 'completed') {
            return redirect()->route('interview-session.show', $session);
        }

        $session->loadMissing(['resume:id,title', 'workJob:id,title,company']);
        $assistantMessages = $this->visibleMessages($session)->where('role', 'assistant');
        $result = $assistantMessages->first(
            fn (array $message): bool => str_contains($message['content'], 'Overall Assessment')
                && str_contains($message['content'], 'Strengths')
                && str_contains($message['content'], 'Areas to Improve')
                && str_contains($message['content'], 'Recommendation'),
        ) ?? $assistantMessages->last();

        return Inertia::render('Interview/Results', [
            'session' => $session,
            'result' => $result['content'] ?? null,
            'context' => [
                'resume_title' => $session->resume?->title,
                'job_title' => $session->workJob?->title,
                'company' => $session->workJob?->company,
            ],
        ]);
    }

    private function makeInterviewAgent(
        User $user,
        InterviewSession $session,
        int $currentQuestion = 0,
        bool $finalEvaluation = false,
    ): InterviewAgent
    {
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
    ): object
    {
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
    ): object
    {
        if ($session->conversation_id) {
            return $agent->continue($session->conversation_id, as: $user)
                ->prompt($prompt, model: config('ai.model'));
        }

        $response = $agent->forUser($user)->prompt($prompt, model: config('ai.model'));

        $session->update([
            'conversation_id' => $response->conversationId,
        ]);

        return $response;
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

    private function authorizeOwner(Request $request, InterviewSession $session): void
    {
        if ($session->user_id !== $request->user()->id) {
            abort(403);
        }
    }

    private function ensureInProgress(InterviewSession $session): void
    {
        abort_unless($session->status === 'in_progress', 409, 'This mock interview is not in progress.');
    }
}

