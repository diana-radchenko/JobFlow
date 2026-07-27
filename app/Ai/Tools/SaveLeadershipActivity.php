<?php

namespace App\Ai\Tools;

use App\Models\Resume;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SaveLeadershipActivity implements Tool
{
    public function __construct(public Resume $resume) {}

    public function description(): Stringable|string
    {
        return 'Save one leadership role or extracurricular activity to the resume. Call this once per organization.';
    }

    public function handle(Request $request): Stringable|string
    {
        $data = $request->all();

        $leadershipActivity = $this->resume->user->leadershipActivities()->create([
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

        $this->resume->leadershipActivities()->attach($leadershipActivity->id, ['order' => $this->resume->leadershipActivities()->count()]);

        return "Saved leadership activity: {$leadershipActivity->role} at {$leadershipActivity->organization}.";
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'organization' => $schema->string()->description('The club, society, student body, team, or organization.')->required(),
            'role' => $schema->string()->description('The leadership role or position held, e.g. President, Team Captain.')->required(),
            'description' => $schema->string()->description('What the role involved and the impact/outcome.')->nullable()->required(),
            'url' => $schema->string()->description('A URL to the organization or activity, if any.')->nullable()->required(),
            'city' => $schema->string()->description('City where the activity took place.')->nullable()->required(),
            'country' => $schema->string()->description('Country where the activity took place.')->nullable()->required(),
            'start_date' => $schema->string()->description('Start date in YYYY-MM-DD format.')->required(),
            'end_date' => $schema->string()->description('End date in YYYY-MM-DD format. Omit if still active.')->nullable()->required(),
            'is_current' => $schema->boolean()->description('Whether the candidate still holds this role.')->nullable()->required(),
        ];
    }
}
