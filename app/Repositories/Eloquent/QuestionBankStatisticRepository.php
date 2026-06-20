<?php

namespace App\Repositories\Eloquent;

use App\Models\QuestionBank;
use App\Repositories\Contracts\QuestionBankStatisticRepositoryInterface;

class QuestionBankStatisticRepository implements QuestionBankStatisticRepositoryInterface
{
    public function initializeForQuestion(QuestionBank $question, string $scope, ?int $institutionId): void
    {
        $question->statistics()->create([
            'question_id' => $question->id,
            'scope' => $scope,
            'institution_id' => $institutionId,
        ]);
    }
}
