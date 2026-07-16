<?php

namespace App\Repositories\Contracts;

use App\Models\Resident;
use Illuminate\Support\Collection;

interface GradebookRepositoryInterface
{
    public function getCompletedInstitutionAttemptsForUser(int $userId, bool $withAssessment = false): Collection;

    public function getCompletedNationalAttemptsForUser(int $userId, bool $withAssessment = false): Collection;

    public function getResidentsForOrganization(int $organizationId): Collection;

    public function getResidentsForOrganizations(array $organizationIds): Collection;

    public function getInstitutionTopicPerformanceRows(int $userId): Collection;

    public function getNationalTopicPerformanceRows(int $userId): Collection;
}
