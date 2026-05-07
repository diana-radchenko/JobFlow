<?php

use App\Data\InterviewContextData;
use App\Enums\ApplicationStatus;
use App\Models\AdditionalInformation;
use App\Models\Education;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserWorkJobApplication;
use App\Models\WorkExperience;
use App\Models\WorkJob;

it('builds resume and job context from user resume relationships', function () {
    $user = User::factory()->create();

    WorkExperience::create([
        'user_id' => $user->id,
        'company_name' => 'Legacy Corp',
        'job_title' => 'Junior Developer',
        'start_date' => '2020-01-01',
    ]);

    WorkExperience::create([
        'user_id' => $user->id,
        'company_name' => 'Current Corp',
        'job_title' => 'Senior Developer',
        'start_date' => '2023-01-01',
    ]);

    Education::create([
        'user_id' => $user->id,
        'degree' => 'bachelors',
        'institution' => 'Tech University',
        'field_of_study' => 'Computer Science',
        'start_date' => '2018-01-01',
    ]);

    Skill::create([
        'user_id' => $user->id,
        'name' => 'Laravel',
        'proficiency_level' => 'advanced',
    ]);

    Project::create([
        'user_id' => $user->id,
        'title' => 'Interview Simulator',
        'type' => 'project',
    ]);

    AdditionalInformation::create([
        'user_id' => $user->id,
        'languages' => 'English, German',
        'certifications' => 'AWS',
        'interests' => 'System design',
        'notes' => 'Enjoys pair programming',
    ]);

    $job = WorkJob::create([
        'title' => 'PHP Engineer',
        'salary_start' => 5000,
        'salary_end' => 7000,
        'company' => 'Acme',
        'description' => 'Build Laravel apps',
        'contacts' => 'hr@acme.test',
        'location' => 'Remote',
        'technologies' => ['PHP', 'Laravel', 'PostgreSQL'],
    ]);

    UserWorkJobApplication::create([
        'user_id' => $user->id,
        'work_job_id' => $job->id,
        'status' => ApplicationStatus::Applied,
    ]);

    $context = InterviewContextData::fromUser($user);

    expect($context->workExperiences[0]['company_name'])->toBe('Current Corp');
    expect($context->additionalInfo)->toMatchArray([
        'languages' => 'English, German',
        'certifications' => 'AWS',
        'interests' => 'System design',
        'notes' => 'Enjoys pair programming',
    ]);

    $resumeContext = json_decode($context->resumeContext(), true);
    $jobContext = json_decode($context->jobContext(), true);

    expect($resumeContext['skills'][0]['name'])->toBe('Laravel');
    expect($jobContext)->toMatchArray([
        'title' => 'PHP Engineer',
        'company' => 'Acme',
        'location' => 'Remote',
    ]);
});
