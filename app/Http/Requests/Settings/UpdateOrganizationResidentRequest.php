<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationResidentRequest extends FormRequest
{
    private const YEAR_LEVELS = [
        'Pre-Resident',
        'First Year',
        'Second Year',
        'Third Year',
        'Fourth Year',
        'Graduate',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                Rule::unique('residents', 'email')->ignore($this->route('resident')),
            ],
            'contact_number' => ['required', 'string', 'regex:/^(\+63|0)?9\d{9}$/'],
            'course' => ['required', 'string', 'max:255'],
            'year_level' => ['required', 'string', Rule::in(self::YEAR_LEVELS)],
            'status' => ['required', 'string', 'in:active,inactive'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.email' => 'Please enter a valid email address.',
            'contact_number.regex' => 'Contact number must be a valid Philippine mobile number (e.g., 09123456789 or +639123456789).',
        ];
    }
}
