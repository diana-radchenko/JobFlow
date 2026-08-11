<?php

use App\Enums\ApplicationStatus;
use App\Models\Resume;
use App\Models\User;
use App\Models\UserWorkJobApplication;
use App\Models\WorkJob;

beforeEach(function () {
    $this->employer = User::factory()->employer()->create();
    $this->job = WorkJob::factory()->for($this->employer, 'employer')->create();
    $this->application = UserWorkJobApplication::create([
        'user_id' => User::factory()->create()->id,
        'work_job_id' => $this->job->id,
        'status' => ApplicationStatus::Applied,
    ]);
});

test('opening an application marks it as viewed', function () {
    expect($this->application->viewed_at)->toBeNull();

    $this->actingAs($this->employer)
        ->get(route('employer.applications.show', [$this->job, $this->application]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Employer/Applications/Show')
            ->where('application.id', $this->application->id)
        );

    expect($this->application->refresh()->viewed_at)->not->toBeNull();
});

test('re-opening an application keeps the original viewed timestamp', function () {
    $firstViewedAt = now()->subDay();
    $this->application->update(['viewed_at' => $firstViewedAt]);

    $this->actingAs($this->employer)
        ->get(route('employer.applications.show', [$this->job, $this->application]))
        ->assertOk();

    expect($this->application->refresh()->viewed_at->timestamp)->toBe($firstViewedAt->timestamp);
});

test('an employer can reject or schedule an interview', function (ApplicationStatus $status) {
    $this->actingAs($this->employer)
        ->patch(route('employer.applications.update', [$this->job, $this->application]), [
            'status' => $status->value,
        ])
        ->assertSessionHasNoErrors();

    expect($this->application->refresh()->status)->toBe($status);
})->with([
    'rejected' => ApplicationStatus::Rejected,
    'interview scheduled' => ApplicationStatus::InterviewScheduled,
]);

test('an employer cannot set any other status', function (?string $status) {
    $this->actingAs($this->employer)
        ->patch(route('employer.applications.update', [$this->job, $this->application]), [
            'status' => $status,
        ])
        ->assertSessionHasErrors('status');

    expect($this->application->refresh()->status)->toBe(ApplicationStatus::Applied);
})->with([
    'hired' => ApplicationStatus::Hired->value,
    'offer' => ApplicationStatus::Offer->value,
    'nonsense' => 'shortlisted',
    'missing' => null,
]);

test('an employer cannot see applications on another employers job', function () {
    $this->actingAs(User::factory()->employer()->create())
        ->get(route('employer.applications.show', [$this->job, $this->application]))
        ->assertForbidden();

    expect($this->application->refresh()->viewed_at)->toBeNull();
});

test('candidates cannot open the employer application view', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('employer.applications.show', [$this->job, $this->application]))
        ->assertForbidden();

    expect($this->application->refresh()->viewed_at)->toBeNull();
});

test('an application that belongs to a different job is not reachable', function () {
    $otherJob = WorkJob::factory()->for($this->employer, 'employer')->create();

    $this->actingAs($this->employer)
        ->get(route('employer.applications.show', [$otherJob, $this->application]))
        ->assertNotFound();

    expect($this->application->refresh()->viewed_at)->toBeNull();
});

test('the employer can see the candidate\'s chosen resume on the application', function () {
    $resume = Resume::create(['user_id' => $this->application->user_id, 'title' => 'Backend Resume']);
    $resume->skills()->create(['user_id' => $this->application->user_id, 'name' => 'Laravel', 'proficiency_level' => 'expert']);
    $this->application->update(['resume_id' => $resume->id]);

    $this->actingAs($this->employer)
        ->get(route('employer.applications.show', [$this->job, $this->application]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('application.resume.title', 'Backend Resume')
            ->where('application.resume.skills.0.name', 'Laravel')
        );
});

test('the job page lists its applications with their viewed state', function () {
    $this->actingAs($this->employer)
        ->get(route('employer.jobs.show', $this->job))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Employer/Jobs/Show')
            ->has('applications', 1)
            ->where('applications.0.viewed_at', null)
            ->where('applications.0.user.id', $this->application->user_id)
        );
});
