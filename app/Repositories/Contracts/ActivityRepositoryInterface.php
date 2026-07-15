<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ActivityRepositoryInterface
{
    public function paginateForOrganization(int $organizationId, ?string $organizationType, array $filters, int $perPage = 20): LengthAwarePaginator;

    public function getSummaryForOrganization(int $organizationId, ?string $organizationType, array $filters): array;
}
