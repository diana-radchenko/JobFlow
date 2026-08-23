<?php

use App\Enums\ApplicationStatus;
use App\Models\InterviewSession;
use App\Models\JobConversation;
use App\Models\Resume;
use App\Models\User;
use App\Models\UserWorkJobApplication;
use App\Models\WorkJob;
use App\Services\JobTitleNormalizer;
use App\Services\SalaryMarketComparisonService;

function recruitmentFixture(): array
{
    $employer = User::factory()->employer()->create();
    $candidate = User::factory()->create();
    $resume = Resume::create(['user_id' => $candidate->id, 'title' => 'Coding Resume']);
    $job = WorkJob::factory()->for($employer, 'employer')->create([
        'title' => 'Coding Instructor', 'industry' => 'Education & Training', 'position_level' => 'Junior',
        'employment_type' => 'Internship', 'status' => 'published', 'published_at' => now(),
    ]);
    $application = UserWorkJobApplication::create(['user_id' => $candidate->id, 'work_job_id' => $job->id, 'resume_id' => $resume->id, 'status' => ApplicationStatus::Applied]);
    return compact('employer', 'candidate', 'resume', 'job', 'application');
}

test('employer schedules and reschedules one real interview in the selected timezone', function () {
    extract(recruitmentFixture());
    $url = route('employer.interviews.store', [$job, $application]);
    $payload = ['date' => '2026-09-03', 'time' => '14:30', 'timezone' => 'America/Chicago', 'duration_minutes' => 60, 'employer_note' => 'Video link follows'];
    $this->actingAs($employer)->post($url, $payload)->assertSessionHasNoErrors();
    expect(InterviewSession::count())->toBe(1)
        ->and($application->refresh()->status)->toBe(ApplicationStatus::InterviewScheduled)
        ->and(InterviewSession::first()->scheduled_at->toIso8601String())->toContain('19:30:00');
    $this->actingAs($employer)->post($url, [...$payload, 'time' => '15:30'])->assertSessionHasNoErrors();
    expect(InterviewSession::count())->toBe(1)->and(InterviewSession::first()->scheduled_at->toIso8601String())->toContain('20:30:00');
});

test('candidate cannot schedule an interview and another employer cannot use the application', function () {
    extract(recruitmentFixture());
    $payload = ['date' => '2026-09-03', 'time' => '14:30', 'timezone' => 'America/Chicago'];
    $this->actingAs($candidate)->post(route('employer.interviews.store', [$job, $application]), $payload)->assertForbidden();
    $this->actingAs(User::factory()->employer()->create())->post(route('employer.interviews.store', [$job, $application]), $payload)->assertForbidden();
    expect(InterviewSession::count())->toBe(0);
});

test('scheduled interview is shared with dashboard and request tracker', function () {
    extract(recruitmentFixture());
    InterviewSession::create(['user_id' => $candidate->id, 'employer_id' => $employer->id, 'resume_id' => $resume->id, 'work_job_id' => $job->id, 'application_id' => $application->id, 'type' => 'job_interview', 'complexity' => 'standard', 'mode' => 'scheduled', 'status' => 'scheduled', 'scheduled_at' => now()->addDay(), 'timezone' => 'America/Chicago', 'duration_minutes' => 30]);
    $this->actingAs($candidate)->get(route('dashboard'))->assertInertia(fn ($page) => $page->where('interviewSessions.0.work_job.title', 'Coding Instructor')->where('interviewSessions.0.timezone', 'America/Chicago'));
    $this->actingAs($candidate)->get(route('request-tracker'))->assertInertia(fn ($page) => $page->where('applications.0.interview_session.timezone', 'America/Chicago'));
});

test('chat is limited to the candidate and employer linked by the application', function () {
    extract(recruitmentFixture());
    $url = route('job-chat.messages.store', $application);
    $this->actingAs($candidate)->post($url, ['body' => 'Is this role still open?'])->assertRedirect();
    $this->actingAs($employer)->post($url, ['body' => 'Yes, it is.'])->assertRedirect();
    expect(JobConversation::count())->toBe(1)->and(JobConversation::first()->messages()->count())->toBe(2);
    $this->actingAs(User::factory()->create())->post($url, ['body' => 'Intrusion'])->assertForbidden();
});

test('job filters return only real published employer vacancies', function () {
    extract(recruitmentFixture());
    WorkJob::factory()->create(['title' => 'Demo Coding Instructor', 'industry' => 'Education & Training', 'position_level' => 'Junior', 'employment_type' => 'Internship', 'status' => 'published']);
    $this->actingAs($candidate)->get(route('job-selection', ['keyword' => 'Coding', 'industry' => 'Education & Training', 'position_level' => 'Junior', 'company' => $job->company, 'employment_type' => 'Internship']))
        ->assertInertia(fn ($page) => $page->has('jobs', 1)->where('jobs.0.id', $job->id));
});

test('salary comparison normalizes contextual and synonymous title words', function () {
    $employer = User::factory()->employer()->create();
    foreach (['Summer Camp Coding Instructor', 'Programming Instructor', 'Coding Teacher'] as $index => $title) {
        WorkJob::factory()->for($employer, 'employer')->create(['title' => $title, 'industry' => 'Education & Training', 'position_level' => 'Junior', 'status' => 'published', 'salary_start' => 40000 + $index * 1000, 'salary_end' => 60000 + $index * 1000]);
    }
    $result = app(SalaryMarketComparisonService::class)->compare('Coding Instructor', 'Education & Training', 'Junior');
    expect(app(JobTitleNormalizer::class)->similarity('Summer Camp Coding Instructor', 'Programming Instructor'))->toBeGreaterThan(0)
        ->and($result['sufficient'])->toBeTrue()->and($result['count'])->toBe(3);
});

test('dashboard recommendations contain only real published employer jobs', function () {
    extract(recruitmentFixture());
    $resume->skills()->create(['user_id' => $candidate->id, 'name' => 'Coding', 'proficiency_level' => 'advanced']);
    WorkJob::factory()->create(['title' => 'Fake Demo Job', 'status' => 'published']);
    $this->actingAs($candidate)->get(route('dashboard', ['resume_id' => $resume->id]))
        ->assertInertia(fn ($page) => $page->has('recommendedJobs', 1)->where('recommendedJobs.0.job.id', $job->id));
});
