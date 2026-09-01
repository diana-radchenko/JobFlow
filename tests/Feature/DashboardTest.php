<?php

use App\Enums\ApplicationStatus;
use App\Http\Controllers\DashboardController;
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
            ->where('dashboardSummary.jobSearchProgress', 45)
            ->has('interviewSessions', 1)
            ->where('interviewSessions.0.user_id', $candidate->id)
            ->has('applications', 1)
            ->where('applications.0.work_job.title', $job->title)
            ->where('applications.0.status', ApplicationStatus::Applied->value));
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

test('dashboard article catalog uses publisher visuals and original links with a local fallback', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('articles', 2)
            ->where('articles.0.image', fn (string $image) => str_starts_with($image, 'https://assets.weforum.org/'))
            ->where('articles.0.fallback_image', '/articles/article-fallback.svg')
            ->where('articles.0.category', 'Future of work')
            ->where('articles.0.url', 'https://www.weforum.org/publications/the-future-of-jobs-report-2025/')
            ->where('articles.1.image', fn (string $image) => str_starts_with($image, 'https://proximus.talent-pool.com/cdn/image/'))
            ->where('articles.1.url', 'https://proximus.talent-pool.com/freelance'));

    expect(public_path('articles/article-fallback.svg'))->toBeFile();
});

test('dashboard calculates stable job search progress from real candidate milestones', function () {
    $candidate = User::factory()->create(['name' => 'Diana Candidate']);
    $employer = User::factory()->employer()->create();
    $resume = Resume::create(['user_id' => $candidate->id, 'title' => 'Career Resume']);
    $job = WorkJob::factory()->for($employer, 'employer')->create([
        'title' => 'Application Engineer',
        'company' => 'Flow Systems',
        'status' => 'published',
        'published_at' => now(),
        'salary_start' => 80000,
        'salary_end' => 95000,
        'salary_currency' => 'USD',
        'salary_period' => 'annual',
    ]);
    $application = UserWorkJobApplication::create([
        'user_id' => $candidate->id,
        'work_job_id' => $job->id,
        'resume_id' => $resume->id,
        'status' => ApplicationStatus::Applied,
        'viewed_at' => now(),
    ]);
    $candidate->savedWorkJobs()->attach($job);
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
        'scheduled_at' => now()->addDay(),
        'timezone' => 'UTC',
    ]);

    $this->actingAs($candidate)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('profileFirstName', 'Diana')
            ->where('dashboardSummary.jobSearchProgress', 70)
            ->where('selectedResumeSummary.title', 'Career Resume')
            ->where('selectedResumeSummary.completeness', 13)
            ->has('selectedResumeSummary.checklist', 4)
            ->where('applications.0.work_job.salary_start', '80000.00')
            ->where('applications.0.work_job.salary_currency', 'USD')
            ->where('applications.0.status', ApplicationStatus::Applied->value)
            ->where('jobSearchMilestones', fn ($milestones) => collect($milestones)
                ->where('complete', true)
                ->sum('weight') === 70)
            ->has('jobSearchMilestones', 7));
});

test('job search progress is capped at 80 until an offer or hire exists', function () {
    $controller = app(DashboardController::class);
    $method = new ReflectionMethod($controller, 'jobSearchProgress');
    $resumeSummary = ['completeness' => 100];
    $application = new UserWorkJobApplication([
        'status' => ApplicationStatus::Applied,
        'viewed_at' => now(),
    ]);

    $beforeOffer = $method->invoke($controller, $resumeSummary, collect([$application]), true, true);
    expect($beforeOffer['percentage'])->toBe(80)
        ->and($beforeOffer['milestones'])->toHaveCount(7)
        ->and(collect($beforeOffer['milestones'])->firstWhere('label', 'Offer received')['complete'])->toBeFalse();

    foreach ([ApplicationStatus::Offer, ApplicationStatus::Hired] as $status) {
        $application->status = $status;
        $completed = $method->invoke($controller, $resumeSummary, collect([$application]), true, true);
        expect($completed['percentage'])->toBe(100);
    }
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
            ->where('recentActivity', fn ($activity) => collect($activity)->pluck('event')->sort()->values()->all() === ['Application submitted', 'Application viewed'])
            ->where('recentActivity.0.company', 'Northstar'));
});

