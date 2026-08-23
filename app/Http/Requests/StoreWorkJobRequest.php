<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkJobRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `technologies` is a non-nullable json column, so an omitted (empty) tag
     * list still has to reach the model as an array.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['technologies' => $this->input('technologies', [])]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'contacts' => 'required|string|max:255',
            'description' => 'required|string',
            'salary_start' => 'nullable|numeric|min:0|max:99999999.99',
            'salary_end' => 'nullable|numeric|min:0|max:99999999.99|gte:salary_start',
            'technologies' => 'array',
            'technologies.*' => 'string|max:255',
            'industry' => ['nullable', Rule::in(config('jobs.industries'))],
            'position_level' => ['nullable', Rule::in(config('jobs.position_levels'))],
            'employment_type' => ['nullable', Rule::in(config('jobs.employment_types'))],
            'workplace_type' => ['nullable', Rule::in(config('jobs.workplace_types'))],
            'responsibilities' => 'nullable|string',
            'requirements' => 'nullable|string',
            'benefits' => 'nullable|string',
            'salary_currency' => 'nullable|string|size:3',
            'salary_period' => ['nullable', Rule::in(['hour', 'month', 'year'])],
            'status' => ['nullable', Rule::in(['draft', 'published', 'closed'])],
        ];
    }
}
