<?php

namespace App\Repositories\Contracts;

use App\Models\QuestionBank;

interface QuestionBankStatisticRepositoryInterface
{
    public function initializeForQuestion(QuestionBank $question, string $scope, ?int $institutionId): void;
}
