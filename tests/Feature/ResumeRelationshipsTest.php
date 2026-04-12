<?php

use App\Models\AdditionalInformation;
use App\Models\Education;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\WorkExperience;

it('user has one profile', function () {
    $user = User::factory()->create();
    $profile = UserProfile::create([
        'user_id' => $user->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'city' => 'New York',
        'country' => 'USA',
        'phone' => '+1234567890',
        'linkedin_url' => 'https://linkedin.com/in/john',
    ]);

    expect($user->profile)->toBeInstanceOf(UserProfile::class);
    expect($user->profile->id)->toBe($profile->id);
    expect($user->profile->phone)->toBe('+1234567890');
});

it('user has many educations', function () {
    $user = User::factory()->create();
    $education1 = Education::create([
        'user_id' => $user->id,
        'degree' => 'Bachelors',
        'institution' => 'MIT',
        'field_of_study' => 'Computer Science',
        'start_date' => '2018-01-01',
        'end_date' => '2022-05-15',
    ]);
    $education2 = Education::create([
        'user_id' => $user->id,
        'degree' => 'Masters',
        'institution' => 'Stanford',
        'field_of_study' => 'Machine Learning',
        'start_date' => '2022-09-01',
        'end_date' => '2024-05-30',
    ]);

    expect($user->educations)->toHaveCount(2);
    expect($user->educations->pluck('id'))->toContain($education1->id, $education2->id);
});

it('user has many work experiences', function () {
    $user = User::factory()->create();
    $experience1 = WorkExperience::create([
        'user_id' => $user->id,
        'company_name' => 'Google',
        'job_title' => 'Software Engineer',
        'location' => 'Mountain View',
        'start_date' => '2020-01-15',
        'end_date' => null,
        'is_current' => true,
    ]);
    $experience2 = WorkExperience::create([
        'user_id' => $user->id,
        'company_name' => 'Facebook',
        'job_title' => 'Junior Developer',
        'location' => 'Menlo Park',
        'start_date' => '2018-06-01',
        'end_date' => '2019-12-31',
        'is_current' => false,
    ]);

    expect($user->workExperiences)->toHaveCount(2);
    expect($user->workExperiences->where('is_current', true))->toHaveCount(1);
});

it('user has many skills', function () {
    $user = User::factory()->create();
    $skill1 = Skill::create([
        'user_id' => $user->id,
        'name' => 'PHP',
        'proficiency_level' => 'Expert',
    ]);
    $skill2 = Skill::create([
        'user_id' => $user->id,
        'name' => 'JavaScript',
        'proficiency_level' => 'Advanced',
    ]);
    $skill3 = Skill::create([
        'user_id' => $user->id,
        'name' => 'Python',
        'proficiency_level' => 'Intermediate',
    ]);

    expect($user->skills)->toHaveCount(3);
    expect($user->skills->pluck('name'))->toContain('PHP', 'JavaScript', 'Python');
});

it('user has many projects', function () {
    $user = User::factory()->create();
    $project = Project::create([
        'user_id' => $user->id,
        'title' => 'E-commerce Platform',
        'type' => 'project',
        'description' => 'Built a full-stack e-commerce platform',
        'url' => 'https://github.com/john/ecommerce',
        'start_date' => '2023-01-01',
        'end_date' => '2023-06-30',
    ]);
    $achievement = Project::create([
        'user_id' => $user->id,
        'title' => 'First Open Source Contribution',
        'type' => 'achievement',
        'description' => 'Contributed to Laravel framework',
    ]);

    expect($user->projects)->toHaveCount(2);
    expect($user->projects->where('type', 'project'))->toHaveCount(1);
    expect($user->projects->where('type', 'achievement'))->toHaveCount(1);
});

it('user has one additional information', function () {
    $user = User::factory()->create();
    $additionalInfo = AdditionalInformation::create([
        'user_id' => $user->id,
        'languages' => 'English, Spanish, French',
        'certifications' => 'AWS Certified Solutions Architect',
        'interests' => 'Machine Learning, Open Source',
        'notes' => 'Available for remote work',
    ]);

    expect($user->additionalInformation)->toBeInstanceOf(AdditionalInformation::class);
    expect($user->additionalInformation->id)->toBe($additionalInfo->id);
    expect($user->additionalInformation->languages)->toBe('English, Spanish, French');
});

it('deleting user cascades to all resume data', function () {
    $user = User::factory()->create();
    UserProfile::create([
        'user_id' => $user->id,
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'city' => 'Boston',
        'country' => 'USA',
        'phone' => '+1234567890',
    ]);
    Education::create([
        'user_id' => $user->id,
        'degree' => 'Bachelors',
        'institution' => 'MIT',
    ]);
    WorkExperience::create([
        'user_id' => $user->id,
        'company_name' => 'Google',
        'job_title' => 'Engineer',
        'start_date' => '2020-01-01',
    ]);
    Skill::create([
        'user_id' => $user->id,
        'name' => 'PHP',
    ]);
    Project::create([
        'user_id' => $user->id,
        'title' => 'Project',
        'type' => 'project',
    ]);
    AdditionalInformation::create([
        'user_id' => $user->id,
        'languages' => 'English',
    ]);

    $userId = $user->id;
    $user->delete();

    expect(UserProfile::where('user_id', $userId)->exists())->toBeFalse();
    expect(Education::where('user_id', $userId)->exists())->toBeFalse();
    expect(WorkExperience::where('user_id', $userId)->exists())->toBeFalse();
    expect(Skill::where('user_id', $userId)->exists())->toBeFalse();
    expect(Project::where('user_id', $userId)->exists())->toBeFalse();
    expect(AdditionalInformation::where('user_id', $userId)->exists())->toBeFalse();
});

it('dates are cast to carbon instances', function () {
    $user = User::factory()->create();
    $education = Education::create([
        'user_id' => $user->id,
        'degree' => 'Bachelors',
        'institution' => 'MIT',
        'start_date' => '2020-01-15',
        'end_date' => '2024-05-30',
    ]);

    expect($education->start_date)->toBeInstanceOf(DateTimeInterface::class);
    expect($education->end_date)->toBeInstanceOf(DateTimeInterface::class);

    $workExperience = WorkExperience::create([
        'user_id' => $user->id,
        'company_name' => 'Google',
        'job_title' => 'Engineer',
        'start_date' => '2020-01-15',
        'end_date' => '2024-05-30',
    ]);

    expect($workExperience->start_date)->toBeInstanceOf(DateTimeInterface::class);
    expect($workExperience->end_date)->toBeInstanceOf(DateTimeInterface::class);
});
