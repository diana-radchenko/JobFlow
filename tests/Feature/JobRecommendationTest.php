<?php

use App\Enums\ApplicationStatus;
use App\Models\Resume;
use App\Models\User;
use App\Models\UserWorkJobApplication;
use App\Models\WorkJob;
use App\Services\JobRecommendationService;
use App\Services\JobTitleNormalizer;
use Inertia\Testing\AssertableInertia as Assert;

function recommendationJob(User $employer, array $attributes = []): WorkJob
{
    return WorkJob::factory()->for($employer, 'employer')->create(array_merge([
        'title' => 'Laravel Developer',
        'description' => 'Build web applications with Laravel and PHP.',
        'requirements' => 'PHP and Laravel experience',
        'technologies' => ['PHP', 'Laravel'],
        'status' => 'published',
        'published_at' => now(),
    ], $attributes));
}

test('recommendations contain only real published active vacancies', function () {
    $candidate = User::factory()->create();
    $employer = User::factory()->employer()->create();
    $resume = Resume::create(['user_id' => $candidate->id, 'title' => 'Laravel Developer']);
    $published = recommendationJob($employer);
    recommendationJob($employer, ['status' => 'draft']);
    recommendationJob($employer, ['status' => 'closed']);
    WorkJob::factory()->create(['status' => 'published']);

    $result = app(JobRecommendationService::class)->forResume($resume);

    expect($result)->toHaveCount(1)
        ->and($result->first()['job']->id)->toBe($published->id);
});

test('the selected resume materially changes recommendation ranking', function () {
    $candidate = User::factory()->create();
    $employer = User::factory()->employer()->create();
    $phpResume = Resume::create(['user_id' => $candidate->id, 'title' => 'Laravel Developer']);
    $designResume = Resume::create(['user_id' => $candidate->id, 'title' => 'Product Designer']);
    $phpResume->skills()->create(['user_id' => $candidate->id, 'name' => 'Laravel', 'proficiency_level' => 'advanced']);
    $designResume->skills()->create(['user_id' => $candidate->id, 'name' => 'Figma', 'proficiency_level' => 'advanced']);
    $phpJob = recommendationJob($employer);
    $designJob = recommendationJob($employer, [
        'title' => 'Product Designer',
        'description' => 'Design products in Figma.',
        'requirements' => 'Figma',
        'technologies' => ['Figma'],
    ]);

    $service = app(JobRecommendationService::class);

    expect($service->forResume($phpResume)->first()['job']->id)->toBe($phpJob->id)
        ->and($service->forResume($designResume)->first()['job']->id)->toBe($designJob->id);
});

test('already applied state is returned for the current candidate only', function () {
    $candidate = User::factory()->create();
    $other = User::factory()->create();
    $employer = User::factory()->employer()->create();
    $resume = Resume::create(['user_id' => $candidate->id, 'title' => 'Laravel Developer']);
    $otherResume = Resume::create(['user_id' => $other->id, 'title' => 'Laravel Developer']);
    $job = recommendationJob($employer);
    UserWorkJobApplication::create([
        'user_id' => $other->id,
        'work_job_id' => $job->id,
        'resume_id' => $otherResume->id,
        'status' => ApplicationStatus::Applied,
    ]);

    expect(app(JobRecommendationService::class)->forResume($resume)->first()['applied'])->toBeFalse();

    UserWorkJobApplication::create([
        'user_id' => $candidate->id,
        'work_job_id' => $job->id,
        'resume_id' => $resume->id,
        'status' => ApplicationStatus::Applied,
    ]);

    expect(app(JobRecommendationService::class)->forResume($resume)->first()['applied'])->toBeTrue();
});

test('dashboard cannot select another candidates resume for recommendations', function () {
    $candidate = User::factory()->create();
    $other = User::factory()->create();
    $ownResume = Resume::create(['user_id' => $candidate->id, 'title' => 'Own Resume']);
    $otherResume = Resume::create(['user_id' => $other->id, 'title' => 'Other Resume']);

    $this->actingAs($candidate)->get(route('dashboard', ['resume_id' => $otherResume->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedResumeId', $ownResume->id)
            ->has('resumes', 1)
            ->where('resumes.0.id', $ownResume->id));
});

test('recommendation score and explanations come from evaluated resume fields', function () {
    $candidate = User::factory()->create();
    $employer = User::factory()->employer()->create();
    $resume = Resume::create(['user_id' => $candidate->id, 'title' => 'Laravel Developer']);
    $resume->skills()->create(['user_id' => $candidate->id, 'name' => 'Laravel', 'proficiency_level' => 'advanced']);
    recommendationJob($employer);

    $result = app(JobRecommendationService::class)->forResume($resume)->first();

    expect($result['score'])->toBe(100)
        ->and(collect($result['criteria'])->pluck('label')->all())->toBe(['Skills', 'Role relevance', 'Experience', 'Education'])
        ->and(collect($result['criteria'])->firstWhere('label', 'Education')['status'])->toBe('not_specified')
        ->and($result['strong_matches'])->toContain('Relevant skill: Laravel', 'Relevant role experience');
});

test('technology role wording alone is not treated as an education requirement', function () {
    $candidate = User::factory()->create();
    $employer = User::factory()->employer()->create();
    $resume = Resume::create(['user_id' => $candidate->id, 'title' => 'Programming Instructor']);
    recommendationJob($employer, [
        'title' => 'Programming Instructor',
        'description' => 'Teach programming fundamentals to students.',
        'requirements' => 'Professional teaching experience',
        'technologies' => [],
    ]);

    $result = app(JobRecommendationService::class)->forResume($resume)->first();

    expect(collect($result['criteria'])->firstWhere('label', 'Education')['status'])
        ->toBe('not_specified');
});

test('related teaching role titles normalize without matching unrelated roles', function () {
    $normalizer = app(JobTitleNormalizer::class);

    expect($normalizer->similarity('Coding Teacher', 'Programming Tutor'))->toBe(1.0)
        ->and($normalizer->comparable('Coding Instructor', 'Programming Tutor'))->toBeTrue()
        ->and($normalizer->comparable('Coding Instructor', 'Financial Analyst'))->toBeFalse();
});
