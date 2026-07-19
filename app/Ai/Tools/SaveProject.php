<?php

namespace App\Ai\Tools;

use App\Enums\ProjectType;
use App\Models\Resume;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SaveProject implements Tool
{
    public function __construct(public Resume $resume) {}

    public function description(): Stringable|string
    {
        return 'Save one project or achievement to the resume. Call this once per project.';
    }

    public function handle(Request $request): Stringable|string
    {
        $data = $request->all();

        $project = $this->resume->user->projects()->create([
            'title' => $data['title'] ?? null,
            'type' => $data['type'] ?? ProjectType::Project->value,
            'description' => $data['description'] ?? null,
            'url' => $data['url'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ]);

        $this->resume->projects()->attach($project->id, ['order' => $this->resume->projects()->count()]);

        return "Saved project: {$project->title}.";
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('Project or achievement title.')->required(),
            'type' => $schema->string()
                ->enum(array_column(ProjectType::cases(), 'value'))
                ->description('Whether this is a project or an achievement.')
                ->nullable()
                ->required(),
            'description' => $schema->string()->description('What the project involved and the impact/outcome.')->nullable()->required(),
            'url' => $schema->string()->description('A URL to the project, if any.')->nullable()->required(),
            'start_date' => $schema->string()->description('Start date in YYYY-MM-DD format.')->nullable()->required(),
            'end_date' => $schema->string()->description('End date in YYYY-MM-DD format.')->nullable()->required(),
        ];
    }
}
