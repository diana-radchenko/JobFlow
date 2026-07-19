<?php

namespace App\Ai\Tools;

use App\Enums\SkillsLevel;
use App\Models\Resume;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SaveSkill implements Tool
{
    public function __construct(public Resume $resume) {}

    public function description(): Stringable|string
    {
        return 'Save one skill to the resume. Call this once per skill the candidate mentions.';
    }

    public function handle(Request $request): Stringable|string
    {
        $data = $request->all();

        $skill = $this->resume->user->skills()->create([
            'name' => $data['name'] ?? null,
            'proficiency_level' => $data['proficiency_level'] ?? null,
        ]);

        $this->resume->skills()->attach($skill->id, ['order' => $this->resume->skills()->count()]);

        return "Saved skill: {$skill->name}.";
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('The skill name, e.g. "Laravel" or "Project Management".')->required(),
            'proficiency_level' => $schema->string()
                ->enum(array_column(SkillsLevel::cases(), 'value'))
                ->description('Proficiency level for this skill.')
                ->required(),
        ];
    }
}
