<?php

namespace App\Http\Controllers;

use App\Models\InterviewSession;
use App\Models\Resume;
use App\Models\UserWorkJobApplication;
use App\Services\JobRecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
            'nextSteps' => $this->nextSteps($selectedResume, $applications, $nextInterview, $recommendedJobs),
            'recentActivity' => $this->recentActivity($applications, $interviewSessions),
            'articles' => config('dashboard.articles', []),
        ]);
    }

    /**
     * @param  Collection<int, UserWorkJobApplication>  $applications
     * @param  array<int, mixed>  $recommendedJobs
     * @return array<int, array{title: string, description: string, href: string, action: string}>
     */
    private function nextSteps(?Resume $resume, Collection $applications, ?InterviewSession $nextInterview, array $recommendedJobs): array
    {
        $steps = collect();
        $completeness = $this->resumeCompleteness($resume);

        if (! $resume) {
            $steps->push(['title' => 'Create your resume', 'description' => 'Add a resume to unlock applications and matching jobs.', 'href' => route('resumes.index'), 'action' => 'Create Resume']);
        } elseif ($completeness !== null && $completeness < 75) {
            $steps->push(['title' => 'Improve your resume', 'description' => "Your resume is {$completeness}% complete.", 'href' => route('resume-editor.show', $resume), 'action' => 'Update Resume']);
        }

        if ($nextInterview) {
            $steps->push(['title' => 'Prepare for your upcoming interview', 'description' => $nextInterview->workJob?->title ?? 'Scheduled employer interview', 'href' => route('interview-preparation'), 'action' => 'Practice with AI']);
        }

        if ($recommendedJobs !== []) {
            $steps->push(['title' => 'Review matching jobs', 'description' => count($recommendedJobs).' published vacancies match your resume.', 'href' => route('job-selection'), 'action' => 'Browse Matches']);
        } elseif ($applications->isEmpty()) {
            $steps->push(['title' => 'Start your job search', 'description' => 'Browse real vacancies published by employers.', 'href' => route('job-selection'), 'action' => 'Browse Jobs']);
        } else {
            $steps->push(['title' => 'Review your applications', 'description' => 'Check current employer activity and next steps.', 'href' => route('request-tracker'), 'action' => 'View Applications']);
        }

        return $steps->take(3)->values()->all();
    }

    /**
     * @param  Collection<int, UserWorkJobApplication>  $applications
     * @param  Collection<int, InterviewSession>  $interviews
     * @return array<int, array{event: string, company: string, vacancy: string, occurred_at: mixed}>
     */
    private function recentActivity(Collection $applications, Collection $interviews): array
    {
        $activity = collect();

        foreach ($applications as $application) {
            $base = [
                'company' => $application->workJob?->company ?? 'Employer',
                'vacancy' => $application->workJob?->title ?? 'Vacancy',
            ];
            $activity->push([...$base, 'event' => 'Application submitted', 'occurred_at' => $application->created_at]);

            if ($application->viewed_at) {
                $activity->push([...$base, 'event' => 'Application viewed', 'occurred_at' => $application->viewed_at]);
            }

            $statusEvent = match ($application->status->value) {
                'shortlisted' => 'Application shortlisted',
                'offer', 'hired' => 'Offer received',
                default => null,
            };

            if ($statusEvent) {
                $activity->push([...$base, 'event' => $statusEvent, 'occurred_at' => $application->updated_at]);
            }
        }

        foreach ($interviews as $interview) {
            $activity->push([
                'event' => 'Interview scheduled',
                'company' => $interview->workJob?->company ?? 'Employer',
                'vacancy' => $interview->workJob?->title ?? 'Interview',
                'occurred_at' => $interview->updated_at,
            ]);
        }

        return $activity->sortByDesc('occurred_at')->take(5)->values()->all();
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


