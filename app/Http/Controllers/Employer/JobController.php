<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkJobRequest;
use App\Models\WorkJob;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class JobController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Employer/Jobs/Index', [
            'jobs' => auth()->user()->workJobs()
                ->withCount('applications')
                ->orderByDesc('updated_at')
                ->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Employer/Jobs/Form', [
            'job' => null,
            'jobOptions' => $this->jobOptions(),
        ]);
    }

    public function show(WorkJob $job): Response
    {
        $this->authorize('view', $job);

        return Inertia::render('Employer/Jobs/Show', [
            'job' => $job,
            'jobOptions' => $this->jobOptions(),
            'applications' => $job->applications()
                ->with('user:id,name,email')
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function edit(WorkJob $job): Response
    {
        $this->authorize('update', $job);

        return Inertia::render('Employer/Jobs/Form', [
            'job' => $job,
        ]);
    }

    public function store(StoreWorkJobRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['status'] ??= 'published';
        if ($data['status'] === 'published') $data['published_at'] = now();
        $job = auth()->user()->workJobs()->create($data);

        return redirect()->route('employer.jobs.show', $job)->with('success', 'Job posted successfully.');
    }

    public function update(StoreWorkJobRequest $request, WorkJob $job): RedirectResponse
    {
        $this->authorize('update', $job);

        $data = $request->validated();
        if (($data['status'] ?? $job->status) === 'published' && $job->published_at === null) $data['published_at'] = now();
        $job->update($data);

        return redirect()->route('employer.jobs.show', $job)->with('success', 'Job updated successfully.');
    }

    public function destroy(WorkJob $job): RedirectResponse
    {
        $this->authorize('delete', $job);

        $job->delete();

        return redirect()->route('employer.jobs.index')->with('success', 'Job deleted successfully.');
    }

    /** @return array<string, mixed> */
    private function jobOptions(): array
    {
        return ['industries' => config('jobs.industries'), 'positionLevels' => config('jobs.position_levels'),
            'employmentTypes' => config('jobs.employment_types'), 'workplaceTypes' => config('jobs.workplace_types')];
    }
}
