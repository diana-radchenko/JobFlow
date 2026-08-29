<?php

use App\Enums\ApplicationStatus;
use App\Models\InterviewSession;
use App\Models\Resume;
use App\Models\User;
use App\Models\UserWorkJobApplication;
use App\Models\WorkJob;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard summary uses only the current candidates real applications and interviews', function () {
    $candidate = User::factory()->create();
    $otherCandidate = User::factory()->create();
    $employer = User::factory()->employer()->create();
    $resume = Resume::create(['user_id' => $candidate->id, 'title' => 'Product Resume']);
    $otherResume = Resume::create(['user_id' => $otherCandidate->id, 'title' => 'Other Resume']);
    $job = WorkJob::factory()->for($employer, 'employer')->create([
        'status' => 'published',
        'published_at' => now(),
    ]);

    $application = UserWorkJobApplication::create([
        'user_id' => $candidate->id,
        'work_job_id' => $job->id,
        'resume_id' => $resume->id,
        'status' => ApplicationStatus::Applied,
    ]);
    UserWorkJobApplication::create([
        'user_id' => $otherCandidate->id,
        'work_job_id' => $job->id,
        'resume_id' => $otherResume->id,
        'status' => ApplicationStatus::Applied,
    ]);

    InterviewSession::create([
        'user_id' => $candidate->id,
        'employer_id' => $employer->id,
        'resume_id' => $resume->id,
        'work_job_id' => $job->id,
        'application_id' => $application->id,
        'type' => 'job_interview',
        'complexity' => 'standard',
        'mode' => 'scheduled',
        'status' => 'scheduled',
        'scheduled_at' => now()->addDays(2),
        'timezone' => 'America/Chicago',
        'duration_minutes' => 45,
    ]);
    InterviewSession::create([
        'user_id' => $otherCandidate->id,
        'employer_id' => $employer->id,
        'resume_id' => $otherResume->id,
        'work_job_id' => $job->id,
        'type' => 'job_interview',
        'complexity' => 'standard',
        'mode' => 'scheduled',
        'status' => 'scheduled',
        'scheduled_at' => now()->addDay(),
        'timezone' => 'UTC',
    ]);

    $this->actingAs($candidate)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboardSummary.applications', 1)
            ->where('dashboardSummary.interviews', 1)
            ->where('dashboardSummary.resumeCompleteness', 13)
            ->has('interviewSessions', 1)
            ->where('interviewSessions.0.user_id', $candidate->id));
});

test('dashboard chooses the nearest upcoming interview and preserves its calendar timezone date', function () {
    CarbonImmutable::setTestNow('2026-09-01 12:00:00 UTC');
    $candidate = User::factory()->create();
    $employer = User::factory()->employer()->create();
    $resume = Resume::create(['user_id' => $candidate->id, 'title' => 'Engineering Resume']);
    $job = WorkJob::factory()->for($employer, 'employer')->create([
        'title' => 'Platform Engineer',
        'company' => 'Codecraft Works',
    ]);
    $base = [
        'user_id' => $candidate->id,
        'employer_id' => $employer->id,
        'resume_id' => $resume->id,
        'work_job_id' => $job->id,
        'application_id' => null,
        'type' => 'job_interview',
        'complexity' => 'standard',
        'mode' => 'scheduled',
        'status' => 'scheduled',
        'timezone' => 'America/Chicago',
        'duration_minutes' => 45,
    ];

    InterviewSession::create([...$base, 'scheduled_at' => '2026-08-31 15:00:00']);
    $nearest = InterviewSession::create([...$base, 'scheduled_at' => '2026-09-03 19:30:00']);
    InterviewSession::create([...$base, 'scheduled_at' => '2026-09-05 16:00:00']);

    $this->actingAs($candidate)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('nextInterview.id', $nearest->id)
            ->where('nextInterview.timezone', 'America/Chicago')
            ->where('nextInterview.scheduled_at', fn (string $value) =>
                CarbonImmutable::parse($value)->utc()->format('Y-m-d H:i') === '2026-09-03 19:30'
            )
            ->where('nextInterview.work_job.company', 'Codecraft Works'));

    CarbonImmutable::setTestNow();
});

test('dashboard article catalog uses local images and provides a local fallback', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('articles', 4)
            ->where('articles.0.image', '/articles/flexible-work.svg')
            ->where('articles.0.fallback_image', '/articles/article-fallback.svg')
            ->where('articles.0.category', 'Career planning')
            ->where('articles.0.reading_time', '6 min read'));

    expect(public_path('articles/flexible-work.svg'))->toBeFile()
        ->and(public_path('articles/article-fallback.svg'))->toBeFile();
});

test('dashboard derives next steps and recent activity from current candidate data', function () {
    $candidate = User::factory()->create();
    $employer = User::factory()->employer()->create();
    $resume = Resume::create(['user_id' => $candidate->id, 'title' => 'Candidate Resume']);
    $job = WorkJob::factory()->for($employer, 'employer')->create([
        'title' => 'Support Engineer',
        'company' => 'Northstar',
        'status' => 'published',
        'published_at' => now(),
    ]);
    UserWorkJobApplication::create([
        'user_id' => $candidate->id,
        'work_job_id' => $job->id,
        'resume_id' => $resume->id,
        'status' => ApplicationStatus::Applied,
        'viewed_at' => now(),
    ]);

    $this->actingAs($candidate)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('nextSteps')
            ->where('nextSteps.0.title', 'Improve your resume')
            ->has('recentActivity', 2)
            ->where('recentActivity.0.event', 'Application viewed')
            ->where('recentActivity.0.company', 'Northstar'));
});

