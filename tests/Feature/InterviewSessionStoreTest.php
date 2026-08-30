<?php

use App\Enums\ApplicationStatus;
use App\Models\InterviewSession;
use App\Models\User;
use App\Models\UserWorkJobApplication;
use App\Models\WorkJob;

test('starting an interview requires a resume', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('interview-session.store'), [
            'type' => 'resume-based',
            'complexity' => 'advanced',
            'mode' => 'live',
        ])
        ->assertSessionHasErrors('resume_id');
});

test('a user cannot start an interview with another user\'s resume', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $resume = $owner->resumes()->create(['title' => 'Private Resume']);

    $this->actingAs($intruder)
        ->post(route('interview-session.store'), [
            'type' => 'resume-based',
            'complexity' => 'advanced',
            'mode' => 'live',
            'resume_id' => $resume->id,
        ])
        ->assertSessionHasErrors('resume_id');
});

test('starting an interview stores the selected resume and job', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);
    $job = WorkJob::create([
        'title' => 'Backend Engineer',
        'company' => 'Acme',
        'description' => 'Build APIs',
        'contacts' => 'jobs@example.com',
        'location' => 'Remote',
        'technologies' => ['PHP'],
    ]);
    UserWorkJobApplication::create([
        'user_id' => $user->id,
        'work_job_id' => $job->id,
        'status' => ApplicationStatus::Applied,
    ]);

    $this->actingAs($user)
        ->post(route('interview-session.store'), [
            'type' => 'resume-based',
            'complexity' => 'advanced',
            'mode' => 'live',
            'resume_id' => $resume->id,
            'work_job_id' => $job->id,
        ])
        ->assertRedirect();

    $session = InterviewSession::where('user_id', $user->id)->firstOrFail();

    expect($session->resume_id)->toBe($resume->id);
    expect($session->work_job_id)->toBe($job->id);
});

test('a user cannot start an interview for a job they have not applied to', function () {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);
    $job = WorkJob::create([
        'title' => 'Backend Engineer',
        'company' => 'Acme',
        'description' => 'Build APIs',
        'contacts' => 'jobs@example.com',
        'location' => 'Remote',
        'technologies' => ['PHP'],
    ]);

    $this->actingAs($user)
        ->post(route('interview-session.store'), [
            'type' => 'resume-based',
            'complexity' => 'advanced',
            'mode' => 'live',
            'resume_id' => $resume->id,
            'work_job_id' => $job->id,
        ])
        ->assertSessionHasErrors('work_job_id');
});

test('starting an interview only resumes an active session of the requested mode', function (string $existingMode, string $requestedMode) {
    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'My Resume']);
    $context = ['type' => 'technical', 'complexity' => 'intermediate', 'resume_id' => $resume->id];
    $existing = InterviewSession::create([
        ...$context, 'user_id' => $user->id, 'mode' => $existingMode, 'status' => 'in_progress',
    ]);

    $response = $this->actingAs($user)->post(route('interview-session.store'), [...$context, 'mode' => $requestedMode]);

    $selected = InterviewSession::where('user_id', $user->id)->where('mode', $requestedMode)->firstOrFail();
    $response->assertRedirect(route('interview-session.show', $selected));
    expect($selected->id === $existing->id)->toBe($existingMode === $requestedMode);
    expect(InterviewSession::where('user_id', $user->id)->count())->toBe($existingMode === $requestedMode ? 1 : 2);
})->with([
    'Voice does not resume Text' => ['text', 'live'],
    'Text does not resume Voice' => ['live', 'text'],
    'Voice resumes Voice' => ['live', 'live'],
    'Text resumes Text' => ['text', 'text'],
]);
