<?php

namespace App\Actions\NationalAssessments;

use App\Models\National\NationalQuestion;
use App\Models\QuestionBank;
use App\Models\Topic;
use App\Models\User;
use App\Repositories\Contracts\QuestionBankChoiceRepositoryInterface;
use App\Repositories\Contracts\QuestionBankRepositoryInterface;
use App\Repositories\Contracts\QuestionBankStatisticRepositoryInterface;

class SyncNationalQuestionToBankAction
{
    public function __construct(
        private readonly QuestionBankRepositoryInterface $questionBankRepository,
        private readonly QuestionBankChoiceRepositoryInterface $questionBankChoiceRepository,
        private readonly QuestionBankStatisticRepositoryInterface $questionBankStatisticRepository,
    ) {}

    public function execute(
        NationalQuestion $question,
        array $choicesData,
        ?string $imagePath,
        ?int $topicId,
        ?string $topicName,
        User $user
    ): QuestionBank {
        if (! $topicId && $topicName) {
            $topicId = Topic::where('name', $topicName)->first()?->id;
        }

        $bankQuestion = $this->questionBankRepository->create([
            'organization_id' => null,
            'owner_type' => 'national',
            'topic_id' => $topicId,
            'created_by' => $user->id,
            'question_type' => $question->question_type,
            'question_text' => $question->question_text,
            'points' => $question->points,
            'image_path' => $imagePath,
            'is_approved' => false,
        ]);

        $this->questionBankChoiceRepository->createMany($bankQuestion, $choicesData);
        $this->questionBankStatisticRepository->initializeForQuestion($bankQuestion, 'national', null);

        return $bankQuestion;
    }
}
