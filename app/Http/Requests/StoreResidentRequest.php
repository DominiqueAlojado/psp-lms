<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResidentRequest extends FormRequest
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
            'organization_id' => ['required', 'exists:organizations,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:residents,email'],
            'contact_number' => ['required', 'string', 'regex:/^(\+63|0)?9\d{9}$/'],
            'course' => ['required', 'string', 'max:255'],
            'year_level' => ['required', 'string', Rule::in(self::YEAR_LEVELS)],
            'status' => ['required', 'string', 'in:active,inactive'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'organization_id.required' => 'Organization is required',
            'first_name.required' => 'First name is required',
            'last_name.required' => 'Last name is required',
            'email.email' => 'Please enter a valid email address.',
            'email.required' => 'Please enter a valid email address.',
            'contact_number.required' => 'Contact number is required',
            'contact_number.regex' => 'Contact number must be a valid Philippine mobile number (e.g., 09123456789 or +639123456789).',
            'course.required' => 'Course is required',
            'year_level.required' => 'Year level is required',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => "Passwords don't match",
        ];
    }
}
