<?php

namespace App\Http\Requests\NationalAssessments;

use App\Models\QuestionBank;
use Illuminate\Foundation\Http\FormRequest;

class AddNationalQuestionsFromBankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $exists = QuestionBank::query()
                        ->whereKey($value)
                        ->where('owner_type', 'national')
                        ->exists();

                    if (! $exists) {
                        $fail('One or more selected question bank items are invalid for this assessment.');
                    }
                },
            ],
        ];
    }
}
