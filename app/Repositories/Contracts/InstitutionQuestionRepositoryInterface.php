<?php

namespace App\Repositories\Contracts;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionQuestion;

interface InstitutionQuestionRepositoryInterface
{
    public function createForAssessment(InstitutionAssessment $assessment, array $attributes): InstitutionQuestion;

    public function update(InstitutionQuestion $question, array $attributes): bool;

    public function delete(InstitutionQuestion $question): bool;

    public function findForAssessment(InstitutionAssessment $assessment, int $questionId): ?InstitutionQuestion;

    public function deleteMissingForAssessment(InstitutionAssessment $assessment, array $questionIds): void;
}
