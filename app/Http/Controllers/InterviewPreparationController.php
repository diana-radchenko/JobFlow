<?php

namespace App\Http\Controllers;

use App\Models\InterviewSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InterviewPreparationController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $activeSession = InterviewSession::where('user_id', $request->user()->id)
            ->where('status', 'in_progress')
            ->first();

        $pastSessions = InterviewSession::where('user_id', $request->user()->id)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->paginate(3);

        return Inertia::render('InterviewPreparation', [
            'activeSession' => $activeSession,
            'pastSessions' => Inertia::scroll($pastSessions),
        ]);
    }
}
