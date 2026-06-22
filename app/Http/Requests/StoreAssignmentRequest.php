<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'instructions' => ['required', 'string'],
            'assignment_type' => ['required', 'string', 'in:case_report,procedure_log,journal_review,presentation,research_paper,reflection,other'],
            'target_year_levels' => ['required', 'array', 'min:1'],
            'target_year_levels.*' => ['string'],
            'max_score' => ['required', 'integer', 'min:1'],
            'due_date' => ['required', 'date'],
            'allow_late_submission' => ['required', 'boolean'],
            'late_submission_until' => ['nullable', 'date', 'after:due_date', 'required_if:allow_late_submission,true'],
            'late_penalty_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'allow_resubmission' => ['required', 'boolean'],
            'max_submissions' => ['required', 'integer', 'min:1', 'max:10'],
            'allowed_file_types' => ['required', 'array', 'min:1'],
            'allowed_file_types.*' => ['string'],
            'max_file_size_mb' => ['required', 'integer', 'min:1', 'max:100'],
            'max_files' => ['required', 'integer', 'min:1', 'max:20'],
            'is_published' => ['required', 'boolean'],
        ];
    }
}
