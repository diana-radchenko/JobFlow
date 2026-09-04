<?php

namespace App\Services;

use App\Enums\EducationDegree;
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
        $this->loadMatchRelations($resume);
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
        $this->loadMatchRelations($resume);
        $appliedJobIds = $resume->user->applications()->pluck('work_job_id');
        $savedJobIds = $resume->user->savedWorkJobs()->pluck('work_jobs.id');

        return $this->score($resume, $job, $appliedJobIds, $savedJobIds);
    }

    private function loadMatchRelations(Resume $resume): void
    {
        $resume->loadMissing([
            'skills',
            'workExperiences',
            'educations',
            'projects',
            'leadershipActivities',
            'volunteerExperiences',
            'user',
        ]);
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
        $available += 45;
        if ($skills->isNotEmpty()) {
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
                $matchedSkills = $skills->filter(fn (string $skill) => $this->contains($jobText, $skill))->values();
                $skillScore = match (true) {
                    $matchedSkills->count() >= 3 => 1.0,
                    $matchedSkills->count() === 2 => 0.75,
                    $matchedSkills->count() === 1 => 0.5,
                    default => 0.0,
                };
            }
        } else {
            $matchedSkills = collect();
            $skillScore = 0.0;
        }

        $earned += $skillScore * 45;
        $criteria[] = [
            'label' => 'Skills',
            'score' => (int) round($skillScore * 100),
            'status' => 'available',
            'matches' => $matchedSkills->all(),
        ];
        $strongMatches = [...$strongMatches, ...$matchedSkills->take(3)->map(fn ($skill) => "Relevant skill: {$skill}")->all()];

        $roleTitles = collect([$resume->title])
            ->merge($resume->workExperiences->pluck('job_title'))
            ->merge($resume->leadershipActivities->pluck('role'))
            ->merge($resume->volunteerExperiences->pluck('role'))
            ->merge($resume->projects->pluck('title'))
            ->filter();

        $available += 30;
        $roleScore = $roleTitles->isNotEmpty()
            ? (float) $roleTitles->max(fn (string $title) => $this->titleNormalizer->similarity($title, $job->title))
            : 0.0;
        $earned += $roleScore * 30;
        $criteria[] = ['label' => 'Role relevance', 'score' => (int) round($roleScore * 100), 'status' => 'available'];
        if ($roleScore >= 0.6) {
            $strongMatches[] = 'Relevant role experience';
        }

        $experienceScores = collect();
        $resume->workExperiences->each(function (WorkExperience $experience) use ($experienceScores, $job, $jobText) {
            $experienceScores->push(max(
                $this->titleNormalizer->similarity((string) $experience->job_title, $job->title),
                $this->experienceTextOverlap($jobText, (string) ($experience->description ?? '')),
            ));
        });
        $resume->leadershipActivities->each(function ($activity) use ($experienceScores, $job, $jobText) {
            $experienceScores->push(max(
                $this->titleNormalizer->similarity((string) ($activity->role ?? ''), $job->title),
                $this->experienceTextOverlap($jobText, (string) ($activity->description ?? '')),
            ));
        });
        $resume->volunteerExperiences->each(function ($activity) use ($experienceScores, $job, $jobText) {
            $experienceScores->push(max(
                $this->titleNormalizer->similarity((string) ($activity->role ?? ''), $job->title),
                $this->experienceTextOverlap($jobText, (string) ($activity->description ?? '')),
            ));
        });
        $resume->projects->each(function ($project) use ($experienceScores, $job, $jobText) {
            $experienceScores->push(max(
                $this->titleNormalizer->similarity((string) ($project->title ?? ''), $job->title),
                $this->experienceTextOverlap($jobText, (string) ($project->description ?? '')),
            ));
        });

        $available += 15;
        $experienceScore = $experienceScores->isNotEmpty() ? (float) $experienceScores->max() : 0.0;
        $earned += $experienceScore * 15;
        $criteria[] = ['label' => 'Experience', 'score' => (int) round($experienceScore * 100), 'status' => 'available'];

        if (! $this->educationRequirementSpecified($jobText)) {
            $criteria[] = ['label' => 'Education', 'score' => null, 'status' => 'not_required'];
        } else {
            $available += 10;
            $educationScore = $resume->educations->isNotEmpty()
                ? (float) $resume->educations->max(fn (Education $education) => $this->educationMatchScore($jobText, $education))
                : 0.0;
            $earned += $educationScore * 10;
            $criteria[] = ['label' => 'Education', 'score' => (int) round($educationScore * 100), 'status' => 'available'];
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
        $keywords = collect($tokens)->filter(fn (string $token) => strlen($token) >= 4)->unique()->take(20);
        if ($keywords->isEmpty()) {
            return 0.0;
        }
        $matches = $keywords->filter(fn (string $token) => $this->contains($jobText, $token))->count();
        return min(0.6, $matches / $keywords->count());
    }

    private function educationRequirementSpecified(string $jobText): bool
    {
        return preg_match('/\b(degree|required education|education requirement|bachelor(?:\'s)?|master(?:\'s)?|phd|doctorate|college diploma|academic qualification|high school diploma|currently enrolled|college student|university student)\b/i', $jobText) === 1;
    }

    private function educationMatchScore(string $jobText, Education $education): float
    {
        $field = trim((string) ($education->field_of_study ?? ''));
        $degree = $education->degree;
        $degreeValue = $degree?->value ?? '';
        $institution = trim((string) ($education->institution ?? ''));
        $resumeEducation = strtolower(trim("{$degreeValue} {$field} {$institution}"));

        $requiredRank = $this->requiredEducationRank($jobText);
        if ($requiredRank !== null && $this->educationDegreeRank($degree) < $requiredRank) {
            return 0.0;
        }

        $technologyFields = ['computer science', 'information technology', 'software engineering', 'programming', 'computer engineering'];
        $jobRequiresTechnologyEducation = collect($technologyFields)->contains(fn (string $term) => str_contains($jobText, $term));
        $resumeHasTechnologyEducation = collect($technologyFields)->contains(fn (string $term) => str_contains($resumeEducation, $term));
        if ($jobRequiresTechnologyEducation) {
            return $resumeHasTechnologyEducation ? 1.0 : 0.0;
        }
        if ($requiredRank !== null) {
            return 1.0;
        }
        if (preg_match('/\b(college student|university student|currently enrolled)\b/i', $jobText) === 1) {
            return in_array($degree, [EducationDegree::Associate, EducationDegree::Bachelors, EducationDegree::Masters, EducationDegree::Doctorate, EducationDegree::PostdoctoralResearcher], true) ? 1.0 : 0.0;
        }
        if ($this->contains($jobText, $field) || ($degreeValue !== '' && $this->contains($jobText, str_replace('_', ' ', $degreeValue)))) {
            return 1.0;
        }
        return preg_match('/\b(degree|required education|education requirement|academic qualification|college diploma)\b/i', $jobText) === 1 ? 1.0 : 0.0;
    }

    private function requiredEducationRank(string $jobText): ?int
    {
        return match (true) {
            preg_match('/\b(phd|doctorate|doctoral)\b/i', $jobText) === 1 => 5,
            preg_match('/\bmaster(?:\'s)?\b/i', $jobText) === 1 => 4,
            preg_match('/\bbachelor(?:\'s)?\b/i', $jobText) === 1 => 3,
            preg_match('/\b(associate(?:\'s)?|college diploma)\b/i', $jobText) === 1 => 2,
            preg_match('/\bhigh school diploma\b/i', $jobText) === 1 => 1,
            default => null,
        };
    }

    private function educationDegreeRank(?EducationDegree $degree): int
    {
        return match ($degree) {
            EducationDegree::HighSchool, EducationDegree::Certificate => 1,
            EducationDegree::Associate => 2,
            EducationDegree::Bachelors => 3,
            EducationDegree::Masters => 4,
            EducationDegree::Doctorate, EducationDegree::PostdoctoralResearcher => 5,
            default => 0,
        };
    }
}
