<?php

namespace App\Services;

use App\Actions\NationalAssessments\AddNationalQuestionsFromBankAction;
use App\Actions\NationalAssessments\BuildNationalQuestionChoicesAction;
use App\Actions\NationalAssessments\SaveNationalQuestionAction;
use App\Actions\NationalAssessments\StoreNationalQuestionsAction;
use App\Actions\NationalAssessments\SyncNationalQuestionToBankAction;
use App\Models\National\NationalAssessment;
use App\Models\National\NationalQuestion;
use App\Models\User;
use App\Repositories\Contracts\NationalAssessmentRepositoryInterface;
use App\Repositories\Contracts\NationalQuestionRepositoryInterface;
use App\Repositories\Contracts\QuestionBankRepositoryInterface;
use App\Services\ActivityLog\NationalAssessmentActivityLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NationalAssessmentQuestionService
{
    public function __construct(
        private readonly NationalAssessmentRepositoryInterface $assessmentRepository,
        private readonly NationalQuestionRepositoryInterface $questionRepository,
        private readonly QuestionBankRepositoryInterface $questionBankRepository,
        private readonly NationalAssessmentActivityLogService $activityLogService,
        private readonly StoreNationalQuestionsAction $storeQuestionsAction,
        private readonly AddNationalQuestionsFromBankAction $addFromBankAction,
        private readonly SaveNationalQuestionAction $saveQuestionAction,
        private readonly BuildNationalQuestionChoicesAction $buildChoicesAction,
        private readonly SyncNationalQuestionToBankAction $syncQuestionToBankAction,
    ) {}

    public function storeQuestions(NationalAssessment $assessment, array $questions): void
    {
        $totalPointsAdded = DB::transaction(function () use ($assessment, $questions) {
            $totalPointsAdded = $this->storeQuestionsAction->execute($assessment, $questions);

            $this->assessmentRepository->update($assessment, [
                'total_points' => $assessment->total_points + $totalPointsAdded,
            ]);

            return $totalPointsAdded;
        });

        $assessment->refresh();
        $this->activityLogService->logQuestionsAdded($assessment, count($questions), $totalPointsAdded);
    }

    public function addFromBank(NationalAssessment $assessment, array $questionIds): array
    {
        $result = DB::transaction(function () use ($assessment, $questionIds) {
            $result = $this->addFromBankAction->execute($assessment, $questionIds);

            $this->assessmentRepository->update($assessment, [
                'total_points' => $assessment->total_points + $result['total_points_added'],
            ]);

            return $result;
        });

        return [
            'added_count' => $result['added_count'],
            'skipped_count' => $result['skipped_count'],
        ];
    }

    public function saveQuestion(NationalAssessment $assessment, array $validated, User $user): NationalQuestion
    {
        $saved = $this->saveQuestionAction->execute($assessment, $validated);
        /** @var NationalQuestion $question */
        $question = $saved['question'];
        $choicesData = $this->buildChoicesAction->execute($question, $validated);
        $existsInBank = $this->questionBankRepository->existsForNationalCreator($question->question_text, $user->id);

        if ($saved['is_new'] || ! $existsInBank) {
            try {
                $this->syncQuestionToBankAction->execute(
                    $question,
                    $choicesData,
                    $saved['image_path'],
                    $saved['topic_id'],
                    $question->topic,
                    $user
                );
            } catch (\Throwable $exception) {
                Log::error('Failed to save question to question bank', [
                    'error' => $exception->getMessage(),
                    'question_id' => $question->id,
                ]);
            }
        }

        $this->assessmentRepository->update($assessment, [
            'total_points' => $this->assessmentRepository->sumQuestionPoints($assessment),
        ]);

        if ($saved['is_new']) {
            $this->activityLogService->logQuestionAdded(
                $assessment,
                $question->id,
                $question->question_text,
                $question->question_type,
                $question->points,
                $question->topic,
                $choicesData
            );
        } elseif ($saved['old']['question_text'] !== null) {
            $this->activityLogService->logQuestionUpdated(
                $assessment,
                $question->id,
                $question->question_text,
                $question->question_type,
                $question->points,
                $question->topic,
                $choicesData,
                $saved['old']['question_text'],
                $saved['old']['question_type'],
                $saved['old']['points'],
                $saved['old']['topic'],
                $saved['old']['choices']
            );
        }

        return $question;
    }

    public function deleteQuestion(NationalAssessment $assessment, NationalQuestion $question): void
    {
        $this->questionRepository->delete($question);

        $this->assessmentRepository->update($assessment, [
            'total_points' => $this->assessmentRepository->sumQuestionPoints($assessment),
        ]);
    }
}
