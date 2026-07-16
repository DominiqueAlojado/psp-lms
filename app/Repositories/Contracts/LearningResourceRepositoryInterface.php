<?php

namespace App\Repositories\Contracts;

use App\Models\LearningResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface LearningResourceRepositoryInterface
{
    public function paginatePublishedByOrganization(?int $organizationId, array $filters, int $perPage = 20, bool $includeAllOrganizations = false): LengthAwarePaginator;

    public function paginateForManagementByOrganization(?int $organizationId, bool $canManageSystem, array $filters, int $perPage = 20, bool $includeAllOrganizations = false): LengthAwarePaginator;

    public function getPublishedCategoriesByOrganization(?int $organizationId, bool $includeAllOrganizations = false): Collection;

    public function getCategoriesByOrganization(?int $organizationId, bool $includeAllOrganizations = false): Collection;

    public function create(array $attributes): LearningResource;

    public function update(LearningResource $resource, array $attributes): bool;

    public function delete(LearningResource $resource): bool;

    public function incrementDownloadCount(LearningResource $resource): void;
}
