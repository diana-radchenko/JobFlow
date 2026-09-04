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

            $earned += $skillScore * 45;
            $criteria[] = [
                'label' => 'Skills',
                'score' => (int) round($skillScore * 100),
                'status' => 'available',
                'matches' => $matchedSkills->all(),
            ];
            $strongMatches = [...$strongMatches, ...$matchedSkills->take(3)->map(fn ($skill) => "Relevant skill: {$skill}")->all()];
        } else {
            $criteria[] = ['label' => 'Skills', 'score' => 0, 'status' => 'available', 'matches' => []];
        }

        $roleTitles = collect([$resume->title])
            ->merge($resume->workExperiences->pluck('job_title'))
            ->merge($resume->leadershipActivities->pluck('role'))
            ->merge($resume->volunteerExperiences->pluck('role'))
            ->merge($resume->projects->pluck('title'))
            ->filter();

        $available += 30;
        if ($roleTitles->isNotEmpty()) {
            $roleScore = (float) $roleTitles->max(fn (string $title) => $this->titleNormalizer->similarity($title, $job->title));
            $earned += $roleScore * 30;
            $criteria[] = ['label' => 'Role relevance', 'score' => (int) round($roleScore * 100), 'status' => 'available'];
            if ($roleScore >= 0.6) {
                $strongMatches[] = 'Relevant role experience';
            }
        } else {
            $criteria[] = ['label' => 'Role relevance', 'score' => 0, 'status' => 'available'];
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
        if ($experienceScores->isNotEmpty()) {
            $experienceScore = (float) $experienceScores->max();
            $earned += $experienceScore * 15;
            $criteria[] = ['label' => 'Experience', 'score' => (int) round($experienceScore * 100), 'status' => 'available'];
        } else {
            $criteria[] = ['label' => 'Experience', 'score' => 0, 'status' => 'available'];
        }

        if (! $this->educationRequirementSpecified($jobText)) {
            $criteria[] = ['label' => 'Education', 'score' => null, 'status' => 'not_required'];
        } else {
            $available += 10;
            if ($resume->educations->isEmpty()) {
                $criteria[] = ['label' => 'Education', 'score' => 0, 'status' => 'available'];
            } else {
                $educationScore = (float) $resume->educations->max(
                    fn (Education $education) => $this->educationMatchScore($jobText, $education),
                );
                $earned += $educationScore * 10;
                $criteria[] = ['label' => 'Education', 'score' => (int) round($educationScore * 100), 'status' => 'available'];
            }
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

        return $term !== '' && preg_match('/(?<![\p{L}\p{N}])'.preg_quote($term, '/').'(?![\p{L}\p{N}])/iu', $haystack) === 1;
    }

    private function skillMatchesRequirement(string $resumeSkill, string $requirement): bool
    {
        $resumeSkill = $this->normalizeSkill($resumeSkill);
        $requirement = $this->normalizeSkill($requirement);

        return $resumeSkill !== '' && $resumeSkill === $requirement;
    }

    private function normalizeSkill(string $skill): string
    {
        $skill = mb_strtolower(trim($skill));
        $skill = preg_replace('/\b(programming|development|developer)\b/ui', '', $skill) ?? $skill;
        $skill = preg_replace('/[^\p{L}\p{N}+#.]+/u', ' ', $skill) ?? $skill;

        return trim(preg_replace('/\s+/u', ' ', $skill) ?? $skill);
    }

    private function experienceTextOverlap(string $jobText, string $description): float
    {
        $tokens = preg_split('/[^\p{L}\p{N}+#.]+/u', mb_strtolower($description), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $stopWords = [
            'with', 'from', 'that', 'this', 'have', 'has', 'using', 'used', 'work', 'worked',
            'team', 'teams', 'modern', 'tools', 'role', 'responsible', 'including', 'across', 'into',
            'their', 'there', 'where', 'which', 'while', 'about', 'through', 'within', 'project', 'projects',
        ];
        $keywords = collect($tokens)
            ->filter(fn (string $token) => mb_strlen($token) >= 4 && ! in_array($token, $stopWords, true))
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
        $degree = $education->degree;

        if (preg_match('/\b(currently enrolled|college student|university student)\b/i', $jobText) === 1) {
            $collegeLevel = in_array($degree, [
                EducationDegree::Associate,
                EducationDegree::Bachelors,
                EducationDegree::Masters,
                EducationDegree::Doctorate,
                EducationDegree::PostdoctoralResearcher,
            ], true);
            $currentlyEnrolled = $education->end_date === null || $education->end_date->isToday() || $education->end_date->isFuture();

            return $collegeLevel && $currentlyEnrolled ? 1.0 : 0.0;
        }

        $requiredRank = $this->requiredEducationRank($jobText);
        if ($requiredRank !== null && $this->educationDegreeRank($degree) < $requiredRank) {
            return 0.0;
        }

        $requiredField = $this->requiredFieldOfStudy($jobText);
        if ($requiredField !== null) {
            return $this->fieldOfStudyMatches($field, $requiredField) ? 1.0 : 0.0;
        }

        return $requiredRank !== null ? 1.0 : 0.0;
    }

    private function requiredFieldOfStudy(string $jobText): ?string
    {
        if (preg_match('/\b(?:bachelor(?:\'s)?|master(?:\'s)?|associate(?:\'s)?|doctoral|doctorate|degree)(?:\s+degree)?\s+in\s+([a-z][a-z &\/-]{2,80}?)(?=\s+(?:required|preferred)\b|[.,;]|$)/i', $jobText, $matches) !== 1) {
            return null;
        }

        return trim($matches[1]);
    }

    private function fieldOfStudyMatches(string $resumeField, string $requiredField): bool
    {
        $resumeField = $this->normalizeField($resumeField);
        $requiredField = $this->normalizeField($requiredField);

        if ($resumeField === '' || $requiredField === '') {
            return false;
        }

        $technologyFields = [
            'computer science',
            'information technology',
            'software engineering',
            'programming',
            'computer engineering',
        ];
        if (in_array($resumeField, $technologyFields, true) && in_array($requiredField, $technologyFields, true)) {
            return true;
        }

        return $resumeField === $requiredField;
    }

    private function normalizeField(string $field): string
    {
        $field = strtolower(trim($field));
        $field = preg_replace('/[^a-z0-9]+/', ' ', $field) ?? $field;

        return trim(preg_replace('/\s+/', ' ', $field) ?? $field);
    }

    private function requiredEducationRank(string $jobText): ?int
    {
        $mandatoryText = preg_replace('/[^.;\n]*(?:preferred|nice to have|a plus)[^.;\n]*/i', '', $jobText) ?? $jobText;

        if (preg_match('/\bhigh school diploma\b[^.;]{0,60}\brequired\b|\brequired\b[^.;]{0,60}\bhigh school diploma\b/i', $mandatoryText) === 1) {
            return 1;
        }
        if (preg_match('/\b(?:associate(?:\'s)?|college diploma)\b[^.;]{0,60}\brequired\b|\brequired\b[^.;]{0,60}\b(?:associate(?:\'s)?|college diploma)\b/i', $mandatoryText) === 1) {
            return 2;
        }
        if (preg_match('/\bbachelor(?:\'s)?\b[^.;]{0,80}\brequired\b|\brequired\b[^.;]{0,80}\bbachelor(?:\'s)?\b|\bbachelor(?:\'s)?\b[^.;]{0,30}\bor\b[^.;]{0,30}\bmaster(?:\'s)?\b/i', $mandatoryText) === 1) {
            return 3;
        }
        if (preg_match('/\bmaster(?:\'s)?\b[^.;]{0,60}\brequired\b|\brequired\b[^.;]{0,60}\bmaster(?:\'s)?\b/i', $mandatoryText) === 1) {
            return 4;
        }
        if (preg_match('/\b(?:phd|doctorate|doctoral)\b[^.;]{0,60}\brequired\b|\brequired\b[^.;]{0,60}\b(?:phd|doctorate|doctoral)\b/i', $mandatoryText) === 1) {
            return 5;
        }

        return null;
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
