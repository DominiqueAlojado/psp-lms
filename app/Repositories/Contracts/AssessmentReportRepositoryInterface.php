<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface AssessmentReportRepositoryInterface
{
    public function institutionExamExists(int $examId): bool;

    public function nationalExamExists(int $examId): bool;

    public function getCompletedInstitutionAttemptsForReport(array $filters, ?int $organizationId, bool $canViewAllOrganizations): Collection;

    public function getCompletedNationalAttemptsForReport(array $filters, ?int $organizationId, bool $canViewAllOrganizations): Collection;

    public function getOrganizations(): Collection;

    public function getPublishedInstitutionExamOptions(?int $organizationId, bool $canViewAllOrganizations): Collection;

    public function getPublishedNationalExamOptions(): Collection;

    public function getLiveInstitutionAttempts(array $filters, ?int $organizationId, bool $canViewAllOrganizations): Collection;

    public function getSessionChangesForInstitutionAttempt(int $attemptId): Collection;

    public function getIdlePeriodsForInstitutionAttempt(int $attemptId): Collection;
}
