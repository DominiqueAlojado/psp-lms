<?php

namespace App\Http\Requests\NationalAssessments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNationalAssessmentQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question_type' => ['required', Rule::in(['multiple_choice', 'multiple_select', 'true_false'])],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.points' => ['required', 'integer', 'min:1'],
            'questions.*.difficulty_level' => ['nullable', Rule::in(['easy', 'medium', 'hard'])],
            'questions.*.topic' => ['nullable', 'string', 'max:255'],
            'questions.*.choices' => ['nullable', 'array'],
            'questions.*.choices.*.choice_text' => ['required_with:questions.*.choices', 'string'],
            'questions.*.choices.*.is_correct' => ['required_with:questions.*.choices', 'boolean'],
            'questions.*.answer' => ['nullable', 'boolean'],
        ];
    }
}
