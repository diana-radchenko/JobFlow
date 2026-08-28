<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\InterviewSession;
use App\Models\UserWorkJobApplication;
use App\Models\WorkJob;
use App\Services\JobConversationService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class InterviewScheduleController extends Controller
{
    public function store(
        Request $request,
        WorkJob $job,
        UserWorkJobApplication $application,
        JobConversationService $conversations,
    ): RedirectResponse
    {
        $this->authorize('update', $job);
        abort_unless($application->work_job_id === $job->id, 404);

        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'timezone' => ['required', Rule::in(timezone_identifiers_list())],
            'duration_minutes' => ['nullable', 'integer', Rule::in([30, 45, 60, 90])],
            'interview_format' => ['nullable', Rule::in(['video', 'phone', 'in_person'])],
            'meeting_link' => ['nullable', 'url:http,https', 'max:2048'],
            'location' => ['nullable', 'string', 'max:500'],
            'employer_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $localDateTime = CarbonImmutable::createFromFormat(
            '!Y-m-d H:i',
            $validated['date'].' '.$validated['time'],
            $validated['timezone'],
        );

        if ($localDateTime->format('Y-m-d H:i') !== $validated['date'].' '.$validated['time'] || ! $localDateTime->isFuture()) {
            throw ValidationException::withMessages([
                'date' => 'The interview date and time must be a valid time in the future.',
            ]);
        }

        DB::transaction(function () use ($application, $job, $localDateTime, $validated, $conversations): void {
            $interview = InterviewSession::firstOrNew(['application_id' => $application->id]);
            $action = $interview->exists ? 'rescheduled' : 'scheduled';

            $interview->fill([
                'user_id' => $application->user_id,
                'employer_id' => auth()->id(),
                'resume_id' => $application->resume_id,
                'work_job_id' => $job->id,
                'scheduled_at' => $localDateTime->utc(),
                'timezone' => $validated['timezone'],
                'duration_minutes' => $validated['duration_minutes'] ?? 30,
                'interview_format' => $validated['interview_format'] ?? null,
                'meeting_link' => $validated['meeting_link'] ?? null,
                'location' => $validated['location'] ?? null,
                'employer_note' => $validated['employer_note'] ?? null,
                'cancelled_at' => null,
                'type' => 'job_interview',
                'complexity' => 'standard',
                'mode' => 'scheduled',
                'status' => 'scheduled',
            ])->save();

            $interview->events()->create([
                'changed_by' => auth()->id(),
                'action' => $action,
                ...$interview->only([
                    'scheduled_at', 'timezone', 'duration_minutes', 'interview_format',
                    'meeting_link', 'location', 'employer_note',
                ]),
            ]);

            $application->update(['status' => ApplicationStatus::InterviewScheduled]);
            $conversations->recordInterviewEvent($application, $interview, $action, auth()->id());
        });

        return back()->with('success', 'Interview scheduled successfully.');
    }

    public function destroy(
        WorkJob $job,
        UserWorkJobApplication $application,
        JobConversationService $conversations,
    ): RedirectResponse
    {
        $this->authorize('update', $job);
        abort_unless($application->work_job_id === $job->id, 404);

        DB::transaction(function () use ($application, $conversations): void {
            $interview = $application->interviewSession()->where('status', 'scheduled')->firstOrFail();
            $interview->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            $interview->events()->create([
                'changed_by' => auth()->id(),
                'action' => 'cancelled',
                ...$interview->only([
                    'scheduled_at', 'timezone', 'duration_minutes', 'interview_format',
                    'meeting_link', 'location', 'employer_note',
                ]),
            ]);
            $application->update(['status' => ApplicationStatus::Applied]);
            $conversations->recordInterviewEvent($application, $interview, 'cancelled', auth()->id());
        });

        return back()->with('success', 'Interview cancelled. The interview history was retained.');
    }
}

