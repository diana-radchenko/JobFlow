<?php

use App\Models\User;
use App\Models\UserWorkJobApplication;
use App\Models\WorkJob;
use App\Enums\ApplicationStatus;

test('guests are redirected to the login page from salary', function () {
    $response = $this->get(route('salary'));
    $response->assertRedirect(route('login'));
});

test('salary comparison can derive role industry and level from the current users application', function () {
    $candidate = User::factory()->create();
    $employer = User::factory()->employer()->create();
    $resume = $candidate->resumes()->create(['title' => 'Candidate Resume']);
    $job = WorkJob::factory()->for($employer, 'employer')->create([
        'title' => 'Coding Instructor',
        'company' => 'Flow Academy',
        'industry' => 'Education & Training',
        'position_level' => 'Junior',
        'salary_start' => 50000,
        'salary_end' => 70000,
        'salary_period' => 'annual',
        'status' => 'published',
        'published_at' => now(),
    ]);
    $application = UserWorkJobApplication::create([
        'user_id' => $candidate->id,
        'work_job_id' => $job->id,
        'resume_id' => $resume->id,
        'status' => ApplicationStatus::Applied,
    ]);

    $this->actingAs($candidate)->get(route('salary', ['application_id' => $application->id]))
        ->assertInertia(fn ($page) => $page
            ->where('filters.title', 'Coding Instructor')
            ->where('filters.industry', 'Education & Training')
            ->where('filters.position_level', 'Junior')
            ->where('selectedApplicationId', $application->id)
            ->where('comparison.count', 1));
});

test('salary application selection forbids another users application', function () {
    $candidate = User::factory()->create();
    $other = User::factory()->create();
    $job = WorkJob::factory()->for(User::factory()->employer()->create(), 'employer')->create();
    $application = UserWorkJobApplication::create([
        'user_id' => $other->id,
        'work_job_id' => $job->id,
        'status' => ApplicationStatus::Applied,
    ]);

    $this->actingAs($candidate)->get(route('salary', ['application_id' => $application->id]))
        ->assertForbidden();
});

test('authenticated users can visit the salary page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('salary'));
    $response->assertOk();
});

test('salary page shares the users resumes for the AI review card', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);

    $this->actingAs($user)
        ->get(route('salary'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Salary')
            ->has('resumes', 1)
            ->where('resumes.0.id', $resume->id)
            ->where('resumes.0.title', $resume->title)
        );
});

