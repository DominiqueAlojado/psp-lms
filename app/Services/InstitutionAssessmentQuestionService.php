<?php

namespace App\Services;

use App\Actions\InstitutionExams\AddInstitutionQuestionsFromBankAction;
use App\Actions\InstitutionExams\DeleteInstitutionQuestionsAction;
use App\Actions\InstitutionExams\StoreQuestionImageAction;
use App\Actions\InstitutionExams\SyncInstitutionQuestionToBankAction;
use App\Actions\InstitutionExams\UpsertInstitutionQuestionAction;
use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionQuestion;
use App\Models\User;
use App\Repositories\Contracts\InstitutionAssessmentRepositoryInterface;
use App\Repositories\Contracts\InstitutionQuestionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InstitutionAssessmentQuestionService
{
    public function __construct(
        private readonly InstitutionAssessmentRepositoryInterface $assessmentRepository,
        private readonly InstitutionQuestionRepositoryInterface $questionRepository,
        private readonly AddInstitutionQuestionsFromBankAction $addFromBankAction,
        private readonly DeleteInstitutionQuestionsAction $deleteQuestionsAction,
        private readonly StoreQuestionImageAction $storeQuestionImageAction,
        private readonly UpsertInstitutionQuestionAction $upsertInstitutionQuestionAction,
        private readonly SyncInstitutionQuestionToBankAction $syncInstitutionQuestionToBankAction,
    ) {}

    /**
     * @return array{added_count:int, skipped_count:int}
     */
    public function addFromBank(InstitutionAssessment $assessment, array $questionIds): array
    {
        return $this->addFromBankAction->execute($assessment, $questionIds);
    }

    public function deleteQuestion(InstitutionAssessment $assessment, InstitutionQuestion $question): void
    {
        $this->deleteQuestionsAction->deleteOne($assessment, $question);
    }

    public function deleteQuestions(InstitutionAssessment $assessment, array $questionIds): int
    {
        return $this->deleteQuestionsAction->deleteMany($assessment, $questionIds);
    }

    public function storeQuestions(InstitutionAssessment $assessment, array $questions): void
    {
        DB::transaction(function () use ($assessment, $questions) {
            $existingQuestionIds = [];
            $totalPoints = 0;

            foreach ($questions as $payload) {
                $imagePath = $this->storeQuestionImageAction->execute($payload['image'] ?? null);
                $question = $this->upsertInstitutionQuestionAction->execute(
                    $assessment,
                    $payload,
                    $imagePath
                );

                $existingQuestionIds[] = $question->id;
                $totalPoints += $payload['points'];
            }

            $this->questionRepository->deleteMissingForAssessment($assessment, $existingQuestionIds);
            $this->assessmentRepository->update($assessment, ['total_points' => $totalPoints]);
        });
    }

    public function saveQuestion(
        InstitutionAssessment $assessment,
        array $payload,
        User $user,
    ): InstitutionQuestion {
        $hasValidId = ! empty($payload['id']) && $payload['id'] > 0;
        $isNewQuestion = ! $hasValidId;

        Log::info('Saving institution question', [
            'is_new' => $isNewQuestion,
            'has_id' => ! empty($payload['id']),
            'question_id' => $payload['id'] ?? 'none',
            'question_id_type' => gettype($payload['id'] ?? null),
            'assessment_id' => $assessment->id,
            'question_text_preview' => substr($payload['question_text'] ?? '', 0, 50),
        ]);

        $imagePath = $this->storeQuestionImageAction->execute($payload['image'] ?? null);

        $question = DB::transaction(function () use ($assessment, $payload, $imagePath) {
            $question = $this->upsertInstitutionQuestionAction->execute(
                $assessment,
                $payload,
                $imagePath
            );

            $this->assessmentRepository->update($assessment, [
                'total_points' => $this->assessmentRepository->sumQuestionPoints($assessment),
            ]);

            return $question;
        });

        $this->syncInstitutionQuestionToBankAction->execute(
            $question,
            $assessment,
            $payload,
            $imagePath,
            $user,
            $isNewQuestion
        );

        $this->assessmentRepository->update($assessment, [
            'total_points' => $this->assessmentRepository->sumQuestionPoints($assessment),
        ]);

        return $question;
    }
}
