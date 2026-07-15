<?php

namespace App\Http\Requests\InstitutionExams;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionQuestion;
use App\Models\Topic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveInstitutionAssessmentQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var InstitutionAssessment $assessment */
        $assessment = $this->route('assessment');

        return [
            'id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) use ($assessment) {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $exists = InstitutionQuestion::query()
                        ->where('assessment_id', $assessment->id)
                        ->whereKey($value)
                        ->exists();

                    if (! $exists) {
                        $fail('The selected question is invalid for this assessment.');
                    }
                },
            ],
            'topic_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) use ($assessment) {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $exists = Topic::query()
                        ->whereKey($value)
                        ->where(function ($query) use ($assessment) {
                            $query->where('organization_id', $assessment->organization_id)
                                ->orWhere('is_global', true);
                        })
                        ->exists();

                    if (! $exists) {
                        $fail('The selected topic is invalid for this assessment.');
                    }
                },
            ],
            'question_type' => ['required', Rule::in(['multiple_choice', 'multiple_select', 'true_false'])],
            'question_text' => ['required', 'string'],
            'points' => ['required', 'integer', 'min:1'],
            'order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'string'],
            'choices' => ['nullable', 'array'],
            'choices.*.choice_text' => ['required_with:choices', 'string'],
            'choices.*.is_correct' => ['required_with:choices', 'boolean'],
            'answer' => ['nullable'],
        ];
    }
}
