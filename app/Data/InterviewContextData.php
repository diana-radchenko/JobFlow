<?php

namespace App\Data;

use App\Models\User;

class InterviewContextData
{
    /**
     * @param  array<int, array<string, mixed>>  $workExperiences
     * @param  array<int, array<string, mixed>>  $educations
     * @param  array<int, array<string, mixed>>  $skills
     * @param  array<int, array<string, mixed>>  $projects
     * @param  array<int, array<string, mixed>>  $volunteerExperiences
     * @param  array<int, array<string, mixed>>  $leadershipActivities
     * @param  array<string, mixed>|null  $additionalInfo
     * @param  array<string, mixed>|null  $job
     */
    public function __construct(
        public array $workExperiences,
        public array $educations,
        public array $skills,
        public array $projects,
        public array $volunteerExperiences,
        public array $leadershipActivities,
        public ?array $additionalInfo,
        public ?array $job,
    ) {}

    public static function fromUser(User $user): self
    {
        $latestApplication = $user->applications()->with('workJob')->latest()->first();
        $job = $latestApplication?->workJob;

        return new self(
            workExperiences: $user->workExperiences()
                ->orderBy('start_date', 'desc')
                ->get()
                ->map->only([
                    'company_name',
                    'job_title',
                    'city',
                    'country',
                    'is_remote',
                    'start_date',
                    'end_date',
                    'is_current',
                    'description',
                ])
                ->all(),
            educations: $user->educations()
                ->orderBy('start_date', 'desc')
                ->get()
                ->map->only([
                    'degree',
                    'institution',
                    'field_of_study',
                    'start_date',
                    'end_date',
                    'description',
                ])
                ->all(),
            skills: $user->skills()
                ->get()
                ->map->only([
                    'name',
                    'proficiency_level',
                ])
                ->all(),
            projects: $user->projects()
                ->get()
                ->map->only([
                    'title',
                    'type',
                    'description',
                    'url',
                    'start_date',
                    'end_date',
                ])
                ->all(),
            volunteerExperiences: $user->volunteerExperiences()
                ->orderBy('start_date', 'desc')
                ->get()
                ->map->only([
                    'organization',
                    'role',
                    'description',
                    'url',
                    'city',
                    'country',
                    'start_date',
                    'end_date',
                    'is_current',
                ])
                ->all(),
            leadershipActivities: $user->leadershipActivities()
                ->orderBy('start_date', 'desc')
                ->get()
                ->map->only([
                    'organization',
                    'role',
                    'description',
                    'url',
                    'city',
                    'country',
                    'start_date',
                    'end_date',
                    'is_current',
                ])
                ->all(),
            additionalInfo: $user->additionalInformation?->only([
                'languages',
                'certifications',
                'interests',
                'notes',
            ]),
            job: $job?->only([
                'title',
                'company',
                'description',
                'location',
                'technologies',
                'salary_start',
                'salary_end',
            ]),
        );
    }

    public function resumeContext(): string
    {
        return json_encode([
            'work_experiences' => $this->workExperiences,
            'educations' => $this->educations,
            'skills' => $this->skills,
            'projects' => $this->projects,
            'volunteer_experiences' => $this->volunteerExperiences,
            'leadership_activities' => $this->leadershipActivities,
            'additional_info' => $this->additionalInfo,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    public function jobContext(): string
    {
        if ($this->job === null) {
            return 'No job details provided.';
        }

        return json_encode($this->job, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }
}
