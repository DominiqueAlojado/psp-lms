<?php

namespace App\Http\Requests\InstitutionExams;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionQuestion;
use App\Models\Topic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInstitutionAssessmentQuestionsRequest extends FormRequest
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
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.id' => [
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
                        $fail('One or more selected questions are invalid for this assessment.');
                    }
                },
            ],
            'questions.*.topic_id' => [
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
                        $fail('One or more selected topics are invalid for this assessment.');
                    }
                },
            ],
            'questions.*.question_type' => ['required', Rule::in(['multiple_choice', 'multiple_select', 'true_false'])],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.points' => ['required', 'integer', 'min:1'],
            'questions.*.order' => ['nullable', 'integer', 'min:0'],
            'questions.*.image' => ['nullable', 'string'],
            'questions.*.choices' => ['nullable', 'array'],
            'questions.*.choices.*.choice_text' => ['required_with:questions.*.choices', 'string'],
            'questions.*.choices.*.is_correct' => ['required_with:questions.*.choices', 'boolean'],
            'questions.*.answer' => ['nullable'],
        ];
    }
}
