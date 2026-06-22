<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssignmentSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $assignment = $this->route('assignment');

        return [
            'submission_text' => ['nullable', 'string'],
            'files' => ['required', 'array', 'max:' . $assignment->max_files],
            'files.*' => ['file', 'max:' . ($assignment->max_file_size_mb * 1024)],
        ];
    }
}
