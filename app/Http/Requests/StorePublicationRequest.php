<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePublicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'publication_date' => 'nullable|date',
            'url' => 'nullable|url|max:2048',
            'description' => 'nullable|string|max:5000',
        ];
    }
}

