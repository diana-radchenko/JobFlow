<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobSelectionRequest;
use App\Models\UserWorkJobApplication;
use App\Models\WorkJob;
use App\Services\JobConversationService;
use App\Services\JobRecommendationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class JobSelectionController extends Controller
{
    public function jobSelection(JobSelectionRequest $request, JobRecommendationService $recommendations): Response
    {
        $filters = $request->validated();
        $annualMinimum = "salary_start * CASE LOWER(COALESCE(salary_period, 'annual')) WHEN 'hour' THEN 2080 WHEN 'hourly' THEN 2080 WHEN 'week' THEN 52 WHEN 'weekly' THEN 52 WHEN 'month' THEN 12 WHEN 'monthly' THEN 12 ELSE 1 END";
        $annualMaximum = "COALESCE(salary_end, salary_start) * CASE LOWER(COALESCE(salary_period, 'annual')) WHEN 'hour' THEN 2080 WHEN 'hourly' THEN 2080 WHEN 'week' THEN 52 WHEN 'weekly' THEN 52 WHEN 'month' THEN 12 WHEN 'monthly' THEN 12 ELSE 1 END";
        $query = WorkJob::published()
            ->withExists([
                'applications as applied' => fn ($query) => $query->where('user_id', auth()->id()),
                'savedBy as saved' => fn ($query) => $query->where('users.id', auth()->id()),
            ]);

        $view = $filters['view'] ?? 'all';
        $query->when($view === 'saved', fn ($query) => $query->whereHas('savedBy', fn ($saved) => $saved->where('users.id', auth()->id())));
        $query->when($view === 'applied', fn ($query) => $query->whereHas('applications', fn ($applications) => $applications->where('user_id', auth()->id())));
        $query->when($filters['keyword'] ?? null, fn ($q, $value) => $q->where(fn ($q) => $q
            ->where('title', 'like', "%{$value}%")
            ->orWhere('description', 'like', "%{$value}%")));
        $query->when($filters['industry'] ?? null, function ($query, $industry) {
            $query->whereIn('industry', [$industry, ...config("jobs.industry_aliases.{$industry}", [])]);
        });
        $query->when($filters['position_level'] ?? null, fn ($q, $value) => $q->where('position_level', $value));
        $query->when($filters['company'] ?? null, fn ($q, $value) => $q->where('company', 'like', "%{$value}%"));
        $query->when($filters['employment_type'] ?? null, fn ($q, $value) => $q->where('employment_type', $value));
        $query->when($filters['location'] ?? null, fn ($q, $value) => $q->where('location', 'like', "%{$value}%"));
        $query->when($filters['workplace_type'] ?? null, fn ($q, $value) => $q->where('workplace_type', $value));

        if (array_key_exists('salary_min', $filters) && $filters['salary_min'] !== null) {
            $query->whereRaw("{$annualMaximum} >= CAST(? AS REAL)", [(float) $filters['salary_min']]);
        }
        if (array_key_exists('salary_max', $filters) && $filters['salary_max'] !== null) {
            $query->whereRaw("{$annualMinimum} <= CAST(? AS REAL)", [(float) $filters['salary_max']]);
        }

        $query->when($filters['date_posted'] ?? null, fn ($q, $days) => $q->where('published_at', '>=', now()->subDays((int) $days)));

        match ($filters['sort'] ?? 'newest') {
            'salary_high' => $query->orderByRaw("{$annualMaximum} DESC"),
            'salary_low' => $query->orderByRaw("{$annualMinimum} ASC"),
            default => $query->orderByDesc('published_at'),
        };

        $jobs = $query->get();
        $user = $request->user();
        $matchingResume = $user->resumes()->where('is_primary', true)->first()
            ?? $user->resumes()->latest('updated_at')->first();

        $matches = $matchingResume
            ? $jobs->mapWithKeys(function (WorkJob $job) use ($matchingResume, $recommendations) {
                $match = $recommendations->forJob($matchingResume, $job);

                return [$job->id => [
                    'score' => $match['score'],
                    'criteria' => $match['criteria'],
                    'strong_matches' => $match['strong_matches'],
                    'gaps' => $match['gaps'],
                ]];
            })
            : collect();

        return Inertia::render('JobSelection', [
            'jobs' => $jobs,
            'filters' => $filters,
            'filterOptions' => [
                'industries' => config('jobs.industries'),
                'positionLevels' => config('jobs.position_levels'),
                'employmentTypes' => config('jobs.employment_types'),
                'workplaceTypes' => config('jobs.workplace_types'),
            ],
            'matchingResume' => $matchingResume?->only(['id', 'title']),
            'matches' => $matches,
        ]);
    }

    public function show(WorkJob $job): Response
    {
        abort_unless($job->user_id !== null && $job->status === 'published', 404);
        $job->loadCount('applications');
        $userApplication = auth()->user()->applications()->where('work_job_id', $job->id)->first();

        return Inertia::render('JobDetail', [
            'job' => $job,
            'userApplication' => $userApplication,
            'saved' => $job->savedBy()->where('users.id', auth()->id())->exists(),
            'resumes' => auth()->user()->resumes()->select('id', 'title')->orderByDesc('updated_at')->get(),
        ]);
    }

    public function apply(Request $request, WorkJob $job, JobConversationService $conversations): RedirectResponse
    {
        abort_unless($job->user_id !== null && $job->status === 'published', 404);
        $validated = $request->validate([
            'resume_id' => ['required', Rule::exists('resumes', 'id')->where('user_id', auth()->id())],
        ]);

        $application = UserWorkJobApplication::firstOrCreate(
            ['user_id' => auth()->id(), 'work_job_id' => $job->id],
            $validated,
        );

        $conversations->forApplication($application);

        return redirect()->route('job-selection.show', $job)->with('success', 'Application submitted successfully!');
    }
}
