<?php

namespace App\Actions\Organizations;

use App\Models\Organization;
use App\Repositories\Contracts\OrganizationRepositoryInterface;

class CreateOrganizationAction
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizationRepository,
    ) {}

    public function execute(array $attributes): Organization
    {
        return $this->organizationRepository->create($attributes);
    }
}
