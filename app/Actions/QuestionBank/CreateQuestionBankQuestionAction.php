<?php

namespace App\Actions\QuestionBank;

use App\Models\QuestionBank;
use App\Repositories\Contracts\QuestionBankRepositoryInterface;

class CreateQuestionBankQuestionAction
{
    public function __construct(
        private readonly QuestionBankRepositoryInterface $questionBankRepository,
    ) {}

    public function execute(array $attributes): QuestionBank
    {
        return $this->questionBankRepository->create($attributes);
    }
}
