<?php

namespace App\Http\Requests\NationalAssessments;

use App\Models\National\NationalAssessment;
use App\Models\National\NationalQuestion;
use App\Models\National\NationalQuestionChoice;
use App\Models\Topic;
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
        /** @var NationalAssessment $assessment */
        $assessment = $this->route('assessment');
        $currentOrganizationId = $this->user()?->current_organization_id;

        return [
            'id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) use ($assessment) {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $exists = NationalQuestion::query()
                        ->where('assessment_id', $assessment->id)
                        ->whereKey($value)
                        ->exists();

                    if (! $exists) {
                        $fail('The selected question is invalid for this assessment.');
                    }
                },
            ],
            'question_type' => ['required', Rule::in(['multiple_choice', 'multiple_select', 'true_false'])],
            'question_text' => ['required', 'string'],
            'points' => ['required', 'integer', 'min:1'],
            'topic' => ['nullable', 'string', 'max:255'],
            'topic_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) use ($currentOrganizationId) {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $exists = Topic::query()
                        ->whereKey($value)
                        ->where(function ($query) use ($currentOrganizationId) {
                            $query->where('is_global', true);

                            if ($currentOrganizationId !== null) {
                                $query->orWhere('organization_id', $currentOrganizationId);
                            }
                        })
                        ->exists();

                    if (! $exists) {
                        $fail('The selected topic is invalid for this national assessment.');
                    }
                },
            ],
            'choices' => ['nullable', 'array'],
            'choices.*.id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) use ($assessment) {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $exists = NationalQuestionChoice::query()
                        ->whereKey($value)
                        ->whereHas('question', function ($query) use ($assessment) {
                            $query->where('assessment_id', $assessment->id);
                        })
                        ->exists();

                    if (! $exists) {
                        $fail('One or more selected choices are invalid for this assessment.');
                    }
                },
            ],
            'choices.*.choice_text' => ['required_with:choices', 'string'],
            'choices.*.is_correct' => ['required_with:choices', 'boolean'],
            'answer' => ['nullable', 'boolean'],
            'image' => ['nullable', 'string'],
        ];
    }
}
