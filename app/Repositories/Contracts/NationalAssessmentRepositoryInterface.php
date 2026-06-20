<?php

namespace App\Repositories\Contracts;

use App\Models\National\NationalAssessment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface NationalAssessmentRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function paginateByPublication(bool $isPublished, array $filters, int $perPage = 15): LengthAwarePaginator;

    public function getDistinctYears(): array;

    public function create(array $attributes): NationalAssessment;

    public function update(NationalAssessment $assessment, array $attributes): bool;

    public function delete(NationalAssessment $assessment): bool;

    public function loadForEdit(NationalAssessment $assessment): NationalAssessment;

    public function loadForShow(NationalAssessment $assessment): NationalAssessment;

    public function loadQuestionsWithChoices(NationalAssessment $assessment): NationalAssessment;

    public function attemptsExist(NationalAssessment $assessment): bool;

    public function sumQuestionPoints(NationalAssessment $assessment): int;
}
