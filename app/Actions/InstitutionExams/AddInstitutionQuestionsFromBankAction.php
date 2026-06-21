<?php

namespace App\Actions\InstitutionExams;

use App\Models\Institution\InstitutionAssessment;
use App\Repositories\Contracts\InstitutionAssessmentRepositoryInterface;
use App\Repositories\Contracts\InstitutionQuestionChoiceRepositoryInterface;
use App\Repositories\Contracts\InstitutionQuestionRepositoryInterface;
use App\Repositories\Contracts\QuestionBankRepositoryInterface;
use Illuminate\Support\Facades\DB;

class AddInstitutionQuestionsFromBankAction
{
    public function __construct(
        private readonly InstitutionAssessmentRepositoryInterface $assessmentRepository,
        private readonly InstitutionQuestionRepositoryInterface $questionRepository,
        private readonly InstitutionQuestionChoiceRepositoryInterface $questionChoiceRepository,
        private readonly QuestionBankRepositoryInterface $questionBankRepository,
    ) {}

    /**
     * @return array{added_count:int, skipped_count:int}
     */
    public function execute(InstitutionAssessment $assessment, array $questionIds): array
    {
        $isNationalOrgAssessment = $assessment->organization?->type === 'national';

        $bankQuestions = $isNationalOrgAssessment
            ? $this->questionBankRepository->findByIdsForOwnerType($questionIds, 'national')
            : $this->questionBankRepository->findByIdsForOrganization(
                $questionIds,
                $assessment->organization_id
            );

        if ($bankQuestions->isEmpty()) {
            return ['added_count' => 0, 'skipped_count' => 0];
        }

        $addedCount = 0;
        $skippedCount = 0;

        DB::transaction(function () use ($assessment, $bankQuestions, &$addedCount, &$skippedCount) {
            $existingSignatures = $assessment->questions()
                ->get(['question_text', 'question_type'])
                ->map(fn ($question) => $this->buildQuestionSignature(
                    $question->question_text,
                    $question->question_type
                ));

            foreach ($bankQuestions as $bankQuestion) {
                $signature = $this->buildQuestionSignature(
                    $bankQuestion->question_text,
                    $bankQuestion->question_type
                );

                if ($existingSignatures->contains($signature)) {
                    $skippedCount++;

                    continue;
                }

                $question = $this->questionRepository->createForAssessment($assessment, [
                    'topic_id' => $bankQuestion->topic_id,
                    'question_type' => $bankQuestion->question_type,
                    'question_text' => $bankQuestion->question_text,
                    'points' => $bankQuestion->points,
                    'explanation' => $bankQuestion->explanation,
                    'image_path' => $bankQuestion->image_path,
                ]);

                $this->questionChoiceRepository->createMany(
                    $question,
                    $bankQuestion->choices->map(fn ($bankChoice) => [
                        'choice_text' => $bankChoice->choice_text,
                        'is_correct' => $bankChoice->is_correct,
                        'order' => $bankChoice->order,
                    ])->all()
                );

                $this->questionBankRepository->incrementUsage($bankQuestion);
                $existingSignatures->push($signature);
                $addedCount++;
            }

            $this->assessmentRepository->update($assessment, [
                'total_points' => $this->assessmentRepository->sumQuestionPoints($assessment),
            ]);
        });

        return [
            'added_count' => $addedCount,
            'skipped_count' => $skippedCount,
        ];
    }

    private function buildQuestionSignature(string $questionText, string $questionType): string
    {
        $normalizedText = preg_replace('/\s+/u', ' ', trim(strip_tags($questionText))) ?? '';

        return mb_strtolower($questionType . '|' . $normalizedText);
    }
}
