<?php

namespace App\Http\Controllers;

use App\Ai\Agents\InterviewAgent;
use App\Data\InterviewContextData;
use App\Models\InterviewSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Ai\Audio;
use Laravel\Ai\Transcription;

class InterviewSessionController extends Controller
{
    private const FINAL_EVALUATION_PROMPT = 'The interview is now complete. Provide a final evaluation of the candidate based on the conversation so far. Include concise scores (1-10) for technical depth, communication, problem-solving, and confidence, then end with the top 3 actionable improvements.';

    public function store(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'type' => ['required', 'string'],
            'complexity' => ['required', 'string'],
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

    public function show(Request $request, InterviewSession $session): Response
    {
        if ($session->user_id !== $request->user()->id) {
            abort(403);
        }

        // Fetch messages if conversation_id exists
        $messages = [];
        if ($session->conversation_id) {
            // We can retrieve messages via the agent's RemembersConversations trait
            // by using the underlying DB if needed, or we just rely on AI SDK table.
            $messages = DB::table('agent_conversation_messages')
                ->where('conversation_id', $session->conversation_id)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn ($m) => [
                    'role' => $m->role,
                    'content' => $m->content,
                ]);
        }

        $component = $session->mode === 'live' ? 'Interview/Live' : 'Interview/Chat';

        return Inertia::render($component, [
            'session' => $session,
            'messages' => $messages,
        ]);
    }

    public function message(Request $request, InterviewSession $session)
    {
        /** @var User $user */
        $user = $request->user();

        if ($session->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);

        $response = $this->promptInterviewAgent($user, $session, $validated['message']);

        return response()->json([
            'message' => [
                'role' => 'assistant',
                'content' => (string) $response,
            ],
        ]);
    }

    public function audio(Request $request, InterviewSession $session)
    {
        /** @var User $user */
        $user = $request->user();

        if ($session->user_id !== $user->id) {
            abort(403);
        }

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

        if ($session->user_id !== $user->id) {
            abort(403);
        }

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

        if ($session->user_id !== $user->id) {
            abort(403);
        }

        try {
            $this->promptInterviewAgent($user, $session, self::FINAL_EVALUATION_PROMPT);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'interview' => 'AI service error. Check your OpenAI connection and try again.',
            ]);
        }

        $session->update(['status' => 'completed']);

        return redirect()->route('interview-preparation');
    }

    private function makeInterviewAgent(User $user, InterviewSession $session): InterviewAgent
    {
        $context = $session->resume
            ? InterviewContextData::fromResume($session->resume, $session->workJob)
            : InterviewContextData::fromUser($user);

        return new InterviewAgent(
            $session->type,
            $session->complexity,
            $context->resumeContext(),
            $context->jobContext(),
        );
    }

    private function promptInterviewAgent(User $user, InterviewSession $session, string $prompt): object
    {
        $agent = $this->makeInterviewAgent($user, $session);

        if ($session->conversation_id) {
            return $agent->continue($session->conversation_id, as: $user)
                ->prompt($prompt, model: 'gpt-4o');
        }

        $response = $agent->forUser($user)->prompt($prompt, model: 'gpt-4o');

        $session->update([
            'conversation_id' => $response->conversationId,
        ]);

        return $response;
    }
}
