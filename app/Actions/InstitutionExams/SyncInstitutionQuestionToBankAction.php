<?php

namespace App\Actions\InstitutionExams;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionQuestion;
use App\Models\User;
use App\Repositories\Contracts\QuestionBankChoiceRepositoryInterface;
use App\Repositories\Contracts\QuestionBankRepositoryInterface;
use App\Repositories\Contracts\QuestionBankStatisticRepositoryInterface;
use Illuminate\Support\Facades\Log;

class SyncInstitutionQuestionToBankAction
{
    public function __construct(
        private readonly QuestionBankRepositoryInterface $questionBankRepository,
        private readonly QuestionBankChoiceRepositoryInterface $questionBankChoiceRepository,
        private readonly QuestionBankStatisticRepositoryInterface $questionBankStatisticRepository,
    ) {}

    public function execute(
        InstitutionQuestion $question,
        InstitutionAssessment $assessment,
        array $payload,
        ?string $imagePath,
        User $user,
        bool $isNewQuestion,
    ): void {
        $choicesData = $this->buildChoicesData($payload);

        $existsInBank = $this->questionBankRepository->existsForInstitutionCreator(
            $question->question_text,
            $assessment->organization_id,
            $user->id
        );

        if (! $isNewQuestion && $existsInBank) {
            Log::info('Institution question not saved to question bank - already exists', [
                'question_id' => $question->id,
                'has_id' => ! empty($payload['id']),
                'exists_in_bank' => $existsInBank,
            ]);

            return;
        }

        try {
            Log::info('Attempting to save institution question to question bank', [
                'question_text' => substr($question->question_text, 0, 50),
                'topic_id' => $question->topic_id,
                'organization_id' => $assessment->organization_id,
                'user_id' => $user->id,
                'choices_count' => count($choicesData),
            ]);

            $bankQuestion = $this->questionBankRepository->create([
                'organization_id' => $assessment->organization_id,
                'owner_type' => 'institution',
                'topic_id' => $question->topic_id,
                'created_by' => $user->id,
                'question_type' => $question->question_type,
                'question_text' => $question->question_text,
                'points' => $question->points,
                'image_path' => $imagePath,
                'is_approved' => false,
            ]);

            Log::info('Institution question bank entry created', ['bank_question_id' => $bankQuestion->id]);

            $this->questionBankChoiceRepository->createMany($bankQuestion, $choicesData);
            $this->questionBankStatisticRepository->initializeForQuestion(
                $bankQuestion,
                'institution',
                $assessment->organization_id
            );

            Log::info('Institution question saved to question bank', [
                'question_id' => $question->id,
                'assessment_id' => $assessment->id,
                'is_new' => $isNewQuestion,
                'exists_in_bank' => $existsInBank,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save institution question to question bank', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'question_id' => $question->id,
            ]);
        }
    }

    private function buildChoicesData(array $payload): array
    {
        if (in_array($payload['question_type'], ['multiple_choice', 'multiple_select'])) {
            return collect($payload['choices'] ?? [])->map(fn ($choice) => [
                'choice_text' => $choice['choice_text'],
                'is_correct' => (bool) ($choice['is_correct'] ?? false),
            ])->all();
        }

        if ($payload['question_type'] === 'true_false') {
            $answer = filter_var($payload['answer'] ?? false, FILTER_VALIDATE_BOOLEAN);

            return [
                ['choice_text' => 'True', 'is_correct' => $answer === true],
                ['choice_text' => 'False', 'is_correct' => $answer === false],
            ];
        }

        return [];
    }
}
