<?php

namespace App\Repositories\Contracts;

use App\Models\National\NationalQuestion;

interface NationalQuestionChoiceRepositoryInterface
{
    public function createMany(NationalQuestion $question, array $choices): void;

    public function replaceForQuestion(NationalQuestion $question, array $choices): void;

    public function deleteForQuestion(NationalQuestion $question): void;
}
