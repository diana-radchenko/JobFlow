<?php

namespace App\Ai\Tools;

use App\Models\Resume;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SaveAdditionalInfo implements Tool
{
    public function __construct(public Resume $resume) {}

    public function description(): Stringable|string
    {
        return 'Save additional resume information (languages, certifications, interests, notes) for this resume.';
    }

    public function handle(Request $request): Stringable|string
    {
        $data = array_filter($request->only([
            'languages',
            'certifications',
            'interests',
            'notes',
        ]), fn ($value) => $value !== null);

        if ($this->resume->additionalInformation) {
            $this->resume->additionalInformation->update($data);
        } else {
            $this->resume->additionalInformation()->create(array_merge($data, ['user_id' => $this->resume->user_id]));
        }

        return 'Saved additional information.';
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'languages' => $schema->string()->description('Languages spoken, comma separated.')->nullable()->required(),
            'certifications' => $schema->string()->description('Certifications, comma separated or as free text.')->nullable()->required(),
            'interests' => $schema->string()->description('Hobbies and interests.')->nullable()->required(),
            'notes' => $schema->string()->description('Any other notes to include on the resume.')->nullable()->required(),
        ];
    }
}
