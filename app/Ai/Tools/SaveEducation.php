<?php

namespace App\Ai\Tools;

use App\Enums\EducationDegree;
use App\Models\Resume;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SaveEducation implements Tool
{
    public function __construct(public Resume $resume) {}

    public function description(): Stringable|string
    {
        return 'Save one education entry to the resume once the candidate has provided the details. Call this once per qualification.';
    }

    public function handle(Request $request): Stringable|string
    {
        $data = $request->all();

        $education = $this->resume->user->educations()->create([
            'degree' => $data['degree'] ?? null,
            'institution' => $data['institution'] ?? null,
            'field_of_study' => $data['field_of_study'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        $this->resume->educations()->attach($education->id, ['order' => $this->resume->educations()->count()]);

        return "Saved education: {$education->degree->value} at {$education->institution}.";
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'degree' => $schema->string()
                ->enum(array_column(EducationDegree::cases(), 'value'))
                ->description('The degree or qualification level.')
                ->required(),
            'institution' => $schema->string()->description('School, college, or university name.')->required(),
            'field_of_study' => $schema->string()->description('Field of study or major.')->nullable()->required(),
            'start_date' => $schema->string()->description('Start date in YYYY-MM-DD format.')->nullable()->required(),
            'end_date' => $schema->string()->description('End (or expected) date in YYYY-MM-DD format.')->nullable()->required(),
            'description' => $schema->string()->description('Notable achievements, honors, or relevant coursework.')->nullable()->required(),
        ];
    }
}
