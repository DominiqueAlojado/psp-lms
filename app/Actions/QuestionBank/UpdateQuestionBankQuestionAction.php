<?php

namespace App\Actions\QuestionBank;

use App\Models\QuestionBank;
use App\Repositories\Contracts\QuestionBankRepositoryInterface;

class UpdateQuestionBankQuestionAction
{
    public function __construct(
        private readonly QuestionBankRepositoryInterface $questionBankRepository,
    ) {}

    public function execute(QuestionBank $question, array $attributes): bool
    {
        return $this->questionBankRepository->update($question, $attributes);
    }
}
