<?php

use App\Enums\ApplicationStatus;
use App\Models\Resume;
use App\Models\User;
use App\Models\UserWorkJobApplication;
use App\Models\WorkJob;

beforeEach(function () {
    $this->employer = User::factory()->employer()->create();
});

test('guests are redirected to the login page', function () {
    $this->get(route('employer.jobs.index'))->assertRedirect(route('login'));
});

test('candidates cannot reach the employer area', function (string $route) {
    $job = WorkJob::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route($route, $job))
        ->assertForbidden();
})->with(['employer.jobs.index', 'employer.jobs.create', 'employer.jobs.show', 'employer.jobs.edit']);

test('employers cannot reach the candidate area', function (string $route) {
    $this->actingAs($this->employer)->get(route($route))->assertForbidden();
})->with(['dashboard', 'request-tracker', 'job-selection', 'resumes.index']);

test('the job list only shows jobs the employer owns', function () {
    $own = WorkJob::factory()->for($this->employer, 'employer')->create();
    $someoneElses = WorkJob::factory()->for(User::factory()->employer()->create(), 'employer')->create();
    $platformJob = WorkJob::factory()->create();

    $this->actingAs($this->employer)
        ->get(route('employer.jobs.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Employer/Jobs/Index')
            ->has('jobs', 1)
            ->where('jobs.0.id', $own->id)
            ->where('jobs.0.applications_count', 0)
        );

    expect([$someoneElses->id, $platformJob->id])->not->toContain($own->id);
});

test('an employer can post a job', function () {
    $this->actingAs($this->employer)
        ->post(route('employer.jobs.store'), [
            'title' => 'Senior Backend Engineer',
            'company' => 'Acme',
            'location' => 'Remote',
            'contacts' => 'careers@acme.test',
            'description' => 'Build things.',
            'salary_start' => 90000,
            'salary_end' => 130000,
            'technologies' => ['PHP', 'Laravel'],
        ])
        ->assertRedirect(route('employer.jobs.show', WorkJob::latest('id')->first()));

    $job = WorkJob::firstWhere('title', 'Senior Backend Engineer');

    expect($job->user_id)->toBe($this->employer->id)
        ->and($job->technologies)->toBe(['PHP', 'Laravel']);
});

test('vacancy markdown survives draft save edit publish and resave unchanged', function () {
    $markdown = [
        'description' => "## Overview\n\nBuild **safe** products.\n\n- Design the API\n- Review changes\n\n<script>window.__vacancyMarkdownXss = true</script>",
        'responsibilities' => "## Responsibilities\n\n- Own **delivery**\n- Support the team",
        'requirements' => "## Schedule\n\n- Monday to Friday\n- **Flexible** start time",
        'benefits' => "## Benefits\n\n- Learning budget\n- **Remote** equipment",
    ];
    $payload = [
        'title' => 'Markdown Vacancy',
        'company' => 'Acme',
        'location' => 'Remote',
        'contacts' => 'careers@acme.test',
        'technologies' => ['Laravel', 'Vue'],
        ...$markdown,
    ];

    $this->actingAs($this->employer)
        ->post(route('employer.jobs.store'), [...$payload, 'status' => 'draft'])
        ->assertSessionHasNoErrors();

    $job = WorkJob::query()->where('title', 'Markdown Vacancy')->firstOrFail();

    expect($job->status)->toBe('draft')
        ->and($job->description)->toBe($markdown['description'])
        ->and($job->responsibilities)->toBe($markdown['responsibilities'])
        ->and($job->requirements)->toBe($markdown['requirements'])
        ->and($job->benefits)->toBe($markdown['benefits']);

    $this->actingAs($this->employer)
        ->get(route('employer.jobs.edit', $job))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Employer/Jobs/Form')
            ->where('job.description', $markdown['description'])
            ->where('job.responsibilities', $markdown['responsibilities'])
            ->where('job.requirements', $markdown['requirements'])
            ->where('job.benefits', $markdown['benefits']));

    $this->actingAs($this->employer)
        ->put(route('employer.jobs.update', $job), [...$payload, 'status' => 'published'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('employer.jobs.show', $job));

    $job->refresh();

    expect($job->status)->toBe('published')
        ->and($job->published_at)->not->toBeNull()
        ->and($job->description)->toBe($markdown['description'])
        ->and($job->responsibilities)->toBe($markdown['responsibilities'])
        ->and($job->requirements)->toBe($markdown['requirements'])
        ->and($job->benefits)->toBe($markdown['benefits']);

    $this->actingAs($this->employer)
        ->get(route('employer.jobs.show', $job))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Employer/Jobs/Show')
            ->where('job.description', $markdown['description'])
            ->where('job.responsibilities', $markdown['responsibilities'])
            ->where('job.requirements', $markdown['requirements'])
            ->where('job.benefits', $markdown['benefits']));

    $candidate = User::factory()->create();

    $this->actingAs($candidate)
        ->get(route('job-selection.show', $job))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('JobDetail')
            ->where('job.description', $markdown['description'])
            ->where('job.responsibilities', $markdown['responsibilities'])
            ->where('job.requirements', $markdown['requirements'])
            ->where('job.benefits', $markdown['benefits']));

    $this->actingAs($this->employer)
        ->put(route('employer.jobs.update', $job), [...$payload, 'status' => 'published'])
        ->assertSessionHasNoErrors();

    $job->refresh();

    expect($job->description)->toBe($markdown['description'])
        ->and($job->responsibilities)->toBe($markdown['responsibilities'])
        ->and($job->requirements)->toBe($markdown['requirements'])
        ->and($job->benefits)->toBe($markdown['benefits']);
});

test('posting a job without technologies stores an empty list', function () {
    $this->actingAs($this->employer)->post(route('employer.jobs.store'), [
        'title' => 'Designer',
        'company' => 'Acme',
        'location' => 'Remote',
        'contacts' => 'careers@acme.test',
        'description' => 'Design things.',
    ])->assertSessionHasNoErrors();

    expect(WorkJob::firstWhere('title', 'Designer')->technologies)->toBe([]);
});

test('a job cannot end below where its salary starts', function () {
    $this->actingAs($this->employer)->post(route('employer.jobs.store'), [
        'title' => 'Designer',
        'company' => 'Acme',
        'location' => 'Remote',
        'contacts' => 'careers@acme.test',
        'description' => 'Design things.',
        'salary_start' => 90000,
        'salary_end' => 10000,
    ])->assertSessionHasErrors('salary_end');

    expect(WorkJob::query()->count())->toBe(0);
});

test('an employer can update and delete their own job', function () {
    $job = WorkJob::factory()->for($this->employer, 'employer')->create();

    $this->actingAs($this->employer)->put(route('employer.jobs.update', $job), [
        'title' => 'Updated Title',
        'company' => $job->company,
        'location' => $job->location,
        'contacts' => $job->contacts,
        'description' => $job->description,
    ])->assertRedirect(route('employer.jobs.show', $job));

    expect($job->refresh()->title)->toBe('Updated Title');

    $this->actingAs($this->employer)
        ->delete(route('employer.jobs.destroy', $job))
        ->assertRedirect(route('employer.jobs.index'));

    expect(WorkJob::query()->whereKey($job->id)->exists())->toBeFalse();
});

test('the published vacancy detail shows only real applicants for that vacancy', function () {
    $job = WorkJob::factory()->for($this->employer, 'employer')->create(['status' => 'published']);
    $otherJob = WorkJob::factory()->for($this->employer, 'employer')->create();
    $candidate = User::factory()->create(['name' => 'Visible Candidate']);
    $resume = Resume::create(['user_id' => $candidate->id, 'title' => 'Backend Engineer Resume']);
    $application = UserWorkJobApplication::create([
        'user_id' => $candidate->id,
        'work_job_id' => $job->id,
        'resume_id' => $resume->id,
        'status' => ApplicationStatus::Applied,
    ]);
    UserWorkJobApplication::create([
        'user_id' => User::factory()->create()->id,
        'work_job_id' => $otherJob->id,
        'status' => ApplicationStatus::Applied,
    ]);

    $this->actingAs($this->employer)
        ->get(route('employer.jobs.show', $job))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Employer/Jobs/Show')
            ->where('job.id', $job->id)
            ->where('job.applications_count', 1)
            ->has('applications', 1)
            ->where('applications.0.id', $application->id)
            ->where('applications.0.user.name', 'Visible Candidate')
            ->where('applications.0.resume.title', 'Backend Engineer Resume'));
});

test('saving an edited vacancy returns to its published detail page', function () {
    $job = WorkJob::factory()->for($this->employer, 'employer')->create(['status' => 'published']);

    $this->actingAs($this->employer)->put(route('employer.jobs.update', $job), [
        'title' => 'Updated Published Vacancy',
        'company' => $job->company,
        'location' => $job->location,
        'contacts' => $job->contacts,
        'description' => $job->description,
        'status' => 'published',
    ])->assertRedirect(route('employer.jobs.show', $job));

    expect($job->refresh()->title)->toBe('Updated Published Vacancy');
});

test('an employer cannot touch another employers job', function () {
    $job = WorkJob::factory()->for(User::factory()->employer()->create(), 'employer')->create();

    $this->actingAs($this->employer)->get(route('employer.jobs.show', $job))->assertForbidden();
    $this->actingAs($this->employer)->get(route('employer.jobs.edit', $job))->assertForbidden();
    $this->actingAs($this->employer)->put(route('employer.jobs.update', $job), [
        'title' => 'Hijacked',
        'company' => $job->company,
        'location' => $job->location,
        'contacts' => $job->contacts,
        'description' => $job->description,
    ])->assertForbidden();
    $this->actingAs($this->employer)->delete(route('employer.jobs.destroy', $job))->assertForbidden();

    expect($job->refresh()->title)->not->toBe('Hijacked');
});

test('an employer cannot touch an unowned platform job', function () {
    $job = WorkJob::factory()->create();

    $this->actingAs($this->employer)->get(route('employer.jobs.show', $job))->assertForbidden();
    $this->actingAs($this->employer)->delete(route('employer.jobs.destroy', $job))->assertForbidden();
});
