<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
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
            'start_date' => ['required', 'date', 'after:now'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'registration_deadline' => ['nullable', 'date', 'before:start_date'],
            'location' => ['nullable', 'string', 'max:255'],
            'virtual_link' => ['nullable', 'url', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_free' => ['boolean'],
            'image' => ['nullable', 'image', 'max:5120', 'mimes:jpeg,jpg,png,gif,webp'],
            'cme_credits' => ['nullable', 'numeric', 'min:0'],
            'target_year_levels' => ['nullable', 'array'],
            'requirements' => ['nullable', 'string'],
            'requires_approval' => ['boolean'],
            'is_published' => ['boolean'],
        ];
    }
}
