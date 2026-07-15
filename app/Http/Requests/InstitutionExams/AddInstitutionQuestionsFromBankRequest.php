<?php

namespace App\Http\Requests\InstitutionExams;

use App\Models\Institution\InstitutionAssessment;
use App\Models\QuestionBank;
use Illuminate\Foundation\Http\FormRequest;

class AddInstitutionQuestionsFromBankRequest extends FormRequest
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
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) use ($assessment) {
                    $query = QuestionBank::query()->whereKey($value);

                    if ($assessment->organization?->type === 'national') {
                        $query->where('owner_type', 'national');
                    } else {
                        $query->where('owner_type', 'institution')
                            ->where('organization_id', $assessment->organization_id);
                    }

                    if (! $query->exists()) {
                        $fail('One or more selected question bank items are invalid for this assessment.');
                    }
                },
            ],
        ];
    }
}
