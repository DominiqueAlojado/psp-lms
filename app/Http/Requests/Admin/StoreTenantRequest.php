<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isSystemAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'domain' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*$/i',
                function ($attribute, $value, $fail) {
                    if (\App\Models\Tenant::where('domain', $value)->exists()) {
                        $fail('This domain is already in use.');
                    }
                },
            ],
            'database' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z0-9_]+$/',
                function ($attribute, $value, $fail) {
                    if (\App\Models\Tenant::where('database', $value)->exists()) {
                        $fail('This database name is already in use.');
                    }
                },
            ],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The hospital name is required.',
            'domain.required' => 'The domain is required.',
            'domain.unique' => 'This domain is already in use.',
            'domain.regex' => 'The domain format is invalid. Use format like: hospital.example.com',
            'database.required' => 'The database name is required.',
            'database.unique' => 'This database name is already in use.',
            'database.regex' => 'Database name must contain only lowercase letters, numbers, and underscores.',
        ];
    }
}
