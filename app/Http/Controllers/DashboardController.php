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

        $user = $request->user();
        $profileFirstName = str($user->name ?: $user->email)
            ->before(' ')
            ->before('@')
            ->toString();

        $resumes = $user->resumes()
            ->select(['id', 'title'])
            ->orderByDesc('updated_at')
            ->get();
        $selectedResume = ($request->filled('resume_id')
            ? $user->resumes()->find($request->integer('resume_id'))
            : null) ?? $user->resumes()->latest('updated_at')->first();
        $recommendedJobs = $selectedResume ? $recommendations->forResume($selectedResume) : [];
        $resumeSummary = $this->resumeSummary($selectedResume);
        $jobSearchProgress = $this->jobSearchProgress(
            $resumeSummary,
            $applications,
            $user->savedWorkJobs()->exists(),
            InterviewSession::where('user_id', $user->id)
                ->whereIn('status', ['scheduled', 'completed'])
                ->exists(),
        );

        return Inertia::render('Dashboard', [
            'applications' => $applications,
            'interviewSessions' => $interviewSessions,
            'nextInterview' => $nextInterview,
            'dashboardSummary' => [
                'applications' => $applications->count(),
                'interviews' => $interviewSessions->count(),
                'offers' => $applications->whereIn('status', ['offer', 'hired'])->count(),
                'resumeCompleteness' => $resumeSummary['completeness'] ?? null,
                'recommendedMatches' => count($recommendedJobs),
                'jobSearchProgress' => $jobSearchProgress['percentage'],
            ],
            'profileFirstName' => $profileFirstName,
            'resumes' => $resumes,
            'selectedResumeId' => $selectedResume?->id,
            'selectedResumeSummary' => $resumeSummary,
            'jobSearchMilestones' => $jobSearchProgress['milestones'],
            'recommendedJobs' => $recommendedJobs,
            'nextSteps' => $this->nextSteps($selectedResume, $resumeSummary['completeness'] ?? null, $applications, $nextInterview, $recommendedJobs),
            'recentActivity' => $this->recentActivity($applications, $interviewSessions),
            'articles' => config('dashboard.articles', []),
        ]);
    }

    /**
     * @param  Collection<int, UserWorkJobApplication>  $applications
     * @param  Collection<int, mixed>|array<int, mixed>  $recommendedJobs
     * @return array<int, array{title: string, description: string, href: string, action: string}>
     */
    private function nextSteps(?Resume $resume, ?int $resumeCompleteness, Collection $applications, ?InterviewSession $nextInterview, Collection|array $recommendedJobs): array
    {
        $steps = collect();
        $recommendedJobs = collect($recommendedJobs);
        if (! $resume) {
            $steps->push(['title' => 'Create your resume', 'description' => 'Add a resume to unlock applications and matching jobs.', 'href' => route('resumes.index'), 'action' => 'Create Resume']);
        } elseif ($resumeCompleteness !== null && $resumeCompleteness < 75) {
            $steps->push(['title' => 'Improve your resume', 'description' => "Your resume is {$resumeCompleteness}% complete.", 'href' => route('resume-editor.show', $resume), 'action' => 'Update Resume']);
        }

        if ($nextInterview) {
            $steps->push(['title' => 'Prepare for your upcoming interview', 'description' => $nextInterview->workJob?->title ?? 'Scheduled employer interview', 'href' => route('interview-preparation'), 'action' => 'Practice with AI']);
        }

        if ($recommendedJobs->isNotEmpty()) {
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

    /**
     * @return array{title: string, completeness: int, checklist: array<int, array{label: string, complete: bool}>, href: string}|null
     */
    private function resumeSummary(?Resume $resume): ?array
    {
        if (! $resume) {
            return null;
        }

        $resume->loadCount(['skills', 'projects', 'educations', 'workExperiences', 'languages'])
            ->loadMissing('additionalInformation');

        $profile = auth()->user()->profile;
        $hasProfile = $profile && collect(['first_name', 'last_name', 'phone', 'city', 'country'])
            ->contains(fn (string $field) => filled($profile->{$field}));
        $sections = [
            filled($resume->title),
            $hasProfile,
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

        return [
            'title' => $resume->title,
            'completeness' => (int) round(collect($sections)->filter()->count() / count($sections) * 100),
            'checklist' => [
                ['label' => 'Personal details added', 'complete' => (bool) $hasProfile],
                ['label' => 'Skills added', 'complete' => $resume->skills_count > 0],
                ['label' => 'Work experience added', 'complete' => $resume->work_experiences_count > 0],
                ['label' => 'Education added', 'complete' => $resume->educations_count > 0],
            ],
            'href' => route('resume-editor.show', $resume),
        ];
    }

    /**
     * @param  array{title: string, completeness: int, checklist: array<int, array{label: string, complete: bool}>, href: string}|null  $resumeSummary
     * @param  Collection<int, UserWorkJobApplication>  $applications
     * @return array{percentage: int, milestones: array<int, array{label: string, weight: int, complete: bool}>}
     */
    private function jobSearchProgress(?array $resumeSummary, Collection $applications, bool $hasSavedJob, bool $hasInterview): array
    {
        $hasApplication = $applications->isNotEmpty();
        $hasEmployerInteraction = $applications->contains(
            fn (UserWorkJobApplication $application) => $application->viewed_at !== null
                || $application->status->value !== 'applied',
        );
        $hasOffer = $applications->contains(
            fn (UserWorkJobApplication $application) => in_array($application->status->value, ['offer', 'hired'], true),
        );

        $milestones = [
            ['label' => 'Resume created', 'weight' => 10, 'complete' => $resumeSummary !== null],
            ['label' => 'Resume optimized', 'weight' => 10, 'complete' => ($resumeSummary['completeness'] ?? 0) >= 50],
            ['label' => 'Job saved', 'weight' => 10, 'complete' => $hasSavedJob],
            ['label' => 'Application submitted', 'weight' => 20, 'complete' => $hasApplication],
            ['label' => 'Employer response', 'weight' => 15, 'complete' => $hasEmployerInteraction],
            ['label' => 'Interview scheduled', 'weight' => 15, 'complete' => $hasInterview],
            ['label' => 'Offer received', 'weight' => 20, 'complete' => $hasOffer],
        ];

        return [
            'percentage' => collect($milestones)->where('complete', true)->sum('weight'),
            'milestones' => $milestones,
        ];
    }
}


