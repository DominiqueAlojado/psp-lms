<?php

namespace App\Actions\QuestionBank;

use App\Models\QuestionBank;
use App\Repositories\Contracts\QuestionBankRepositoryInterface;

class ApproveQuestionBankQuestionAction
{
    public function __construct(
        private readonly QuestionBankRepositoryInterface $questionBankRepository,
    ) {}

    public function execute(QuestionBank $question, int $approvedBy): bool
    {
        return $this->questionBankRepository->approve($question, $approvedBy);
    }
}
