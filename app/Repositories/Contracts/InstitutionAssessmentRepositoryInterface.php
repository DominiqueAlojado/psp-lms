<?php

namespace App\Repositories\Contracts;

use App\Models\Institution\InstitutionAssessment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InstitutionAssessmentRepositoryInterface
{
    public function paginateByPublication(?int $organizationId, bool $isPublished, array $filters, int $perPage = 15, bool $includeAllOrganizations = false): LengthAwarePaginator;

    public function create(array $attributes): InstitutionAssessment;

    public function update(InstitutionAssessment $assessment, array $attributes): bool;

    public function delete(InstitutionAssessment $assessment): bool;

    public function loadForEdit(InstitutionAssessment $assessment): InstitutionAssessment;

    public function loadForShow(InstitutionAssessment $assessment): InstitutionAssessment;

    public function loadQuestionsWithChoices(InstitutionAssessment $assessment): InstitutionAssessment;

    public function attemptsCount(InstitutionAssessment $assessment): int;

    public function sumQuestionPoints(InstitutionAssessment $assessment): int;

    public function titleExists(int $organizationId, string $title): bool;
}
