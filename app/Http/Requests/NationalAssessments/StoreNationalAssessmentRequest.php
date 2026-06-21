<?php

namespace App\Http\Requests\NationalAssessments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNationalAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'exam_year' => ['required', 'integer', 'min:2000', 'max:3000'],
            'exam_period' => ['required', 'string', 'max:100'],
            'category' => ['required', Rule::in([
                'anatomic-pathology-theoretical',
                'anatomic-pathology-projection',
                'clinical-pathology-theoretical',
                'clinical-pathology-projection',
            ])],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'passing_score' => ['required', 'integer', 'min:0'],
            'randomize_questions' => ['boolean'],
            'randomize_choices' => ['boolean'],
            'show_results_immediately' => ['boolean'],
            'allow_review' => ['boolean'],
            'is_published' => ['boolean'],
            'national_ranking_enabled' => ['boolean'],
            'institution_comparison_enabled' => ['boolean'],
            'scheduled_date' => ['nullable', 'date'],
            'results_release_date' => ['nullable', 'date'],
        ];
    }
}
