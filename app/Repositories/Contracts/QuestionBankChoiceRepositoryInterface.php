<?php

namespace App\Repositories\Contracts;

use App\Models\QuestionBank;

interface QuestionBankChoiceRepositoryInterface
{
    public function createMany(QuestionBank $question, array $choices): void;

    public function replaceForQuestion(QuestionBank $question, array $choices): void;
}
