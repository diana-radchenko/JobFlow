<?php

namespace App\Http\Controllers;

use App\Models\InterviewSession;
use App\Models\UserWorkJobApplication;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $applications = UserWorkJobApplication::with(['workJob'])
            ->where('user_id', auth()->id())
            ->get();

        $interviewSessions = InterviewSession::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        $profileFirstName = auth()->user()->profile?->first_name ?? ' ';

        return Inertia::render('Dashboard', [
            'applications' => $applications,
            'interviewSessions' => $interviewSessions,
            'profileFirstName' => $profileFirstName,
        ]);
    }
}
