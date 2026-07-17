<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('organizations', 'name')->ignore($this->route('organization')->id),
            ],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', Rule::in(['chapter', 'institution', 'national'])],
            'is_active' => ['boolean'],
            'training_officers' => ['nullable', 'json'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Institution name is required',
            'name.unique' => 'An institution with this name already exists',
            'type.required' => 'Institution type is required',
            'type.in' => 'Please select a valid institution type',
            'training_officers.json' => 'Invalid training officers data',
        ];
    }
}
