<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLanguageRequest extends FormRequest
{
    public const PROFICIENCIES = ['Native', 'Fluent', 'Professional', 'Intermediate', 'Basic'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'proficiency' => ['required', Rule::in(self::PROFICIENCIES)],
        ];
    }
}

