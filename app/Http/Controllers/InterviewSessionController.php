<?php

namespace App\Http\Controllers;

use App\Ai\Agents\InterviewAgent;
use App\Data\InterviewContextData;
use App\Models\InterviewSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InterviewSessionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'string'],
            'complexity' => ['required', 'string'],
            'mode' => ['required', 'string', 'in:text'], // currently only supporting text mode
        ]);

        // Check if there is an active session
        $activeSession = InterviewSession::where('user_id', $request->user()->id)
            ->where('status', 'in_progress')
            ->first();

        if ($activeSession) {
            return redirect()->route('interview-session.show', $activeSession);
        }

        $session = InterviewSession::create([
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'complexity' => $validated['complexity'],
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

        return Inertia::render('Interview/Chat', [
            'session' => $session,
            'messages' => $messages,
        ]);
    }

    public function message(Request $request, InterviewSession $session)
    {
        if ($session->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);

        $context = InterviewContextData::fromUser($request->user());

        $agent = new InterviewAgent(
            $session->type,
            $session->complexity,
            $context->resumeContext(),
            $context->jobContext(),
        );

        if ($session->conversation_id) {
            $response = $agent->continue($session->conversation_id, as: $request->user())->prompt($validated['message'], model: 'gpt-5.4-nano');
        } else {
            $response = $agent->forUser($request->user())->prompt($validated['message'], model: 'gpt-5.4-nano');

            $session->update([
                'conversation_id' => $response->conversationId,
            ]);
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
        if ($session->user_id !== $request->user()->id) {
            abort(403);
        }

        $session->update(['status' => 'completed']);

        return redirect()->route('interview-preparation');
    }
}
