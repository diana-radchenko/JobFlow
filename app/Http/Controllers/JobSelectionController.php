<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobSelectionRequest;
use App\Models\UserWorkJobApplication;
use App\Services\JobConversationService;
use App\Models\WorkJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class JobSelectionController extends Controller
{
    public function jobSelection(JobSelectionRequest $request): Response
    {
        $query = WorkJob::published()
            ->withExists([
                'applications as applied' => fn ($query) => $query->where('user_id', auth()->id()),
                'savedBy as saved' => fn ($query) => $query->where('users.id', auth()->id()),
            ]);
        $query->when($request->view === 'saved', fn ($query) => $query->whereHas('savedBy', fn ($saved) => $saved->where('users.id', auth()->id())));
        $query->when($request->view === 'applied', fn ($query) => $query->whereHas('applications', fn ($applications) => $applications->where('user_id', auth()->id())));
        $query->when($request->keyword, fn ($q, $value) => $q->where(fn ($q) => $q
            ->where('title', 'like', "%{$value}%")
            ->orWhere('description', 'like', "%{$value}%")));
        $query->when($request->industry, function ($query, $industry) {
            $query->whereIn('industry', [
                $industry,
                ...config("jobs.industry_aliases.{$industry}", []),
            ]);
        });
        $query->when($request->position_level, fn ($q, $value) => $q->where('position_level', $value));
        $query->when($request->company, fn ($q, $value) => $q->where('company', 'like', "%{$value}%"));
        $query->when($request->employment_type, fn ($q, $value) => $q->where('employment_type', $value));
        $query->when($request->location, fn ($q, $value) => $q->where('location', 'like', "%{$value}%"));
        $query->when($request->workplace_type, fn ($q, $value) => $q->where('workplace_type', $value));
        $query->when($request->salary_min, fn ($q, $value) => $q->where(fn ($salaryQuery) => $salaryQuery
            ->where('salary_end', '>=', $value)
            ->orWhere(fn ($fallback) => $fallback->whereNull('salary_end')->where('salary_start', '>=', $value))));
        $query->when($request->date_posted, fn ($q, $days) => $q->where('published_at', '>=', now()->subDays((int) $days)));

        $jobs = $query->orderByDesc('published_at')->get();

        return Inertia::render('JobSelection', [
            'jobs' => $jobs,
            'filters' => $request->only(['keyword', 'company', 'industry', 'position_level', 'employment_type', 'location', 'workplace_type', 'salary_min', 'date_posted', 'view']),
            'filterOptions' => [
                'industries' => config('jobs.industries'),
                'positionLevels' => config('jobs.position_levels'),
                'employmentTypes' => config('jobs.employment_types'),
                'workplaceTypes' => config('jobs.workplace_types'),
            ],
        ]);
    }

    public function show(WorkJob $job): Response
    {
        abort_unless($job->user_id !== null && $job->status === 'published', 404);
        $job->loadCount('applications');
        $userApplication = auth()->user()
            ->applications()
            ->where('work_job_id', $job->id)
            ->first();

        return Inertia::render('JobDetail', [
            'job' => $job,
            'userApplication' => $userApplication,
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
            [
                'user_id' => auth()->id(),
                'work_job_id' => $job->id,
            ],
            $validated,
        );

        $conversations->forApplication($application);

        return redirect()->route('job-selection.show', $job)->with('success', 'Application submitted successfully!');
    }
}


