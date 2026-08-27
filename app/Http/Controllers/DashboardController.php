<?php

namespace App\Http\Controllers;

use App\Models\InterviewSession;
use App\Models\UserWorkJobApplication;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\JobRecommendationService;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, JobRecommendationService $recommendations): Response
    {
        $applications = UserWorkJobApplication::with(['workJob'])
            ->where('user_id', auth()->id())
            ->get();

        $interviewSessions = InterviewSession::with('workJob:id,title,company')->where('user_id', auth()->id())
            ->where('status', 'scheduled')->whereNotNull('scheduled_at')->orderBy('scheduled_at')
            ->get();

        $profileFirstName = str(auth()->user()->email)->before('@')->toString();

        $resumes = auth()->user()->resumes()
            ->select(['id', 'title'])
            ->orderByDesc('updated_at')
            ->get();
        $selectedResume = $request->filled('resume_id') ? auth()->user()->resumes()->find($request->integer('resume_id')) : auth()->user()->resumes()->latest('updated_at')->first();

        return Inertia::render('Dashboard', [
            'applications' => $applications,
            'interviewSessions' => $interviewSessions,
            'profileFirstName' => $profileFirstName,
            'resumes' => $resumes,
            'selectedResumeId' => $selectedResume?->id,
            'recommendedJobs' => $selectedResume ? $recommendations->forResume($selectedResume) : [],
        ]);
    }
}

