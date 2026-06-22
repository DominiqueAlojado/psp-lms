<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GradeAssignmentSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $submission = $this->route('submission');

        return [
            'score' => ['required', 'numeric', 'min:0', 'max:' . $submission->max_score],
            'grader_feedback' => ['nullable', 'string'],
        ];
    }
}
