<?php

namespace App\Repositories\Contracts;

use App\Models\Organization;
use App\Models\Resident;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface ResidentRepositoryInterface
{
    public function paginate(array $filters, ?int $organizationId = null, ?int $membershipOrganizationId = null, int $perPage = 15): LengthAwarePaginator;

    public function getOrganizations(): Collection;

    public function getForOrganization(int $organizationId): Collection;

    public function getActiveOrganizationsExcluding(array $excludedIds): Collection;

    public function getYearLevelStats(?int $organizationId = null, ?int $membershipOrganizationId = null): array;

    public function getDistinctCourses(?int $organizationId = null, ?int $membershipOrganizationId = null): Collection;

    public function create(array $attributes): Resident;

    public function update(Resident $resident, array $attributes): bool;

    public function delete(Resident $resident): bool;

    public function findOrganizationById(int $organizationId): ?Organization;

    public function findForOrganization(int $organizationId, int $residentId): Resident;

    public function scopeToOrganization(Builder $query, ?int $organizationId = null, ?int $membershipOrganizationId = null): Builder;
}
