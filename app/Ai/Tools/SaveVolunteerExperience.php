<?php

namespace App\Ai\Tools;

use App\Models\Resume;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SaveVolunteerExperience implements Tool
{
    public function __construct(public Resume $resume) {}

    public function description(): Stringable|string
    {
        return 'Save one volunteer or community involvement entry to the resume. Call this once per organization.';
    }

    public function handle(Request $request): Stringable|string
    {
        $data = $request->all();

        $volunteerExperience = $this->resume->user->volunteerExperiences()->create([
            'organization' => $data['organization'] ?? null,
            'role' => $data['role'] ?? null,
            'description' => $data['description'] ?? null,
            'url' => $data['url'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'is_current' => $data['is_current'] ?? false,
        ]);

        $this->resume->volunteerExperiences()->attach($volunteerExperience->id, ['order' => $this->resume->volunteerExperiences()->count()]);

        return "Saved volunteer experience: {$volunteerExperience->role} at {$volunteerExperience->organization}.";
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'organization' => $schema->string()->description('The organization, charity, or community group.')->required(),
            'role' => $schema->string()->description('The volunteer role or position held.')->required(),
            'description' => $schema->string()->description('What the volunteering involved and the impact/outcome.')->nullable()->required(),
            'url' => $schema->string()->description("A URL to the organization or the candidate's work there, if any.")->nullable()->required(),
            'city' => $schema->string()->description('City where the volunteering took place.')->nullable()->required(),
            'country' => $schema->string()->description('Country where the volunteering took place.')->nullable()->required(),
            'start_date' => $schema->string()->description('Start date in YYYY-MM-DD format.')->required(),
            'end_date' => $schema->string()->description('End date in YYYY-MM-DD format. Omit if still active.')->nullable()->required(),
            'is_current' => $schema->boolean()->description('Whether the candidate is still volunteering here.')->nullable()->required(),
        ];
    }
}
