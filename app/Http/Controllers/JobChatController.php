<?php

namespace App\Http\Controllers;

use App\Models\JobConversation;
use App\Models\UserWorkJobApplication;
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
            ->with(['workJob:id,title,company', 'employer:id,name', 'candidate:id,name', 'messages.sender:id,name'])
            ->withCount(['messages as unread_count' => fn ($query) => $query->whereNull('read_at')->where('sender_id', '!=', $user->id)])
            ->latest('updated_at')->get();

        $selected = $conversations->firstWhere('id', (int) $request->query('conversation')) ?? $conversations->first();
        if ($selected) $selected->messages()->whereNull('read_at')->where('sender_id', '!=', $user->id)->update(['read_at' => now()]);

        return Inertia::render('Chat/Index', ['conversations' => $conversations, 'selectedConversationId' => $selected?->id]);
    }

    public function store(Request $request, UserWorkJobApplication $application): RedirectResponse
    {
        $user = $request->user();
        $application->loadMissing('workJob');
        abort_if($application->workJob->user_id === null, 404);
        abort_unless($user->id === $application->user_id || $user->id === $application->workJob->user_id, 403);
        $validated = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $conversation = JobConversation::firstOrCreate(['application_id' => $application->id], [
            'work_job_id' => $application->work_job_id,
            'employer_user_id' => $application->workJob->user_id,
            'candidate_user_id' => $application->user_id,
        ]);
        $conversation->messages()->create(['sender_id' => $user->id, 'body' => $validated['body']]);
        $conversation->touch();

        return redirect()->route('job-chat.index', ['conversation' => $conversation->id]);
    }
}
