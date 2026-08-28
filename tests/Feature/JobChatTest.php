<?php

use App\Enums\ApplicationStatus;
use App\Models\JobConversation;
use App\Models\JobMessage;
use App\Models\Resume;
use App\Models\User;
use App\Models\UserWorkJobApplication;
use App\Models\WorkJob;

function jobChatFixture(): array
{
    $employer = User::factory()->employer()->create();
    $candidate = User::factory()->create();
    $resume = Resume::create(['user_id' => $candidate->id, 'title' => 'Product Resume']);
    $job = WorkJob::factory()->for($employer, 'employer')->create([
        'title' => 'Product Designer',
        'company' => 'FLOW Studio',
        'status' => 'published',
    ]);
    $application = UserWorkJobApplication::create([
        'user_id' => $candidate->id,
        'work_job_id' => $job->id,
        'resume_id' => $resume->id,
        'status' => ApplicationStatus::Applied,
    ]);

    return compact('employer', 'candidate', 'resume', 'job', 'application');
}

function jobChatInterviewPayload(array $overrides = []): array
{
    $date = now('America/Chicago')->addWeek()->startOfHour();

    return [
        'date' => $date->format('Y-m-d'),
        'time' => $date->format('H:i'),
        'timezone' => 'America/Chicago',
        'duration_minutes' => 45,
        'interview_format' => 'video',
        'meeting_link' => 'https://meet.example.com/interview',
        ...$overrides,
    ];
}

test('candidate and employer exchange one shared conversation with recipient unread state', function () {
    extract(jobChatFixture());
    $url = route('job-chat.messages.store', $application);

    $this->actingAs($candidate)->post($url, [
        'body' => 'Hello, I have applied for this role.',
        'sender_id' => $employer->id,
    ])->assertRedirect();

    $conversation = JobConversation::sole();
    $candidateMessage = JobMessage::sole();
    expect($conversation->application_id)->toBe($application->id)
        ->and($conversation->candidate_user_id)->toBe($candidate->id)
        ->and($conversation->employer_user_id)->toBe($employer->id)
        ->and($candidateMessage->sender_id)->toBe($candidate->id)
        ->and($candidateMessage->read_at)->toBeNull();

    $this->actingAs($employer)
        ->get(route('job-chat.index', ['conversation' => $conversation->id]))
        ->assertInertia(fn ($page) => $page
            ->where('currentUser.role', 'employer')
            ->where('selectedConversation.messages.0.body', 'Hello, I have applied for this role.')
            ->where('selectedConversation.candidate.name', $candidate->name));

    expect($candidateMessage->refresh()->read_at)->not->toBeNull();

    $this->actingAs($employer)->post($url, ['body' => 'Thank you. We would like to talk.'])->assertRedirect();
    $reply = JobMessage::latest('id')->first();
    expect($reply->sender_id)->toBe($employer->id)->and($reply->read_at)->toBeNull();

    $this->actingAs($candidate)
        ->get(route('job-chat.index', ['conversation' => $conversation->id]))
        ->assertInertia(fn ($page) => $page
            ->where('currentUser.role', 'candidate')
            ->where('selectedConversation.messages.1.body', 'Thank you. We would like to talk.')
            ->where('selectedConversation.work_job.company', 'FLOW Studio'));

    expect($reply->refresh()->read_at)->not->toBeNull();
});

test('chat forbids unrelated users and never trusts a forged sender id', function () {
    extract(jobChatFixture());
    $url = route('job-chat.messages.store', $application);
    $outsiderCandidate = User::factory()->create();
    $outsiderEmployer = User::factory()->employer()->create();

    $this->actingAs($outsiderCandidate)->post($url, ['body' => 'Forged'])->assertForbidden();
    $this->actingAs($outsiderEmployer)->post($url, ['body' => 'Forged'])->assertForbidden();
    expect(JobConversation::count())->toBe(0)->and(JobMessage::count())->toBe(0);

    $this->actingAs($candidate)->post($url, [
        'body' => 'A valid candidate message',
        'sender_id' => $outsiderEmployer->id,
    ])->assertRedirect();
    expect(JobMessage::sole()->sender_id)->toBe($candidate->id);

    $this->actingAs($outsiderCandidate)->get(route('job-chat.index'))
        ->assertInertia(fn ($page) => $page->has('conversations', 0)->where('selectedConversation', null));
    $this->actingAs($outsiderEmployer)->get(route('job-chat.index'))
        ->assertInertia(fn ($page) => $page->has('conversations', 0)->where('selectedConversation', null));
});

test('interview schedule reschedule and cancellation use the same application conversation', function () {
    extract(jobChatFixture());
    $url = route('employer.interviews.store', [$job, $application]);

    $this->actingAs($employer)->post($url, jobChatInterviewPayload())->assertSessionHasNoErrors();
    $this->actingAs($employer)->post($url, jobChatInterviewPayload([
        'date' => now('America/Chicago')->addDays(9)->format('Y-m-d'),
    ]))->assertSessionHasNoErrors();
    $this->actingAs($employer)
        ->delete(route('employer.interviews.destroy', [$job, $application]))
        ->assertSessionHasNoErrors();

    $conversation = JobConversation::sole();
    expect($conversation->application_id)->toBe($application->id)
        ->and($conversation->messages()->where('type', 'system')->count())->toBe(3)
        ->and($conversation->messages()->pluck('body')->all()[0])->toContain('Interview scheduled')
        ->and($conversation->messages()->pluck('body')->all()[1])->toContain('Interview rescheduled')
        ->and($conversation->messages()->pluck('body')->all()[2])->toContain('Interview cancelled');

    $this->actingAs($candidate)
        ->get(route('job-chat.index', ['conversation' => $conversation->id]))
        ->assertInertia(fn ($page) => $page
            ->where('selectedConversation.messages.0.type', 'system')
            ->where('selectedConversation.messages.2.type', 'system'));
});

