<?php

namespace App\Ai\Tools;

use App\Models\Resume;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SavePersonalInfo implements Tool
{
    public function __construct(public Resume $resume) {}

    public function description(): Stringable|string
    {
        return 'Save the candidate\'s personal / contact information. This is shared across all of their resumes. Call once all provided fields are collected.';
    }

    public function handle(Request $request): Stringable|string
    {
        $data = array_filter($request->only([
            'first_name',
            'last_name',
            'middle_name',
            'date_of_birth',
            'phone',
            'linkedin_url',
            'city',
            'country',
        ]), fn ($value) => $value !== null);

        $user = $this->resume->user;

        if ($user->profile) {
            $user->profile->update($data);
        } else {
            $user->profile()->create($data);
        }

        return 'Saved personal information.';
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'first_name' => $schema->string()->description('First name.')->required(),
            'last_name' => $schema->string()->description('Last name.')->required(),
            'middle_name' => $schema->string()->description('Middle name, if any.')->nullable()->required(),
            'date_of_birth' => $schema->string()->description('Date of birth in YYYY-MM-DD format.')->nullable()->required(),
            'phone' => $schema->string()->description('Contact phone number.')->nullable()->required(),
            'linkedin_url' => $schema->string()->description('Full LinkedIn profile URL.')->nullable()->required(),
            'city' => $schema->string()->description('City of residence.')->nullable()->required(),
            'country' => $schema->string()->description('Country of residence.')->nullable()->required(),
        ];
    }
}
