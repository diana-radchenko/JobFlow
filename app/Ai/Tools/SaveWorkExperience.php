<?php

namespace App\Ai\Tools;

use App\Models\Resume;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SaveWorkExperience implements Tool
{
    public function __construct(public Resume $resume) {}

    public function description(): Stringable|string
    {
        return 'Save one work experience entry to the resume once the candidate has provided the details. Call this once per job.';
    }

    public function handle(Request $request): Stringable|string
    {
        $data = $request->all();

        $experience = $this->resume->user->workExperiences()->create([
            'company_name' => $data['company_name'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'is_remote' => (bool) ($data['is_remote'] ?? false),
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'is_current' => (bool) ($data['is_current'] ?? false),
            'description' => $data['description'] ?? null,
        ]);

        $this->resume->workExperiences()->attach($experience->id, ['order' => $this->resume->workExperiences()->count()]);

        return "Saved work experience: {$experience->job_title} at {$experience->company_name}.";
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'company_name' => $schema->string()->description('Employer / company name.')->required(),
            'job_title' => $schema->string()->description('The role or job title held.')->required(),
            'city' => $schema->string()->description('City where the job was located.')->nullable()->required(),
            'country' => $schema->string()->description('Country where the job was located.')->nullable()->required(),
            'is_remote' => $schema->boolean()->description('Whether the role was remote.')->nullable()->required(),
            'start_date' => $schema->string()->description('Start date in YYYY-MM-DD format.')->required(),
            'end_date' => $schema->string()->description('End date in YYYY-MM-DD format. Null if this is the current job.')->nullable()->required(),
            'is_current' => $schema->boolean()->description('Whether this is the candidate\'s current job.')->nullable()->required(),
            'description' => $schema->string()->description('Responsibilities and achievements in this role.')->nullable()->required(),
        ];
    }
}
