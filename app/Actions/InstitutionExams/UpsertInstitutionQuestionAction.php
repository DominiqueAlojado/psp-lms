<?php

namespace App\Actions\InstitutionExams;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionQuestion;
use App\Repositories\Contracts\InstitutionQuestionChoiceRepositoryInterface;
use App\Repositories\Contracts\InstitutionQuestionRepositoryInterface;

class UpsertInstitutionQuestionAction
{
    public function __construct(
        private readonly InstitutionQuestionRepositoryInterface $questionRepository,
        private readonly InstitutionQuestionChoiceRepositoryInterface $questionChoiceRepository,
    ) {}

    public function execute(
        InstitutionAssessment $assessment,
        array $payload,
        ?string $imagePath = null,
    ): InstitutionQuestion {
        $questionData = [
            'question_type' => $payload['question_type'],
            'topic_id' => $payload['topic_id'] ?? null,
            'question_text' => $payload['question_text'],
            'points' => $payload['points'],
            'order' => $payload['order'] ?? 0,
        ];

        if ($imagePath) {
            $questionData['image_path'] = $imagePath;
        }

        $question = null;

        if (! empty($payload['id'])) {
            $question = $this->questionRepository->findForAssessment(
                $assessment,
                (int) $payload['id']
            );

            if ($question) {
                $this->questionRepository->update($question, $questionData);
                $this->questionChoiceRepository->deleteForQuestion($question);
            }
        }

        if (! $question) {
            $question = $this->questionRepository->createForAssessment(
                $assessment,
                $questionData
            );
        }

        $this->syncChoices($question, $payload);

        return $question;
    }

    private function syncChoices(InstitutionQuestion $question, array $payload): void
    {
        if (in_array($payload['question_type'], ['multiple_choice', 'multiple_select'])) {
            $this->questionChoiceRepository->createMany(
                $question,
                collect($payload['choices'] ?? [])->map(fn ($choice, $index) => [
                    'choice_text' => $choice['choice_text'],
                    'is_correct' => (bool) ($choice['is_correct'] ?? false),
                    'order' => $index,
                ])->all()
            );
        }

        if ($payload['question_type'] === 'true_false') {
            $answer = filter_var($payload['answer'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $this->questionChoiceRepository->createMany($question, [
                ['choice_text' => 'True', 'is_correct' => $answer === true, 'order' => 0],
                ['choice_text' => 'False', 'is_correct' => $answer === false, 'order' => 1],
            ]);
        }
    }
}
