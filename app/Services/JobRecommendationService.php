<?php

namespace App\Services;

use App\Models\Resume;
use App\Models\WorkJob;
use Illuminate\Support\Collection;

class JobRecommendationService
{
    /** @return Collection<int, array<string, mixed>> */
    public function forResume(Resume $resume): Collection
    {
        $resume->loadMissing(['skills', 'workExperiences', 'educations', 'projects', 'languages']);
        $terms = collect($resume->skills)->pluck('name')
            ->merge($resume->workExperiences->pluck('job_title'))
            ->merge($resume->projects->pluck('title'))->filter()->map(fn ($value) => strtolower((string) $value));

        return WorkJob::published()->latest('published_at')->get()->map(function (WorkJob $job) use ($terms) {
            $haystack = strtolower(implode(' ', [$job->title, $job->description, $job->requirements, implode(' ', $job->technologies ?? [])]));
            $matches = $terms->filter(fn (string $term) => str_contains($haystack, $term))->unique()->values();
            $score = min(100, 25 + $matches->count() * 15);
            return ['job' => $job, 'score' => $score, 'reasons' => $matches->take(3)->map(fn ($term) => ucfirst($term).' from your resume appears in this vacancy.')->all()];
        })->sortByDesc('score')->take(6)->values();
    }
}
