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

    /** @return array<string, mixed> */
    public function forJob(Resume $resume, WorkJob $job): array
    {
        $resume->loadMissing(['skills', 'workExperiences', 'educations', 'user']);
        $appliedJobIds = $resume->user->applications()->pluck('work_job_id');
        $savedJobIds = $resume->user->savedWorkJobs()->pluck('work_jobs.id');

        return $this->score($resume, $job, $appliedJobIds, $savedJobIds);
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
            $requiredSkills = collect($job->technologies ?? [])->map(fn ($skill) => trim((string) $skill))->filter()->unique()->values();

            if ($requiredSkills->isNotEmpty()) {
                $matchedRequirements = $requiredSkills->filter(
                    fn (string $requirement) => $skills->contains(
                        fn (string $skill) => $this->skillMatchesRequirement($skill, $requirement),
                    ),
                )->values();
                $matchedSkills = $skills->filter(
                    fn (string $skill) => $requiredSkills->contains(
                        fn (string $requirement) => $this->skillMatchesRequirement($skill, $requirement),
                    ),
                )->values();
                $skillScore = $matchedRequirements->count() / $requiredSkills->count();
            } else {
                // When a vacancy has no structured technology list, use positive evidence
                // found in the vacancy text without penalizing a candidate for unrelated
                // extra skills present in their resume.
                $matchedSkills = $skills->filter(fn (string $skill) => $this->contains($jobText, $skill))->values();
                $skillScore = match (true) {
                    $matchedSkills->count() >= 3 => 1.0,
                    $matchedSkills->count() === 2 => 0.75,
                    $matchedSkills->count() === 1 => 0.5,
                    default => 0.0,
                };
            }

            $earned += $skillScore * 45;
            $criteria[] = [
                'label' => 'Skills',
                'score' => (int) round($skillScore * 100),
                'status' => 'available',
                'matches' => $matchedSkills->all(),
            ];
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
                    $this->experienceTextOverlap($jobText, (string) ($experience->description ?? '')),
                )
            );
            $earned += $experienceScore * 15;
            $criteria[] = ['label' => 'Experience', 'score' => (int) round($experienceScore * 100), 'status' => 'available'];
        } else {
            $criteria[] = ['label' => 'Experience', 'score' => null, 'status' => 'not_enough_data'];
        }

        if (! $this->educationRequirementSpecified($jobText)) {
            $criteria[] = ['label' => 'Education requirement', 'score' => null, 'status' => 'not_specified'];
        } elseif ($resume->educations->isEmpty()) {
            $criteria[] = ['label' => 'Education requirement', 'score' => null, 'status' => 'not_enough_data'];
        } else {
            $available += 10;
            $educationScore = (float) $resume->educations->max(
                fn (Education $education) => $this->educationMatchScore($jobText, $education),
            );
            $earned += $educationScore * 10;
            $criteria[] = ['label' => 'Education requirement', 'score' => (int) round($educationScore * 100), 'status' => 'available'];
        }

        $gaps = collect($job->technologies ?? [])
            ->filter(fn ($technology) => ! $skills->contains(
                fn (string $skill) => $this->skillMatchesRequirement($skill, (string) $technology),
            ))
            ->take(2)
            ->map(fn ($technology) => "Missing requirement: {$technology}")
            ->values()
            ->all();

        return [
            'job' => $job,
            'score' => $available > 0 ? (int) round($earned / $available * 100) : null,
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

    private function skillMatchesRequirement(string $resumeSkill, string $requirement): bool
    {
        $resumeSkill = $this->normalizeSkill($resumeSkill);
        $requirement = $this->normalizeSkill($requirement);

        if ($resumeSkill === '' || $requirement === '') {
            return false;
        }

        return $resumeSkill === $requirement
            || str_contains($resumeSkill, $requirement)
            || str_contains($requirement, $resumeSkill);
    }

    private function normalizeSkill(string $skill): string
    {
        $skill = strtolower(trim($skill));
        $skill = preg_replace('/\b(programming|development|developer)\b/', '', $skill) ?? $skill;
        $skill = preg_replace('/[^a-z0-9+#.]+/', ' ', $skill) ?? $skill;

        return trim(preg_replace('/\s+/', ' ', $skill) ?? $skill);
    }

    private function experienceTextOverlap(string $jobText, string $description): float
    {
        $tokens = preg_split('/[^a-z0-9+#.]+/', strtolower($description), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $keywords = collect($tokens)
            ->filter(fn (string $token) => strlen($token) >= 4)
            ->unique()
            ->take(20);

        if ($keywords->isEmpty()) {
            return 0.0;
        }

        $matches = $keywords->filter(fn (string $token) => $this->contains($jobText, $token))->count();
        $ratio = $matches / $keywords->count();

        return min(0.6, $ratio);
    }

    private function educationRequirementSpecified(string $jobText): bool
    {
        return preg_match('/\b(degree|required education|education requirement|bachelor(?:\'s)?|master(?:\'s)?|phd|doctorate|college diploma|academic qualification|high school diploma|currently enrolled|college student|university student)\b/i', $jobText) === 1;
    }

    private function educationMatchScore(string $jobText, Education $education): float
    {
        $field = trim((string) ($education->field_of_study ?? ''));
        $degree = trim((string) ($education->degree?->value ?? ''));
        $institution = trim((string) ($education->institution ?? ''));
        $resumeEducation = strtolower(trim("{$degree} {$field} {$institution}"));

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
        $jobRequiresTechnologyEducation = collect($technologyFields)
            ->contains(fn (string $term) => str_contains($jobText, $term));
        $resumeHasTechnologyEducation = collect($technologyFields)
            ->contains(fn (string $term) => str_contains($resumeEducation, $term));

        if ($jobRequiresTechnologyEducation && $resumeHasTechnologyEducation) {
            return 1.0;
        }

        // If the employer only requires general enrollment/degree completion and the
        // resume contains an education record, treat that requirement as satisfied.
        if (preg_match('/\b(degree|college student|university student|currently enrolled|high school diploma)\b/i', $jobText) === 1) {
            return 1.0;
        }

        return 0.0;
    }
}
