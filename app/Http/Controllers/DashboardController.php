<?php

namespace App\Http\Controllers;

use App\Models\InterviewSession;
use App\Models\Resume;
use App\Models\UserWorkJobApplication;
use App\Services\JobRecommendationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, JobRecommendationService $recommendations): Response
    {
        $applications = UserWorkJobApplication::with(['workJob'])
            ->where('user_id', auth()->id())
            ->latest('updated_at')
            ->get();

        $interviewSessions = InterviewSession::with('workJob:id,title,company')
            ->where('user_id', auth()->id())
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->orderBy('scheduled_at')
            ->get();

        $nextInterview = $interviewSessions->first(
            fn (InterviewSession $session) => $session->scheduled_at?->isFuture(),
        );

        $profileFirstName = str(auth()->user()->email)->before('@')->toString();

        $resumes = auth()->user()->resumes()
            ->select(['id', 'title'])
            ->orderByDesc('updated_at')
            ->get();
        $selectedResume = ($request->filled('resume_id')
            ? auth()->user()->resumes()->find($request->integer('resume_id'))
            : null) ?? auth()->user()->resumes()->latest('updated_at')->first();
        $recommendedJobs = $selectedResume ? $recommendations->forResume($selectedResume) : [];

        return Inertia::render('Dashboard', [
            'applications' => $applications,
            'interviewSessions' => $interviewSessions,
            'nextInterview' => $nextInterview,
            'dashboardSummary' => [
                'applications' => $applications->count(),
                'interviews' => $interviewSessions->count(),
                'resumeCompleteness' => $this->resumeCompleteness($selectedResume),
                'recommendedMatches' => count($recommendedJobs),
            ],
            'profileFirstName' => $profileFirstName,
            'resumes' => $resumes,
            'selectedResumeId' => $selectedResume?->id,
            'recommendedJobs' => $recommendedJobs,
            'articles' => config('dashboard.articles', []),
        ]);
    }

    private function resumeCompleteness(?Resume $resume): ?int
    {
        if (! $resume) {
            return null;
        }

        $resume->loadCount(['skills', 'projects', 'educations', 'workExperiences', 'languages'])
            ->loadMissing('additionalInformation');

        $profile = auth()->user()->profile;
        $sections = [
            filled($resume->title),
            $profile && collect(['first_name', 'last_name', 'phone', 'city', 'country'])
                ->contains(fn (string $field) => filled($profile->{$field})),
            $resume->skills_count > 0,
            $resume->work_experiences_count > 0,
            $resume->educations_count > 0,
            $resume->projects_count > 0,
            $resume->languages_count > 0,
            $resume->additionalInformation && collect([
                $resume->additionalInformation->certifications,
                $resume->additionalInformation->interests,
                $resume->additionalInformation->notes,
            ])->contains(fn ($value) => filled($value)),
        ];

        return (int) round(collect($sections)->filter()->count() / count($sections) * 100);
    }
}

