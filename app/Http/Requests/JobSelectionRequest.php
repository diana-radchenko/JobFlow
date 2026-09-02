<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JobSelectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'location' => ['nullable', 'string', 'max:255'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'min:0', 'gte:salary_min'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', Rule::in(config('jobs.industries'))],
            'position_level' => ['nullable', Rule::in(config('jobs.position_levels'))],
            'company' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', Rule::in(config('jobs.employment_types'))],
            'workplace_type' => ['nullable', Rule::in(config('jobs.workplace_types'))],
            'date_posted' => ['nullable', Rule::in(['1', '7', '30'])],
            'view' => ['nullable', Rule::in(['all', 'saved', 'applied'])],
            'sort' => ['nullable', Rule::in(['newest', 'salary_high', 'salary_low'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $payload = collect($this->all())
            ->map(function (mixed $value) {
                if (is_string($value)) {
                    $trimmed = trim($value);

                    return $trimmed === '' ? null : $trimmed;
                }

                return $value;
            })
            ->toArray();

        $this->replace($payload);
    }
}
