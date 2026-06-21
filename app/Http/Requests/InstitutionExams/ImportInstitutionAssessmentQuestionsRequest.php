<?php

namespace App\Http\Requests\InstitutionExams;

use Illuminate\Foundation\Http\FormRequest;

class ImportInstitutionAssessmentQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ];
    }
}
