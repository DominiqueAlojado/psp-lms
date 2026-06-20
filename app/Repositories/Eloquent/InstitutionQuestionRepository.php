<?php

namespace App\Repositories\Eloquent;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionQuestion;
use App\Repositories\Contracts\InstitutionQuestionRepositoryInterface;

class InstitutionQuestionRepository implements InstitutionQuestionRepositoryInterface
{
    public function createForAssessment(InstitutionAssessment $assessment, array $attributes): InstitutionQuestion
    {
        return $assessment->questions()->create($attributes);
    }

    public function update(InstitutionQuestion $question, array $attributes): bool
    {
        return $question->update($attributes);
    }

    public function delete(InstitutionQuestion $question): bool
    {
        return (bool) $question->delete();
    }

    public function findForAssessment(InstitutionAssessment $assessment, int $questionId): ?InstitutionQuestion
    {
        return $assessment->questions()->find($questionId);
    }

    public function deleteMissingForAssessment(InstitutionAssessment $assessment, array $questionIds): void
    {
        $assessment->questions()->whereNotIn('id', $questionIds)->delete();
    }
}
