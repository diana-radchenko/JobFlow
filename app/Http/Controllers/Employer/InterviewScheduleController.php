<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\InterviewSession;
use App\Models\UserWorkJobApplication;
use App\Models\WorkJob;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InterviewScheduleController extends Controller
{
    public function store(Request $request, WorkJob $job, UserWorkJobApplication $application): RedirectResponse
    {
        $this->authorize('update', $job);
        abort_unless($application->work_job_id === $job->id, 404);

        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'timezone' => ['required', Rule::in(config('jobs.timezones'))],
            'duration_minutes' => ['nullable', 'integer', Rule::in([30, 45, 60, 90])],
            'employer_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $scheduledAt = CarbonImmutable::createFromFormat('Y-m-d H:i', $validated['date'].' '.$validated['time'], $validated['timezone'])->utc();
        InterviewSession::updateOrCreate(['application_id' => $application->id], [
            'user_id' => $application->user_id,
            'employer_id' => auth()->id(),
            'resume_id' => $application->resume_id,
            'work_job_id' => $job->id,
            'scheduled_at' => $scheduledAt,
            'timezone' => $validated['timezone'],
            'duration_minutes' => $validated['duration_minutes'] ?? 30,
            'employer_note' => $validated['employer_note'] ?? null,
            'type' => 'job_interview', 'complexity' => 'standard', 'mode' => 'scheduled', 'status' => 'scheduled',
        ]);
        $application->update(['status' => ApplicationStatus::InterviewScheduled]);

        return back()->with('success', 'Interview scheduled successfully.');
    }
}
