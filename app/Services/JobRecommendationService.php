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
            $criteria[] = ['label' => 'Skills', 'score' => (int) round($skillScore * 100), 'status' => 'available', 'matches' => $matchedSkills->all()];
            $strongMatches = [...$strongMatches, ...$matchedSkills->take(3)->map(fn ($skill) => "Relevant skill: {$skill}")->all()];
        } else {
            $criteria[] = ['label' => 'Skills', 'score' => null, 'status' => 'not_enough_data'];
        }

        $roleTitles = collect([$resume->title])->merge($resume->workExperiences->pluck('job_title'))->filter();
        if ($roleTitles->isNotEmpty()) {
            $available += 30;
            $roleScore = (float) $roleTitles->max(fn (string $title) => $this->titleNormalizer->similarity($title, $job->title));
            $earned += $roleScore * 30;
            $criteria[] = ['label' => 'Role relevance', 'score' => (int) round($roleScore * 100), 'status' => 'available'];
            if ($roleScore >= 0.6) {
                $strongMatches[] = 'Relevant role experience';
            }
        } else {
            $criteria[] = ['label' => 'Role relevance', 'score' => null, 'status' => 'not_enough_data'];
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
            $criteria[] = ['label' => 'Experience', 'score' => (int) round($experienceScore * 100), 'status' => 'available'];
        } else {
            $criteria[] = ['label' => 'Experience', 'score' => null, 'status' => 'not_enough_data'];
        }

        if (! $this->educationRequirementSpecified($jobText)) {
            $criteria[] = ['label' => 'Education', 'score' => null, 'status' => 'not_specified'];
        } elseif ($resume->educations->isEmpty()) {
            $criteria[] = ['label' => 'Education', 'score' => null, 'status' => 'not_enough_data'];
        } else {
            $available += 10;
            $educationScore = (float) $resume->educations->max(
                fn (Education $education) => $this->educationMatchScore($jobText, $education),
            );
            $earned += $educationScore * 10;
            $criteria[] = ['label' => 'Education', 'score' => (int) round($educationScore * 100), 'status' => 'available'];
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

    private function educationRequirementSpecified(string $jobText): bool
    {
        return preg_match('/\b(degree|required education|education requirement|bachelor(?:\'s)?|master(?:\'s)?|phd|doctorate|college diploma|academic qualification)\b/i', $jobText) === 1;
    }

    private function educationMatchScore(string $jobText, Education $education): float
    {
        $field = trim((string) ($education->field_of_study ?? ''));
        $degree = trim((string) ($education->degree?->value ?? ''));

        if ($this->contains($jobText, $field) || $this->contains($jobText, $degree)) {
            return 1.0;
        }

        $technologyFields = [
            'computer science',
            'information technology',
            'software engineering',
            'programming',
            'computer engineering',
        ];
        $resumeEducation = strtolower("{$degree} {$field}");
        $jobRequiresTechnologyEducation = collect($technologyFields)
            ->contains(fn (string $term) => str_contains($jobText, $term));
        $resumeHasTechnologyEducation = collect($technologyFields)
            ->contains(fn (string $term) => str_contains($resumeEducation, $term));

        return $jobRequiresTechnologyEducation && $resumeHasTechnologyEducation ? 1.0 : 0.0;
    }
}
