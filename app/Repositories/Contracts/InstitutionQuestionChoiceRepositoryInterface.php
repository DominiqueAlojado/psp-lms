<?php

namespace App\Repositories\Contracts;

use App\Models\Institution\InstitutionQuestion;

interface InstitutionQuestionChoiceRepositoryInterface
{
    public function createMany(InstitutionQuestion $question, array $choices): void;

    public function replaceForQuestion(InstitutionQuestion $question, array $choices): void;

    public function deleteForQuestion(InstitutionQuestion $question): void;
}
