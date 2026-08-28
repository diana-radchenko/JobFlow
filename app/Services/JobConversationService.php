<?php

namespace App\Services;

use App\Models\InterviewSession;
use App\Models\JobConversation;
use App\Models\UserWorkJobApplication;

class JobConversationService
{
    public function forApplication(UserWorkJobApplication $application): JobConversation
    {
        $application->loadMissing('workJob');

        abort_if($application->workJob->user_id === null, 404);

        return JobConversation::firstOrCreate(
            ['application_id' => $application->id],
            [
                'work_job_id' => $application->work_job_id,
                'employer_user_id' => $application->workJob->user_id,
                'candidate_user_id' => $application->user_id,
            ],
        );
    }

    public function recordInterviewEvent(
        UserWorkJobApplication $application,
        InterviewSession $interview,
        string $action,
        int $actorId,
    ): void {
        $conversation = $this->forApplication($application);
        $localDate = $interview->scheduled_at
            ->copy()
            ->setTimezone($interview->timezone ?? 'UTC')
            ->format('F j, Y · g:i A T');
        $label = match ($action) {
            'scheduled' => 'Interview scheduled',
            'rescheduled' => 'Interview rescheduled',
            'cancelled' => 'Interview cancelled',
            default => 'Interview updated',
        };

        $conversation->messages()->create([
            'sender_id' => $actorId,
            'type' => 'system',
            'body' => $label."\n".$localDate,
            'metadata' => [
                'event' => 'interview_'.$action,
                'interview_session_id' => $interview->id,
            ],
        ]);
        $conversation->touch();
    }
}

