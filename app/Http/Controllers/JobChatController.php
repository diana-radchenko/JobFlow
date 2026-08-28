<?php

namespace App\Http\Controllers;

use App\Models\JobConversation;
use App\Models\UserWorkJobApplication;
use App\Services\JobConversationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobChatController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $conversations = JobConversation::query()
            ->where(fn ($query) => $query->where('employer_user_id', $user->id)->orWhere('candidate_user_id', $user->id))
            ->with([
                'workJob:id,title,company',
                'employer:id,name',
                'candidate:id,name',
                'latestMessage.sender:id,name',
            ])
            ->withCount(['messages as unread_count' => fn ($query) => $query->whereNull('read_at')->where('sender_id', '!=', $user->id)])
            ->latest('updated_at')->get();

        $selected = $conversations->firstWhere('id', (int) $request->query('conversation')) ?? $conversations->first();
        if ($selected) {
            $selected->messages()
                ->whereNull('read_at')
                ->where('sender_id', '!=', $user->id)
                ->update(['read_at' => now()]);
            $selected->unread_count = 0;
            $selected->load([
                'messages' => fn ($query) => $query->oldest('created_at'),
                'messages.sender:id,name',
            ]);
        }

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations,
            'selectedConversation' => $selected,
            'currentUser' => [
                'id' => $user->id,
                'role' => $user->role()?->value,
            ],
        ]);
    }

    public function store(
        Request $request,
        UserWorkJobApplication $application,
        JobConversationService $conversations,
    ): RedirectResponse
    {
        $user = $request->user();
        $application->loadMissing('workJob');
        abort_if($application->workJob->user_id === null, 404);
        abort_unless($user->id === $application->user_id || $user->id === $application->workJob->user_id, 403);
        $validated = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $conversation = $conversations->forApplication($application);
        $conversation->messages()->create([
            'sender_id' => $user->id,
            'type' => 'user',
            'body' => $validated['body'],
        ]);
        $conversation->touch();

        return redirect()->route('job-chat.index', ['conversation' => $conversation->id]);
    }
}

