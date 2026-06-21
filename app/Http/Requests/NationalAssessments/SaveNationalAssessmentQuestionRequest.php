<?php

namespace App\Http\Requests\NationalAssessments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveNationalAssessmentQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['nullable', 'integer', 'exists:national_questions,id'],
            'question_type' => ['required', Rule::in(['multiple_choice', 'multiple_select', 'true_false'])],
            'question_text' => ['required', 'string'],
            'points' => ['required', 'integer', 'min:1'],
            'topic' => ['nullable', 'string', 'max:255'],
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
            'choices' => ['nullable', 'array'],
            'choices.*.id' => ['nullable', 'integer', 'exists:national_question_choices,id'],
            'choices.*.choice_text' => ['required_with:choices', 'string'],
            'choices.*.is_correct' => ['required_with:choices', 'boolean'],
            'answer' => ['nullable', 'boolean'],
            'image' => ['nullable', 'string'],
        ];
    }
}
