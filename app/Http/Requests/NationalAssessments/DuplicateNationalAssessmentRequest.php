<?php

namespace App\Http\Requests\NationalAssessments;

use Illuminate\Foundation\Http\FormRequest;

class DuplicateNationalAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }
}
