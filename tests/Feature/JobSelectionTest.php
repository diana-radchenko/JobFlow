<?php

use App\Enums\ApplicationStatus;
use App\Models\Resume;
use App\Models\User;
use App\Models\UserWorkJobApplication;
use App\Models\WorkJob;
use Inertia\Testing\AssertableInertia as Assert;

function selectableJob(array $attributes = []): WorkJob
{
    return WorkJob::factory()
        ->for(User::factory()->employer()->create(), 'employer')
        ->create(array_merge([
            'title' => 'Junior Learning Engineer',
            'company' => 'Northstar Academy',
            'industry' => 'Education & Training',
            'position_level' => 'Junior',
            'employment_type' => 'Part-time',
            'location' => 'Boston, MA',
            'workplace_type' => 'Remote',
            'salary_start' => 50000,
            'salary_end' => 70000,
            'status' => 'published',
            'published_at' => now()->subHours(2),
        ], $attributes));
}

test('guests are redirected to the login page', function () {
    $response = $this->get(route('job-selection'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the job selection page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('job-selection'));
    $response->assertOk();
});

test('job title search returns matching real vacancies', function () {
    $candidate = User::factory()->create();
    $matching = selectableJob();
    selectableJob(['title' => 'Healthcare Operations Manager']);

    $this->actingAs($candidate)->get(route('job-selection', ['keyword' => 'Learning Engineer']))
        ->assertInertia(fn (Assert $page) => $page->has('jobs', 1)->where('jobs.0.id', $matching->id));
});

test('company filter returns only that company vacancies', function () {
    $candidate = User::factory()->create();
    $matching = selectableJob();
    selectableJob(['company' => 'Another Company']);

    $this->actingAs($candidate)->get(route('job-selection', ['company' => 'Northstar']))
        ->assertInertia(fn (Assert $page) => $page->has('jobs', 1)->where('jobs.0.id', $matching->id));
});

test('industry filter supports the shared taxonomy and legacy values', function () {
    $candidate = User::factory()->create();
    $matching = selectableJob(['industry' => 'Technology & Software']);
    selectableJob(['industry' => 'Healthcare']);

    $this->actingAs($candidate)->get(route('job-selection', ['industry' => 'Technology / Software']))
        ->assertInertia(fn (Assert $page) => $page->has('jobs', 1)->where('jobs.0.id', $matching->id));
});

test('position level filter returns matching vacancies', function () {
    $candidate = User::factory()->create();
    $matching = selectableJob();
    selectableJob(['position_level' => 'Manager']);

    $this->actingAs($candidate)->get(route('job-selection', ['position_level' => 'Junior']))
        ->assertInertia(fn (Assert $page) => $page->has('jobs', 1)->where('jobs.0.id', $matching->id));
});

test('employment type filter returns matching vacancies', function () {
    $candidate = User::factory()->create();
    $matching = selectableJob();
    selectableJob(['employment_type' => 'Full-time']);

    $this->actingAs($candidate)->get(route('job-selection', ['employment_type' => 'Part-time']))
        ->assertInertia(fn (Assert $page) => $page->has('jobs', 1)->where('jobs.0.id', $matching->id));
});

test('work arrangement filter returns matching vacancies', function () {
    $candidate = User::factory()->create();
    $matching = selectableJob();
    selectableJob(['workplace_type' => 'On-site']);

    $this->actingAs($candidate)->get(route('job-selection', ['workplace_type' => 'Remote']))
        ->assertInertia(fn (Assert $page) => $page->has('jobs', 1)->where('jobs.0.id', $matching->id));
});

test('all selected filters combine with and logic', function () {
    $candidate = User::factory()->create();
    $matching = selectableJob();
    selectableJob(['employment_type' => 'Full-time']);
    selectableJob(['workplace_type' => 'Hybrid']);

    $filters = [
        'industry' => 'Education & Training',
        'position_level' => 'Junior',
        'employment_type' => 'Part-time',
        'workplace_type' => 'Remote',
        'location' => 'Boston',
        'salary_min' => 65000,
        'date_posted' => '7',
    ];

    $this->actingAs($candidate)->get(route('job-selection', $filters))
        ->assertInertia(fn (Assert $page) => $page->has('jobs', 1)->where('jobs.0.id', $matching->id));
});

test('unpublished and platform vacancies are excluded', function () {
    $candidate = User::factory()->create();
    $published = selectableJob();
    selectableJob(['status' => 'draft']);
    WorkJob::factory()->create(['status' => 'published', 'published_at' => now()]);

    $this->actingAs($candidate)->get(route('job-selection'))
        ->assertInertia(fn (Assert $page) => $page->has('jobs', 1)->where('jobs.0.id', $published->id));
});

test('saved and applied views use the existing candidate relationships', function () {
    $candidate = User::factory()->create();
    $resume = Resume::create(['user_id' => $candidate->id, 'title' => 'Primary Resume']);
    $saved = selectableJob(['title' => 'Saved Role']);
    $applied = selectableJob(['title' => 'Applied Role']);
    selectableJob(['title' => 'Other Role']);
    $candidate->savedWorkJobs()->attach($saved->id);
    UserWorkJobApplication::create([
        'user_id' => $candidate->id,
        'work_job_id' => $applied->id,
        'resume_id' => $resume->id,
        'status' => ApplicationStatus::Applied,
    ]);

    $this->actingAs($candidate)->get(route('job-selection', ['view' => 'saved']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('jobs', 1)
            ->where('jobs.0.id', $saved->id)
            ->where('jobs.0.saved', true));

    $this->actingAs($candidate)->get(route('job-selection', ['view' => 'applied']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('jobs', 1)
            ->where('jobs.0.id', $applied->id)
            ->where('jobs.0.applied', true));
});

test('a candidate can apply to a job with one of their resumes', function () {
    $user = User::factory()->create();
    $resume = Resume::create(['user_id' => $user->id, 'title' => 'My Resume']);
    $job = WorkJob::factory()->for(User::factory()->employer()->create(), 'employer')->create();

    $this->actingAs($user)
        ->post(route('job-selection.apply', $job), ['resume_id' => $resume->id])
        ->assertRedirect(route('job-selection.show', $job));

    expect($user->applications()->where('work_job_id', $job->id)->first()->resume_id)
        ->toBe($resume->id);
});

test('a candidate cannot apply without selecting a resume', function () {
    $user = User::factory()->create();
    $job = WorkJob::factory()->for(User::factory()->employer()->create(), 'employer')->create();

    $this->actingAs($user)
        ->post(route('job-selection.apply', $job), [])
        ->assertSessionHasErrors('resume_id');
});

test('a candidate cannot apply with another user\'s resume', function () {
    $user = User::factory()->create();
    $otherResume = Resume::create(['user_id' => User::factory()->create()->id, 'title' => 'Not Mine']);
    $job = WorkJob::factory()->for(User::factory()->employer()->create(), 'employer')->create();

    $this->actingAs($user)
        ->post(route('job-selection.apply', $job), ['resume_id' => $otherResume->id])
        ->assertSessionHasErrors('resume_id');
});

