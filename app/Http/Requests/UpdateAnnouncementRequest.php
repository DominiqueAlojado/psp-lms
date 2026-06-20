<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'scope' => ['required', Rule::in(['organization', 'system'])],
            'priority' => ['required', Rule::in(['normal', 'important', 'urgent'])],
            'is_published' => ['boolean'],
            'is_pinned' => ['boolean'],
            'target_year_levels' => ['nullable', 'array'],
            'target_year_levels.*' => ['string'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ];
    }
}
