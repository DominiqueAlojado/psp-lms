<?php

namespace App\Actions\NationalAssessments;

use App\Models\National\NationalAssessment;
use App\Repositories\Contracts\NationalQuestionChoiceRepositoryInterface;
use App\Repositories\Contracts\NationalQuestionRepositoryInterface;
use App\Repositories\Contracts\QuestionBankRepositoryInterface;
use App\Services\ActivityLog\NationalAssessmentActivityLogService;

class AddNationalQuestionsFromBankAction
{
    public function __construct(
        private readonly NationalQuestionRepositoryInterface $questionRepository,
        private readonly NationalQuestionChoiceRepositoryInterface $questionChoiceRepository,
        private readonly QuestionBankRepositoryInterface $questionBankRepository,
        private readonly NationalAssessmentActivityLogService $activityLogService,
    ) {}

    public function execute(NationalAssessment $assessment, array $questionIds): array
    {
        $bankQuestions = $this->questionBankRepository->findByIdsForOwnerType($questionIds, 'national');

        if ($bankQuestions->isEmpty()) {
            return ['added_count' => 0, 'skipped_count' => 0, 'total_points_added' => 0];
        }

        $existingSignatures = $assessment->questions()
            ->get(['question_text', 'question_type'])
            ->map(fn ($question) => $this->buildQuestionSignature($question->question_text, $question->question_type));

        $order = $this->questionRepository->getNextOrderForAssessment($assessment);
        $addedCount = 0;
        $skippedCount = 0;
        $totalPointsAdded = 0;

        foreach ($bankQuestions as $bankQuestion) {
            $signature = $this->buildQuestionSignature($bankQuestion->question_text, $bankQuestion->question_type);

            if ($existingSignatures->contains($signature)) {
                $skippedCount++;
                continue;
            }

            $question = $this->questionRepository->createForAssessment($assessment, [
                'question_type' => $bankQuestion->question_type,
                'question_text' => $bankQuestion->question_text,
                'points' => $bankQuestion->points,
                'topic' => $bankQuestion->topic?->name,
                'order' => $order++,
                'image_path' => $bankQuestion->image_path,
            ]);

            $totalPointsAdded += (int) $bankQuestion->points;

            $this->questionChoiceRepository->createMany(
                $question,
                $bankQuestion->choices->map(fn ($choice) => [
                    'choice_text' => $choice->choice_text,
                    'is_correct' => $choice->is_correct,
                    'order' => $choice->order,
                ])->all()
            );

            $this->questionBankRepository->incrementUsage($bankQuestion);
            $existingSignatures->push($signature);
            $addedCount++;

            $question->load('choices');
            $this->activityLogService->logQuestionAddedFromBank(
                $assessment,
                $question->id,
                $question->question_text,
                $question->question_type,
                $question->points,
                $question->topic,
                $question->choices->map(fn ($choice) => [
                    'choice_text' => $choice->choice_text,
                    'is_correct' => $choice->is_correct,
                ])->toArray(),
                $bankQuestion->id
            );
        }

        return [
            'added_count' => $addedCount,
            'skipped_count' => $skippedCount,
            'total_points_added' => $totalPointsAdded,
        ];
    }

    private function buildQuestionSignature(string $questionText, string $questionType): string
    {
        $normalizedText = preg_replace('/\s+/u', ' ', trim(strip_tags($questionText))) ?? '';

        return mb_strtolower($questionType . '|' . $normalizedText);
    }
}
