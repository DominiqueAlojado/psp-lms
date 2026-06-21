<?php

namespace App\Actions\Residents;

use App\Models\Organization;
use App\Models\Resident;

class DetachResidentOrganizationAction
{
    public function execute(Resident $resident, Organization $organization): bool
    {
        return $resident->removeFromOrganization($organization);
    }
}
