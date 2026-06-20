<?php

namespace App\Repositories\Contracts;

use App\Models\Organization;
use App\Models\Resident;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface OrganizationRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function getTypeStats(): array;

    public function getDistinctTypes(): Collection;

    public function create(array $attributes): Organization;

    public function update(Organization $organization, array $attributes): bool;

    public function delete(Organization $organization): bool;

    public function hasResidents(Organization $organization): bool;

    public function getResidents(Organization $organization): Collection;

    public function findResident(Organization $organization, int $residentId): Resident;

    public function updateResident(Resident $resident, array $attributes): bool;

    public function updateResidentUser(Resident $resident, array $attributes): bool;
}
