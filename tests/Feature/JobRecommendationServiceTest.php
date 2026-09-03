<?php

use App\Enums\EducationDegree;
use App\Models\Education;
use App\Models\Resume;
use App\Models\Skill;
use App\Models\User;
use App\Models\WorkJob;
use App\Services\JobRecommendationService;

function jobMatchVacancy(array $attributes = []): WorkJob
{
    return WorkJob::factory()
        ->for(User::factory()->employer()->create(), 'employer')
        ->create(array_merge([
            'title' => 'Summer Camp Coding Instructor',
            'company' => 'CodeWizardsHQ',
            'description' => 'Teach students Python and HTML in online coding classes.',
            'requirements' => null,
            'technologies' => [],
            'status' => 'published',
            'published_at' => now(),
        ], $attributes));
}

test('extra resume skills do not reduce the match score for skills mentioned in vacancy text', function () {
    $user = User::factory()->create();
    $resume = Resume::create(['user_id' => $user->id, 'title' => 'Junior Developer', 'is_primary' => true]);

    foreach (['Python', 'HTML', 'JavaScript', 'SQL', 'Git', 'React'] as $index => $name) {
        $skill = Skill::create(['user_id' => $user->id, 'name' => $name]);
        $resume->skills()->attach($skill->id, ['order' => $index]);
    }

    $match = app(JobRecommendationService::class)->forJob($resume->fresh(), jobMatchVacancy());
    $skills = collect($match['criteria'])->firstWhere('label', 'Skills');

    expect($skills['score'])->toBe(75)
        ->and($skills['matches'])->toContain('Python', 'HTML');
});

test('structured vacancy technologies are scored against required skills rather than all resume skills', function () {
    $user = User::factory()->create();
    $resume = Resume::create(['user_id' => $user->id, 'title' => 'Coding Instructor', 'is_primary' => true]);

    foreach (['Python Programming', 'HTML', 'JavaScript', 'Git'] as $index => $name) {
        $skill = Skill::create(['user_id' => $user->id, 'name' => $name]);
        $resume->skills()->attach($skill->id, ['order' => $index]);
    }

    $job = jobMatchVacancy(['technologies' => ['Python', 'HTML', 'Scratch']]);
    $match = app(JobRecommendationService::class)->forJob($resume->fresh(), $job);
    $skills = collect($match['criteria'])->firstWhere('label', 'Skills');

    expect($skills['score'])->toBe(67)
        ->and($match['gaps'])->toContain('Missing requirement: Scratch');
});

test('resume education remains visible when employer has no education requirement', function () {
    $user = User::factory()->create();
    $resume = Resume::create(['user_id' => $user->id, 'title' => 'Coding Instructor', 'is_primary' => true]);
    $education = Education::create([
        'user_id' => $user->id,
        'degree' => EducationDegree::HighSchool,
        'institution' => 'Dostoevsky School',
        'field_of_study' => 'Information Technology',
    ]);
    $resume->educations()->attach($education->id, ['order' => 0]);

    $match = app(JobRecommendationService::class)->forJob($resume->fresh(), jobMatchVacancy());
    $educationCriterion = collect($match['criteria'])->firstWhere('label', 'Education');

    expect($educationCriterion['status'])->toBe('informational')
        ->and($educationCriterion['score'])->toBeNull()
        ->and($educationCriterion['detail'])->toContain('Information Technology')
        ->and($educationCriterion['detail'])->toContain('Dostoevsky School')
        ->and($educationCriterion['detail'])->toContain('no employer requirement');
});

test('college student requirement is satisfied only by college level education records', function () {
    $user = User::factory()->create();
    $resume = Resume::create(['user_id' => $user->id, 'title' => 'Coding Instructor', 'is_primary' => true]);
    $education = Education::create([
        'user_id' => $user->id,
        'degree' => EducationDegree::Bachelors,
        'institution' => 'Example University',
        'field_of_study' => 'Information Technology',
    ]);
    $resume->educations()->attach($education->id, ['order' => 0]);

    $job = jobMatchVacancy([
        'requirements' => 'Applicants must be currently enrolled as a college or university student.',
    ]);
    $match = app(JobRecommendationService::class)->forJob($resume->fresh(), $job);
    $educationCriterion = collect($match['criteria'])->firstWhere('label', 'Education requirement');

    expect($educationCriterion['status'])->toBe('available')
        ->and($educationCriterion['score'])->toBe(100);
});

test('bachelors requirement is not satisfied by high school education', function () {
    $user = User::factory()->create();
    $resume = Resume::create(['user_id' => $user->id, 'title' => 'Coding Instructor', 'is_primary' => true]);
    $education = Education::create([
        'user_id' => $user->id,
        'degree' => EducationDegree::HighSchool,
        'institution' => 'Example School',
        'field_of_study' => 'Information Technology',
    ]);
    $resume->educations()->attach($education->id, ['order' => 0]);

    $job = jobMatchVacancy([
        'requirements' => "Bachelor's degree in Computer Science required.",
    ]);
    $match = app(JobRecommendationService::class)->forJob($resume->fresh(), $job);
    $educationCriterion = collect($match['criteria'])->firstWhere('label', 'Education requirement');

    expect($educationCriterion['status'])->toBe('available')
        ->and($educationCriterion['score'])->toBe(0);
});
