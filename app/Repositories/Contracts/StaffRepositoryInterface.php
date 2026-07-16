<?php

namespace App\Repositories\Contracts;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface StaffRepositoryInterface
{
    public function getStaffRoleNames(): array;

    public function paginate(array $filters, array $staffRoleNames, array $userOrganizationIds, bool $isAllOrganizationsContext, ?int $currentOrganizationId = null, int $perPage = 15): LengthAwarePaginator;

    public function getRoleStats(array $userOrganizationIds, bool $isAllOrganizationsContext, ?int $currentOrganizationId = null): Collection;

    public function getSelectableRoles(User $user, bool $canManageAllOrganizations): Collection;

    public function getSelectableOrganizations(User $user, bool $canManageAllOrganizations): Collection;

    public function create(array $attributes): User;

    public function update(User $staff, array $attributes): bool;

    public function delete(User $staff): bool;

    public function syncRoles(User $staff, array $roleIds): void;

    public function attachOrganizations(User $staff, array $organizationIds): void;

    public function syncOrganizations(User $staff, array $organizationIds): void;

    public function getOrganizationNamesByIds(array $organizationIds): array;

    public function findOrganizationsByIds(array $organizationIds): Collection;
}
