<?php

use App\Enums\ApplicationStatus;
use App\Models\User;
use App\Models\UserWorkJobApplication;
use App\Models\WorkJob;

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
