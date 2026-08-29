<?php

namespace App\Http\Controllers;

use App\Models\UserWorkJobApplication;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RequestTrackerController extends Controller
{
    public function show(): Response
    {
        $applications = UserWorkJobApplication::with([
            'workJob',
            'interviewSession' => fn ($query) => $query->where('user_id', auth()->id()),
        ])
            ->where('user_id', auth()->id())
            ->latest('created_at')
            ->get();

        $applications->each(function (UserWorkJobApplication $application) {
            $application->setAttribute('tracker_stage', $this->stage($application));
        });

        $scheduledInterviews = $applications->filter(fn (UserWorkJobApplication $application) =>
            $this->hasScheduledInterview($application)
        )->count();

        return Inertia::render('RequestTracker', [
            'applications' => $applications,
            'funnel' => [
                'applied' => $applications->count(),
                'viewed' => $applications->filter(fn (UserWorkJobApplication $application) =>
                    $application->viewed_at !== null || in_array($application->tracker_stage, ['Interview', 'Offer', 'Rejected'], true)
                )->count(),
                'interview' => $scheduledInterviews,
                'offer' => $applications->whereIn('tracker_stage', ['Offer'])->count(),
            ],
        ]);
    }

    private function hasScheduledInterview(UserWorkJobApplication $application): bool
    {
        return $application->interviewSession?->status === 'scheduled'
            && $application->interviewSession->scheduled_at !== null
            && $application->interviewSession->cancelled_at === null;
    }

    private function stage(UserWorkJobApplication $application): string
    {
        $status = $application->status->value;

        if ($status === 'rejected') {
            return 'Rejected';
        }

        if (in_array($status, ['offer', 'hired'], true)) {
            return 'Offer';
        }

        if ($this->hasScheduledInterview($application)) {
            return 'Interview';
        }

        if ($status === 'shortlisted') {
            return 'Shortlisted';
        }

        return $application->viewed_at ? 'Viewed' : 'Applied';
    }

    public function destroy(UserWorkJobApplication $userWorkJobApplication): RedirectResponse
    {
        abort_unless($userWorkJobApplication->user_id === auth()->id(), 403);

        $userWorkJobApplication->delete();

        return redirect()->route('request-tracker');
    }
}
