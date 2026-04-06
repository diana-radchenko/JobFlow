<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobSelectionRequest;
use App\Models\UserWorkJobApplication;
use App\Models\WorkJob;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class JobSelectionController extends Controller
{
    public function jobSelection(JobSelectionRequest $request): Response
    {
        $query = WorkJob::query();

        if ($request->filled('region') && $request->region !== 'does-not-matter') {
            $query->where('location', 'like', '%'.$request->region.'%');
        }

        // own is a filter, where user writes his own dynamic filter value for salary
        if ($request->filled('incomeLevel') && $request->incomeLevel !== 'does-not-matter') {
            if ($request->incomeLevel === 'own' && $request->filled('ownSalary')) {
                $query->where('salary_start', '>=', $request->ownSalary);
            } elseif (is_numeric($request->incomeLevel)) {
                $query->where('salary_start', '>=', $request->incomeLevel);
            }
        }

        $jobs = $query->get();

        return Inertia::render('JobSelection', [
            'jobs' => $jobs,
            'filters' => $request->only(['incomeLevel', 'region', 'ownSalary']),
        ]);
    }

    public function show(WorkJob $job): Response
    {
        $userApplication = auth()->user()
            ->applications()
            ->where('work_job_id', $job->id)
            ->first();

        return Inertia::render('JobDetail', [
            'job' => $job,
            'userApplication' => $userApplication,
        ]);
    }

    public function apply(WorkJob $job): RedirectResponse
    {
        UserWorkJobApplication::firstOrCreate(
            [
                'user_id' => auth()->id(),
                'work_job_id' => $job->id,
            ],
        );

        return redirect()->route('job-selection.show', $job)->with('success', 'Application submitted successfully!');
    }
}
