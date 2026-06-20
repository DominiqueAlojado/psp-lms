<?php

namespace App\Repositories\Eloquent;

use App\Models\National\NationalAssessment;
use App\Models\National\NationalQuestion;
use App\Repositories\Contracts\NationalQuestionRepositoryInterface;

class NationalQuestionRepository implements NationalQuestionRepositoryInterface
{
    public function createForAssessment(NationalAssessment $assessment, array $attributes): NationalQuestion
    {
        return $assessment->questions()->create($attributes);
    }

    public function update(NationalQuestion $question, array $attributes): bool
    {
        return $question->update($attributes);
    }

    public function delete(NationalQuestion $question): bool
    {
        return (bool) $question->delete();
    }

    public function findForAssessment(NationalAssessment $assessment, int $questionId): ?NationalQuestion
    {
        return NationalQuestion::query()
            ->where('assessment_id', $assessment->id)
            ->where('id', $questionId)
            ->with('choices')
            ->first();
    }

    public function getNextOrderForAssessment(NationalAssessment $assessment): int
    {
        return (int) (($assessment->questions()->max('order') ?? 0) + 1);
    }
}
