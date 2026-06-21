<?php

namespace App\Actions\QuestionBank;

use App\Models\QuestionBank;
use App\Repositories\Contracts\QuestionBankRepositoryInterface;

class DeleteQuestionBankQuestionAction
{
    public function __construct(
        private readonly QuestionBankRepositoryInterface $questionBankRepository,
    ) {}

    public function execute(QuestionBank $question): bool
    {
        return $this->questionBankRepository->delete($question);
    }
}
