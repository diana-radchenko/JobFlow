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
        $activeSession = InterviewSession::with(['resume:id,title', 'workJob:id,title,company'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'in_progress')
            ->first();

        $pastSessions = InterviewSession::with(['resume:id,title', 'workJob:id,title,company'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'completed')
            ->whereIn('mode', ['text', 'live'])
            ->whereNull('application_id')
            ->latest()
            ->orderByDesc('id')
            ->paginate(5);
        $upcomingInterviews = InterviewSession::with('workJob:id,title,company')
            ->where('user_id', $request->user()->id)
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>', now())
            ->orderBy('scheduled_at')
            ->get();

        $resumes = $request->user()->resumes()
            ->orderByDesc('updated_at')
            ->get(['id', 'title']);

        $applications = $request->user()->applications()
            ->with('workJob:id,title,company')
            ->latest()
            ->get()
            ->map(fn ($application) => [
                'id' => $application->id,
                'work_job_id' => $application->work_job_id,
                'work_job' => $application->workJob?->only(['id', 'title', 'company']),
            ]);

        return Inertia::render('InterviewPreparation', [
            'activeSession' => $activeSession,
            'pastSessions' => Inertia::scroll($pastSessions),
            'resumes' => $resumes,
            'applications' => $applications,
            'upcomingInterviews' => $upcomingInterviews,
        ]);
    }
}

