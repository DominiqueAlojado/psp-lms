<?php

namespace App\Repositories\Contracts;

use App\Models\National\NationalAssessment;
use App\Models\National\NationalQuestion;

interface NationalQuestionRepositoryInterface
{
    public function createForAssessment(NationalAssessment $assessment, array $attributes): NationalQuestion;

    public function update(NationalQuestion $question, array $attributes): bool;

    public function delete(NationalQuestion $question): bool;

    public function findForAssessment(NationalAssessment $assessment, int $questionId): ?NationalQuestion;

    public function getNextOrderForAssessment(NationalAssessment $assessment): int;
}
