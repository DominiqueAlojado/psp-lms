<?php

namespace App\Actions\NationalAssessments;

use App\Models\National\NationalQuestion;
use App\Repositories\Contracts\NationalQuestionChoiceRepositoryInterface;

class BuildNationalQuestionChoicesAction
{
    public function __construct(
        private readonly NationalQuestionChoiceRepositoryInterface $questionChoiceRepository,
    ) {}

    public function execute(NationalQuestion $question, array $validated): array
    {
        if (in_array($question->question_type, ['multiple_choice', 'multiple_select'])) {
            $choicesData = collect($validated['choices'] ?? [])->map(function ($choice, $index) {
                return [
                    'choice_text' => $choice['choice_text'],
                    'is_correct' => (bool) $choice['is_correct'],
                    'order' => $index + 1,
                ];
            })->all();

            $this->questionChoiceRepository->replaceForQuestion($question, $choicesData);

            return collect($choicesData)->map(fn ($choice) => [
                'choice_text' => $choice['choice_text'],
                'is_correct' => $choice['is_correct'],
            ])->all();
        }

        if ($question->question_type === 'true_false') {
            $answer = (bool) ($validated['answer'] ?? true);
            $choicesData = [
                ['choice_text' => 'True', 'is_correct' => $answer === true, 'order' => 1],
                ['choice_text' => 'False', 'is_correct' => $answer === false, 'order' => 2],
            ];

            $this->questionChoiceRepository->replaceForQuestion($question, $choicesData);

            return [
                ['choice_text' => 'True', 'is_correct' => $answer === true],
                ['choice_text' => 'False', 'is_correct' => $answer === false],
            ];
        }

        $this->questionChoiceRepository->deleteForQuestion($question);

        return [];
    }
}
