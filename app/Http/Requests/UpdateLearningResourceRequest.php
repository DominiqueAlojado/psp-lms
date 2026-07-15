<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLearningResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'max:255'],
            'scope' => ['required', Rule::in(['organization', 'system'])],
            'target_year_levels' => ['nullable', 'array'],
            'target_year_levels.*' => ['string'],
            'is_published' => ['boolean'],
        ];
    }
}
