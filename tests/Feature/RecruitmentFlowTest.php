<?php

use App\Enums\ApplicationStatus;
use App\Models\InterviewSession;
use App\Models\InterviewSessionEvent;
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

function futureInterviewPayload(array $overrides = []): array
{
    $localDateTime = now('America/Chicago')->addDays(7)->startOfHour()->addMinutes(30);

    return [
        'date' => $localDateTime->format('Y-m-d'),
        'time' => $localDateTime->format('H:i'),
        'timezone' => 'America/Chicago',
        'duration_minutes' => 45,
        'interview_format' => 'video',
        'meeting_link' => 'https://meet.example.com/jobflow',
        'location' => 'Online',
        'employer_note' => 'Please join five minutes early.',
        ...$overrides,
    ];
}

test('employer schedules and reschedules one real interview in the selected timezone', function () {
    extract(recruitmentFixture());
    $url = route('employer.interviews.store', [$job, $application]);
    $payload = futureInterviewPayload();
    $this->actingAs($employer)->post($url, $payload)->assertSessionHasNoErrors();
    $expectedUtc = Carbon\CarbonImmutable::createFromFormat('Y-m-d H:i', $payload['date'].' '.$payload['time'], $payload['timezone'])->utc();
    $interview = InterviewSession::first();

    expect(InterviewSession::count())->toBe(1)
        ->and($application->refresh()->status)->toBe(ApplicationStatus::InterviewScheduled)
        ->and($interview->scheduled_at->equalTo($expectedUtc))->toBeTrue()
        ->and($interview->timezone)->toBe('America/Chicago')
        ->and($interview->duration_minutes)->toBe(45)
        ->and($interview->interview_format)->toBe('video')
        ->and($interview->meeting_link)->toBe('https://meet.example.com/jobflow')
        ->and($interview->location)->toBe('Online')
        ->and(InterviewSessionEvent::first()->action)->toBe('scheduled');

    $rescheduled = futureInterviewPayload(['time' => now('America/Chicago')->addDays(8)->format('H:i')]);
    $this->actingAs($employer)->post($url, $rescheduled)->assertSessionHasNoErrors();

    expect(InterviewSession::count())->toBe(1)
        ->and(InterviewSessionEvent::count())->toBe(2)
        ->and(InterviewSessionEvent::latest('id')->first()->action)->toBe('rescheduled');
});

test('candidate cannot schedule an interview and another employer cannot use the application', function () {
    extract(recruitmentFixture());
    $payload = futureInterviewPayload();
    $this->actingAs($candidate)->post(route('employer.interviews.store', [$job, $application]), $payload)->assertForbidden();
    $this->actingAs(User::factory()->employer()->create())->post(route('employer.interviews.store', [$job, $application]), $payload)->assertForbidden();
    expect(InterviewSession::count())->toBe(0);
});

test('interview scheduling rejects past times and invalid timezones', function () {
    extract(recruitmentFixture());
    $url = route('employer.interviews.store', [$job, $application]);
    $past = now('America/Chicago')->subHour();

    $this->actingAs($employer)->post($url, futureInterviewPayload([
        'date' => $past->format('Y-m-d'),
        'time' => $past->format('H:i'),
    ]))->assertSessionHasErrors('date');
    $this->actingAs($employer)->post($url, futureInterviewPayload([
        'timezone' => 'Not/A_Timezone',
    ]))->assertSessionHasErrors('timezone');

    expect(InterviewSession::count())->toBe(0)
        ->and($application->refresh()->status)->toBe(ApplicationStatus::Applied);
});

test('employer cancels an interview without deleting its history', function () {
    extract(recruitmentFixture());
    $storeUrl = route('employer.interviews.store', [$job, $application]);
    $cancelUrl = route('employer.interviews.destroy', [$job, $application]);
    $this->actingAs($employer)->post($storeUrl, futureInterviewPayload())->assertSessionHasNoErrors();

    $this->actingAs($employer)->delete($cancelUrl)->assertSessionHasNoErrors();
    $interview = InterviewSession::first();

    expect(InterviewSession::count())->toBe(1)
        ->and($interview->status)->toBe('cancelled')
        ->and($interview->cancelled_at)->not->toBeNull()
        ->and($application->refresh()->status)->toBe(ApplicationStatus::Applied)
        ->and($interview->events()->pluck('action')->all())->toBe(['scheduled', 'cancelled']);
});

test('unrelated employer cannot cancel another employers interview', function () {
    extract(recruitmentFixture());
    $this->actingAs($employer)->post(
        route('employer.interviews.store', [$job, $application]),
        futureInterviewPayload(),
    )->assertSessionHasNoErrors();

    $this->actingAs(User::factory()->employer()->create())
        ->delete(route('employer.interviews.destroy', [$job, $application]))
        ->assertForbidden();

    expect(InterviewSession::first()->status)->toBe('scheduled');
});

test('scheduled interview is shared with dashboard and request tracker', function () {
    extract(recruitmentFixture());
    InterviewSession::create(['user_id' => $candidate->id, 'employer_id' => $employer->id, 'resume_id' => $resume->id, 'work_job_id' => $job->id, 'application_id' => $application->id, 'type' => 'job_interview', 'complexity' => 'standard', 'mode' => 'scheduled', 'status' => 'scheduled', 'scheduled_at' => now()->addDay(), 'timezone' => 'America/Chicago', 'duration_minutes' => 30]);
    $this->actingAs($candidate)->get(route('dashboard'))->assertInertia(fn ($page) => $page->where('interviewSessions.0.work_job.title', 'Coding Instructor')->where('interviewSessions.0.timezone', 'America/Chicago'));
    $this->actingAs($candidate)->get(route('request-tracker'))->assertInertia(fn ($page) => $page->where('applications.0.interview_session.timezone', 'America/Chicago'));
});

test('candidate sees only their own scheduled interview information', function () {
    extract(recruitmentFixture());
    InterviewSession::create(['user_id' => $candidate->id, 'employer_id' => $employer->id, 'resume_id' => $resume->id, 'work_job_id' => $job->id, 'application_id' => $application->id, 'type' => 'job_interview', 'complexity' => 'standard', 'mode' => 'scheduled', 'status' => 'scheduled', 'scheduled_at' => now()->addDay(), 'timezone' => 'America/Chicago', 'duration_minutes' => 30]);
    $otherCandidate = User::factory()->create();
    $otherResume = Resume::create(['user_id' => $otherCandidate->id, 'title' => 'Other Resume']);
    $otherApplication = UserWorkJobApplication::create(['user_id' => $otherCandidate->id, 'work_job_id' => $job->id, 'resume_id' => $otherResume->id, 'status' => ApplicationStatus::InterviewScheduled]);
    InterviewSession::create(['user_id' => $otherCandidate->id, 'employer_id' => $employer->id, 'resume_id' => $otherResume->id, 'work_job_id' => $job->id, 'application_id' => $otherApplication->id, 'type' => 'job_interview', 'complexity' => 'standard', 'mode' => 'scheduled', 'status' => 'scheduled', 'scheduled_at' => now()->addDays(2), 'timezone' => 'Europe/London', 'duration_minutes' => 60]);

    $this->actingAs($candidate)->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->has('interviewSessions', 1)->where('interviewSessions.0.user_id', $candidate->id));
    $this->actingAs($candidate)->get(route('request-tracker'))
        ->assertInertia(fn ($page) => $page->has('applications', 1)->where('applications.0.user_id', $candidate->id));
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
        ->and($result['sufficient'])->toBeTrue()->and($result['count'])->toBe(3)
        ->and($result['currency'])->toBe('USD')->and($result['period'])->toBe('year');
});

test('salary comparison never combines different currencies or salary periods', function () {
    $employer = User::factory()->employer()->create();
    foreach ([40000, 50000] as $salary) {
        WorkJob::factory()->for($employer, 'employer')->create(['title' => 'Coding Instructor', 'industry' => 'Education & Training', 'position_level' => 'Junior', 'status' => 'published', 'salary_start' => $salary, 'salary_end' => $salary + 10000, 'salary_currency' => 'USD', 'salary_period' => 'year']);
    }
    WorkJob::factory()->for($employer, 'employer')->create(['title' => 'Programming Instructor', 'industry' => 'Education & Training', 'position_level' => 'Junior', 'status' => 'published', 'salary_start' => 25, 'salary_end' => 35, 'salary_currency' => 'EUR', 'salary_period' => 'hour']);

    $result = app(SalaryMarketComparisonService::class)->compare('Coding Instructor', 'Education & Training', 'Junior');

    expect($result['count'])->toBe(2)->and($result['currency'])->toBe('USD')->and($result['period'])->toBe('year')->and($result['minimum'])->toBe(40000.0);
});

test('dashboard recommendations contain only real published employer jobs', function () {
    extract(recruitmentFixture());
    $resume->skills()->create(['user_id' => $candidate->id, 'name' => 'Coding', 'proficiency_level' => 'advanced']);
    WorkJob::factory()->create(['title' => 'Fake Demo Job', 'status' => 'published']);
    $this->actingAs($candidate)->get(route('dashboard', ['resume_id' => $resume->id]))
        ->assertInertia(fn ($page) => $page->has('recommendedJobs', 1)->where('recommendedJobs.0.job.id', $job->id));
});
