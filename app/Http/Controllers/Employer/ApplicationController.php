<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\UserWorkJobApplication;
use App\Models\WorkJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    /**
     * Opening an application counts as the employer having seen it, which is what
     * the candidate's "Percentage of Viewed Applications" chart reports on. Only
     * the first visit is recorded.
     */
    public function show(WorkJob $job, UserWorkJobApplication $application): Response
    {
        $this->authorize('view', $job);

        if ($application->viewed_at === null) {
            $application->update(['viewed_at' => now()]);
        }

        return Inertia::render('Employer/Applications/Show', [
            'job' => $job,
            'application' => $application->load([
                'user:id,name,email',
                'resume.skills',
                'resume.projects',
                'resume.educations',
                'resume.workExperiences',
                'resume.volunteerExperiences',
                'resume.leadershipActivities',
                'resume.publications',
                'resume.awardHonors',
                'resume.languages',
                'resume.additionalInformation',
            ]),
        ]);
    }

    public function update(Request $request, WorkJob $job, UserWorkJobApplication $application): RedirectResponse
    {
        $this->authorize('update', $job);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(ApplicationStatus::class)->only([
                ApplicationStatus::Rejected,
                ApplicationStatus::InterviewScheduled,
            ])],
        ]);

        $application->update($validated);

        return back()->with('success', 'Application updated successfully.');
    }
}

