<?php

namespace App\Actions\Organizations;

use App\Models\Organization;
use App\Repositories\Contracts\OrganizationRepositoryInterface;

class DeleteOrganizationAction
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizationRepository,
    ) {}

    public function execute(Organization $organization): bool
    {
        return $this->organizationRepository->delete($organization);
    }
}
