<?php

namespace App\Repositories\Contracts;

use App\Models\Assignment;
use App\Models\User;
use Illuminate\Support\Collection;

interface AssignmentRepositoryInterface
{
    public function getForOrganization(int $organizationId): Collection;

    public function create(array $attributes): Assignment;

    public function update(Assignment $assignment, array $attributes): bool;

    public function delete(Assignment $assignment): bool;

    public function getPublishedForResident(int $organizationId, ?string $yearLevel, User $user): Collection;
}
