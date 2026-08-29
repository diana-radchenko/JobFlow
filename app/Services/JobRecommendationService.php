<?php

namespace App\Services;

use App\Models\Education;
use App\Models\Resume;
use App\Models\WorkExperience;
use App\Models\WorkJob;
use Illuminate\Support\Collection;

class JobRecommendationService
{
    public function __construct(private JobTitleNormalizer $titleNormalizer) {}

    /** @return Collection<int, array<string, mixed>> */
    public function forResume(Resume $resume): Collection
    {
        $resume->loadMissing(['skills', 'workExperiences', 'educations', 'user']);
        $appliedJobIds = $resume->user->applications()->pluck('work_job_id');
        $savedJobIds = $resume->user->savedWorkJobs()->pluck('work_jobs.id');

        return WorkJob::published()
            ->latest('published_at')
            ->get()
            ->map(fn (WorkJob $job) => $this->score($resume, $job, $appliedJobIds, $savedJobIds))
            ->sortByDesc('score')
            ->take(6)
            ->values();
    }

    /** @param Collection<int, int> $appliedJobIds @param Collection<int, int> $savedJobIds */
    private function score(Resume $resume, WorkJob $job, Collection $appliedJobIds, Collection $savedJobIds): array
    {
        $jobText = strtolower(implode(' ', [
            $job->title,
            $job->description,
            $job->requirements,
            implode(' ', $job->technologies ?? []),
        ]));
        $criteria = [];
        $strongMatches = [];
        $earned = 0.0;
        $available = 0;

        $skills = $resume->skills->pluck('name')->filter()->values();
        if ($skills->isNotEmpty()) {
            $available += 45;
            $matchedSkills = $skills->filter(fn (string $skill) => $this->contains($jobText, $skill))->values();
            $skillScore = $matchedSkills->count() / $skills->count();
            $earned += $skillScore * 45;
            $criteria[] = ['label' => 'Skills', 'score' => (int) round($skillScore * 100), 'matches' => $matchedSkills->all()];
            $strongMatches = [...$strongMatches, ...$matchedSkills->take(3)->map(fn ($skill) => "Relevant skill: {$skill}")->all()];
        }

        $roleTitles = collect([$resume->title])->merge($resume->workExperiences->pluck('job_title'))->filter();
        if ($roleTitles->isNotEmpty()) {
            $available += 30;
            $roleScore = (float) $roleTitles->max(fn (string $title) => $this->titleNormalizer->similarity($title, $job->title));
            $earned += $roleScore * 30;
            $criteria[] = ['label' => 'Role relevance', 'score' => (int) round($roleScore * 100)];
            if ($roleScore >= 0.6) {
                $strongMatches[] = 'Relevant role experience';
            }
        }

        if ($resume->workExperiences->isNotEmpty()) {
            $available += 15;
            $experienceScore = (float) $resume->workExperiences->max(
                fn (WorkExperience $experience) => max(
                    $this->titleNormalizer->similarity($experience->job_title, $job->title),
                    $this->contains($jobText, $experience->description ?? '') ? 0.5 : 0,
                )
            );
            $earned += $experienceScore * 15;
            $criteria[] = ['label' => 'Experience', 'score' => (int) round($experienceScore * 100)];
        }

        if ($resume->educations->isNotEmpty()) {
            $available += 10;
            $educationMatches = $resume->educations->filter(fn (Education $education) =>
                $this->contains($jobText, $education->field_of_study ?? '')
                || $this->contains($jobText, $education->degree?->value ?? '')
            );
            $educationScore = $educationMatches->count() / $resume->educations->count();
            $earned += $educationScore * 10;
            $criteria[] = ['label' => 'Education', 'score' => (int) round($educationScore * 100)];
        }

        $resumeSkills = $skills->map(fn (string $skill) => strtolower($skill));
        $gaps = collect($job->technologies ?? [])
            ->filter(fn ($technology) => ! $resumeSkills->contains(strtolower((string) $technology)))
            ->take(2)
            ->map(fn ($technology) => "Missing requirement: {$technology}")
            ->values()
            ->all();

        return [
            'job' => $job,
            'score' => $available > 0 ? (int) round($earned / $available * 100) : 0,
            'criteria' => $criteria,
            'strong_matches' => array_values(array_unique($strongMatches)),
            'gaps' => $gaps,
            'applied' => $appliedJobIds->contains($job->id),
            'saved' => $savedJobIds->contains($job->id),
        ];
    }

    private function contains(string $haystack, string $term): bool
    {
        $term = trim(strtolower($term));

        return $term !== '' && preg_match('/(?<![a-z0-9])'.preg_quote($term, '/').'(?![a-z0-9])/i', $haystack) === 1;
    }
}
