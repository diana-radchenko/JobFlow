<?php

use App\Enums\ApplicationStatus;
use App\Models\InterviewSession;
use App\Models\Resume;
use App\Models\User;
use App\Models\UserWorkJobApplication;
use App\Models\WorkJob;
use Inertia\Testing\AssertableInertia as Assert;

function trackedApplication(User $candidate, array $application = [], array $interview = []): UserWorkJobApplication
{
    $employer = User::factory()->employer()->create();
    $resume = Resume::create(['user_id' => $candidate->id, 'title' => 'Tracker Resume']);
    $job = WorkJob::factory()->for($employer, 'employer')->create([
        'title' => 'Coding Instructor',
        'company' => 'Northstar Academy',
        'status' => 'published',
    ]);
    $tracked = UserWorkJobApplication::create(array_merge([
        'user_id' => $candidate->id,
        'work_job_id' => $job->id,
        'resume_id' => $resume->id,
        'status' => ApplicationStatus::Applied,
    ], $application));

    if ($interview !== []) {
        InterviewSession::create(array_merge([
            'user_id' => $candidate->id,
            'employer_id' => $employer->id,
            'resume_id' => $resume->id,
            'work_job_id' => $job->id,
            'application_id' => $tracked->id,
            'type' => 'job_interview',
            'complexity' => 'standard',
            'mode' => 'scheduled',
            'status' => 'scheduled',
            'scheduled_at' => '2026-09-03 19:30:00',
            'timezone' => 'America/Chicago',
            'duration_minutes' => 45,
        ], $interview));
    }

    return $tracked;
}

test('guests are redirected to the login page', function () {
    $response = $this->get(route('request-tracker'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the request tracker page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('request-tracker'));
    $response->assertOk();
});

test('the tracker ships the status and viewed state the charts are built from', function () {
    $user = User::factory()->create();
    $job = WorkJob::factory()->create();
    $otherJob = WorkJob::factory()->create();

    $viewed = UserWorkJobApplication::create([
        'user_id' => $user->id,
        'work_job_id' => $job->id,
        'status' => ApplicationStatus::Rejected,
        'viewed_at' => now(),
    ]);
    UserWorkJobApplication::create([
        'user_id' => $user->id,
        'work_job_id' => $otherJob->id,
        'status' => ApplicationStatus::Applied,
    ]);

    $this->actingAs($user)
        ->get(route('request-tracker'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('RequestTracker')
            ->has('applications', 2)
            ->where('applications.0.id', $viewed->id)
            ->where('applications.0.status', ApplicationStatus::Rejected->value)
            ->where('applications.0.viewed_at', fn ($viewedAt) => $viewedAt !== null)
            ->where('applications.1.viewed_at', null)
        );
});

test('a real scheduled interview drives interview count stage and timezone details', function () {
    $candidate = User::factory()->create();
    $application = trackedApplication($candidate, [], ['status' => 'scheduled']);

    $this->actingAs($candidate)->get(route('request-tracker'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('funnel.interview', 1)
            ->where('applications.0.id', $application->id)
            ->where('applications.0.tracker_stage', 'Interview')
            ->where('applications.0.interview_session.scheduled_at', fn (string $date) => str_contains($date, '2026-09-03'))
            ->where('applications.0.interview_session.timezone', 'America/Chicago'));
});

test('application stages and board grouping data are derived from real state', function () {
    $candidate = User::factory()->create();
    trackedApplication($candidate);
    trackedApplication($candidate, ['viewed_at' => now()]);
    trackedApplication($candidate, ['status' => ApplicationStatus::Offer]);
    trackedApplication($candidate, ['status' => ApplicationStatus::Rejected]);

    $this->actingAs($candidate)->get(route('request-tracker'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('funnel.applied', 4)
            ->where('funnel.viewed', 3)
            ->where('funnel.offer', 1)
            ->where('applications', fn ($applications) => collect($applications)
                ->pluck('tracker_stage')->sort()->values()->all() === ['Applied', 'Offer', 'Rejected', 'Viewed']));
});

test('request tracker isolates applications interviews and statistics by candidate', function () {
    $candidate = User::factory()->create();
    $other = User::factory()->create();
    $own = trackedApplication($candidate, [], ['status' => 'scheduled']);
    trackedApplication($other, [], ['status' => 'scheduled']);

    $this->actingAs($candidate)->get(route('request-tracker'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('applications', 1)
            ->where('applications.0.id', $own->id)
            ->where('funnel.applied', 1)
            ->where('funnel.interview', 1));
});

test('guests cannot delete an application', function () {
    $user = User::factory()->create();
    $job = WorkJob::create([
        'title' => 'Test Job',
        'salary_start' => 50000,
        'salary_end' => 80000,
        'company' => 'Acme',
        'description' => 'Description',
        'contacts' => 'jobs@example.com',
        'location' => 'Remote',
        'technologies' => ['PHP'],
    ]);
    $application = UserWorkJobApplication::create([
        'user_id' => $user->id,
        'work_job_id' => $job->id,
        'status' => ApplicationStatus::Applied,
    ]);

    $response = $this->delete(route('request-tracker.applications.destroy', $application));
    $response->assertRedirect(route('login'));
    expect(UserWorkJobApplication::query()->whereKey($application->id)->exists())->toBeTrue();
});

test('authenticated user can delete their own application', function () {
    $user = User::factory()->create();
    $job = WorkJob::create([
        'title' => 'Test Job',
        'salary_start' => 50000,
        'salary_end' => 80000,
        'company' => 'Acme',
        'description' => 'Description',
        'contacts' => 'jobs@example.com',
        'location' => 'Remote',
        'technologies' => ['PHP'],
    ]);
    $application = UserWorkJobApplication::create([
        'user_id' => $user->id,
        'work_job_id' => $job->id,
        'status' => ApplicationStatus::Applied,
    ]);

    $this->actingAs($user);

    $response = $this->delete(route('request-tracker.applications.destroy', $application));
    $response->assertRedirect(route('request-tracker'));
    expect(UserWorkJobApplication::query()->whereKey($application->id)->exists())->toBeFalse();
});

test('user cannot delete another users application', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $job = WorkJob::create([
        'title' => 'Test Job',
        'salary_start' => 50000,
        'salary_end' => 80000,
        'company' => 'Acme',
        'description' => 'Description',
        'contacts' => 'jobs@example.com',
        'location' => 'Remote',
        'technologies' => ['PHP'],
    ]);
    $application = UserWorkJobApplication::create([
        'user_id' => $owner->id,
        'work_job_id' => $job->id,
        'status' => ApplicationStatus::Applied,
    ]);

    $this->actingAs($other);

    $response = $this->delete(route('request-tracker.applications.destroy', $application));
    $response->assertForbidden();
    expect(UserWorkJobApplication::query()->whereKey($application->id)->exists())->toBeTrue();
});
