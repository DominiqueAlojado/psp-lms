<?php

namespace App\Http\Requests\InstitutionExams;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteInstitutionQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['required', 'integer'],
        ];
    }
}
