<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'overall_rating' => ['required', 'integer', 'between:1,4'],
            'content_rating' => ['required', 'integer', 'between:1,4'],
            'support_rating' => ['required', 'integer', 'between:1,4'],
            'usability_rating' => ['required', 'integer', 'between:1,4'],
            'context' => ['nullable', 'string', 'max:255'],
            'module_name' => ['nullable', 'string', 'max:255'],
            'page_url' => ['nullable', 'string', 'max:255'],
            'comment' => ['required', 'string', 'max:3000'],
            'would_recommend' => ['nullable', 'boolean'],
        ];
    }
}
