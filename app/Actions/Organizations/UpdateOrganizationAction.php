<?php

namespace App\Actions\Organizations;

use App\Models\Organization;
use App\Repositories\Contracts\OrganizationRepositoryInterface;

class UpdateOrganizationAction
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizationRepository,
    ) {}

    public function execute(Organization $organization, array $attributes): bool
    {
        return $this->organizationRepository->update($organization, $attributes);
    }
}
