<?php

namespace App\Actions\Organizations;

use App\Models\Organization;
use App\Models\User;

class SwitchUserOrganizationAction
{
    public function execute(User $user, Organization $organization): bool
    {
        return $user->switchOrganization($organization);
    }
}
