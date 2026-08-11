<?php

use App\Models\Resume;
use App\Models\User;
use App\Models\WorkJob;

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

test('a candidate can apply to a job with one of their resumes', function () {
    $user = User::factory()->create();
    $resume = Resume::create(['user_id' => $user->id, 'title' => 'My Resume']);
    $job = WorkJob::factory()->create();

    $this->actingAs($user)
        ->post(route('job-selection.apply', $job), ['resume_id' => $resume->id])
        ->assertRedirect(route('job-selection.show', $job));

    expect($user->applications()->where('work_job_id', $job->id)->first()->resume_id)
        ->toBe($resume->id);
});

test('a candidate cannot apply without selecting a resume', function () {
    $user = User::factory()->create();
    $job = WorkJob::factory()->create();

    $this->actingAs($user)
        ->post(route('job-selection.apply', $job), [])
        ->assertSessionHasErrors('resume_id');
});

test('a candidate cannot apply with another user\'s resume', function () {
    $user = User::factory()->create();
    $otherResume = Resume::create(['user_id' => User::factory()->create()->id, 'title' => 'Not Mine']);
    $job = WorkJob::factory()->create();

    $this->actingAs($user)
        ->post(route('job-selection.apply', $job), ['resume_id' => $otherResume->id])
        ->assertSessionHasErrors('resume_id');
});
