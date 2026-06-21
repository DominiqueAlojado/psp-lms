<?php

namespace App\Actions\NationalAssessments;

use App\Models\National\NationalAssessment;
use App\Repositories\Contracts\NationalQuestionRepositoryInterface;

class StoreNationalQuestionsAction
{
    public function __construct(
        private readonly NationalQuestionRepositoryInterface $questionRepository,
        private readonly BuildNationalQuestionChoicesAction $buildChoicesAction,
    ) {}

    public function execute(NationalAssessment $assessment, array $questions): int
    {
        $order = $this->questionRepository->getNextOrderForAssessment($assessment);
        $totalPointsAdded = 0;

        foreach ($questions as $questionData) {
            $question = $this->questionRepository->createForAssessment($assessment, [
                'question_type' => $questionData['question_type'],
                'question_text' => $questionData['question_text'],
                'points' => $questionData['points'],
                'difficulty_level' => $questionData['difficulty_level'] ?? null,
                'topic' => $questionData['topic'] ?? null,
                'order' => $order++,
            ]);

            $totalPointsAdded += (int) $questionData['points'];
            $this->buildChoicesAction->execute($question, $questionData);
        }

        return $totalPointsAdded;
    }
}
