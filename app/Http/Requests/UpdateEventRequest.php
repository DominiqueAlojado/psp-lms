<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'scope' => ['required', Rule::in(['organization', 'system'])],
            'description' => ['nullable', 'string'],
            'event_category' => ['required', 'in:convention,workshop,seminar,cme,conference,symposium,training,other'],
            'event_type' => ['required', 'in:in-person,virtual,hybrid'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'registration_deadline' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'virtual_link' => ['nullable', 'url', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_free' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:5120', 'mimes:jpeg,jpg,png,gif,webp'],
            'cme_credits' => ['nullable', 'numeric', 'min:0'],
            'target_year_levels' => ['nullable', 'array'],
            'requirements' => ['nullable', 'string'],
            'requires_approval' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }
}
