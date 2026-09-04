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

test('structured skill matching does not confuse substring names', function () {
    $user = User::factory()->create();
    $resume = Resume::create(['user_id' => $user->id, 'title' => 'Developer', 'is_primary' => true]);

    foreach (['JavaScript', 'NoSQL'] as $index => $name) {
        $skill = Skill::create(['user_id' => $user->id, 'name' => $name]);
        $resume->skills()->attach($skill->id, ['order' => $index]);
    }

    $job = jobMatchVacancy(['technologies' => ['Java', 'SQL']]);
    $match = app(JobRecommendationService::class)->forJob($resume->fresh(), $job);
    $skills = collect($match['criteria'])->firstWhere('label', 'Skills');

    expect($skills['score'])->toBe(0)
        ->and($match['gaps'])->toContain('Missing requirement: Java', 'Missing requirement: SQL');
});

test('unicode skill names preserve exact matching', function () {
    $user = User::factory()->create();
    $resume = Resume::create(['user_id' => $user->id, 'title' => 'Tutor', 'is_primary' => true]);
    $skill = Skill::create(['user_id' => $user->id, 'name' => '日本語']);
    $resume->skills()->attach($skill->id, ['order' => 0]);

    $job = jobMatchVacancy(['technologies' => ['日本語']]);
    $match = app(JobRecommendationService::class)->forJob($resume->fresh(), $job);
    $skills = collect($match['criteria'])->firstWhere('label', 'Skills');

    expect($skills['score'])->toBe(100);
});

test('education is neutral when employer has no education requirement', function () {
    $user = User::factory()->create();
    $resume = Resume::create(['user_id' => $user->id, 'title' => 'Coding Instructor', 'is_primary' => true]);
    $education = Education::create([
        'user_id' => $user->id,
        'degree' => EducationDegree::HighSchool,
        'institution' => 'Dostoevsky School',
        'field_of_study' => 'Information Technology',
        'start_date' => '2024-09-01',
    ]);
    $resume->educations()->attach($education->id, ['order' => 0]);

    $match = app(JobRecommendationService::class)->forJob($resume->fresh(), jobMatchVacancy());
    $educationCriterion = collect($match['criteria'])->firstWhere('label', 'Education');

    expect($educationCriterion['status'])->toBe('not_required')
        ->and($educationCriterion['score'])->toBeNull()
        ->and($educationCriterion)->not->toHaveKey('detail');
});

test('college student requirement is satisfied only by currently enrolled college level education records', function () {
    $user = User::factory()->create();
    $resume = Resume::create(['user_id' => $user->id, 'title' => 'Coding Instructor', 'is_primary' => true]);
    $education = Education::create([
        'user_id' => $user->id,
        'degree' => EducationDegree::Bachelors,
        'institution' => 'Example University',
        'field_of_study' => 'Information Technology',
        'start_date' => '2025-09-01',
        'end_date' => now()->addYear()->toDateString(),
    ]);
    $resume->educations()->attach($education->id, ['order' => 0]);

    $job = jobMatchVacancy([
        'requirements' => 'Applicants must be currently enrolled as a college or university student.',
    ]);
    $match = app(JobRecommendationService::class)->forJob($resume->fresh(), $job);
    $educationCriterion = collect($match['criteria'])->firstWhere('label', 'Education');

    expect($educationCriterion['status'])->toBe('available')
        ->and($educationCriterion['score'])->toBe(100);
});

test('past education does not satisfy currently enrolled requirement', function () {
    $user = User::factory()->create();
    $resume = Resume::create(['user_id' => $user->id, 'title' => 'Coding Instructor', 'is_primary' => true]);
    $education = Education::create([
        'user_id' => $user->id,
        'degree' => EducationDegree::Bachelors,
        'institution' => 'Example University',
        'field_of_study' => 'Information Technology',
        'start_date' => '2018-09-01',
        'end_date' => '2022-06-30',
    ]);
    $resume->educations()->attach($education->id, ['order' => 0]);

    $job = jobMatchVacancy(['requirements' => 'Applicants must be currently enrolled as a university student.']);
    $match = app(JobRecommendationService::class)->forJob($resume->fresh(), $job);
    $educationCriterion = collect($match['criteria'])->firstWhere('label', 'Education');

    expect($educationCriterion['score'])->toBe(0);
});

test('bachelors requirement is not satisfied by high school education', function () {
    $user = User::factory()->create();
    $resume = Resume::create(['user_id' => $user->id, 'title' => 'Coding Instructor', 'is_primary' => true]);
    $education = Education::create([
        'user_id' => $user->id,
        'degree' => EducationDegree::HighSchool,
        'institution' => 'Example School',
        'field_of_study' => 'Information Technology',
        'start_date' => '2024-09-01',
    ]);
    $resume->educations()->attach($education->id, ['order' => 0]);

    $job = jobMatchVacancy([
        'requirements' => "Bachelor's degree in Computer Science required.",
    ]);
    $match = app(JobRecommendationService::class)->forJob($resume->fresh(), $job);
    $educationCriterion = collect($match['criteria'])->firstWhere('label', 'Education');

    expect($educationCriterion['status'])->toBe('available')
        ->and($educationCriterion['score'])->toBe(0);
});

test('matching degree rank still requires the requested field of study', function () {
    $user = User::factory()->create();
    $resume = Resume::create(['user_id' => $user->id, 'title' => 'Analyst', 'is_primary' => true]);
    $education = Education::create([
        'user_id' => $user->id,
        'degree' => EducationDegree::Bachelors,
        'institution' => 'Example University',
        'field_of_study' => 'History',
        'start_date' => '2020-09-01',
    ]);
    $resume->educations()->attach($education->id, ['order' => 0]);

    $job = jobMatchVacancy(['requirements' => "Bachelor's degree in Accounting required."]);
    $match = app(JobRecommendationService::class)->forJob($resume->fresh(), $job);
    $educationCriterion = collect($match['criteria'])->firstWhere('label', 'Education');

    expect($educationCriterion['score'])->toBe(0);
});

test('bachelors required with masters preferred keeps bachelors as minimum requirement', function () {
    $user = User::factory()->create();
    $resume = Resume::create(['user_id' => $user->id, 'title' => 'Developer', 'is_primary' => true]);
    $education = Education::create([
        'user_id' => $user->id,
        'degree' => EducationDegree::Bachelors,
        'institution' => 'Example University',
        'field_of_study' => 'Computer Science',
        'start_date' => '2021-09-01',
    ]);
    $resume->educations()->attach($education->id, ['order' => 0]);

    $job = jobMatchVacancy(['requirements' => "Bachelor's degree in Computer Science required. Master's preferred."]);
    $match = app(JobRecommendationService::class)->forJob($resume->fresh(), $job);
    $educationCriterion = collect($match['criteria'])->firstWhere('label', 'Education');

    expect($educationCriterion['score'])->toBe(100);
});
