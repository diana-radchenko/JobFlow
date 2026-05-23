<?php

namespace App\Http\Controllers;

use App\Ai\Agents\InterviewAgent;
use App\Data\InterviewContextData;
use App\Models\InterviewSession;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InterviewSessionController extends Controller
{
    private const FINAL_EVALUATION_PROMPT = 'The interview is now complete. Provide a final evaluation of the candidate based on the conversation so far. Include concise scores (1-10) for technical depth, communication, problem-solving, and confidence, then end with the top 3 actionable improvements.';

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'type' => ['required', 'string'],
            'complexity' => ['required', 'string'],
            'mode' => ['required', 'string', 'in:text,live'],
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

        $page = $session->mode === 'live' ? 'Interview/Live' : 'Interview/Chat';

        return Inertia::render($page, [
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

        try {
            $response = $this->promptInterviewAgent($user, $session, $validated['message']);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'AI service error. Check OPENAI_API_KEY in .env and that the model name is valid.',
            ], 502);
        }

        return response()->json([
            'message' => [
                'role' => 'assistant',
                'content' => (string) $response,
            ],
        ]);
    }

    public function complete(Request $request, InterviewSession $session)
    {
        /** @var User $user */
        $user = $request->user();

        if ($session->user_id !== $user->id) {
            abort(403);
        }

        $this->promptInterviewAgent($user, $session, self::FINAL_EVALUATION_PROMPT);

        $session->update(['status' => 'completed']);

        return redirect()->route('interview-preparation');
    }

    private function makeInterviewAgent(User $user, InterviewSession $session): InterviewAgent
    {
        $context = InterviewContextData::fromUser($user);

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

        $model = config('ai.interview_model');

        if ($session->conversation_id) {
            return $agent->continue($session->conversation_id, as: $user)
                ->prompt($prompt, model: $model);
        }

        $response = $agent->forUser($user)->prompt($prompt, model: $model);

        $session->update([
            'conversation_id' => $response->conversationId,
        ]);

        return $response;
    }
}
