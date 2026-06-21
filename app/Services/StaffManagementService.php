<?php

namespace App\Services;

use App\Actions\Staff\CreateStaffAction;
use App\Actions\Staff\DeleteStaffAction;
use App\Actions\Staff\UpdateStaffAction;
use App\Models\User;

class StaffManagementService
{
    public function __construct(
        private readonly CreateStaffAction $createStaffAction,
        private readonly UpdateStaffAction $updateStaffAction,
        private readonly DeleteStaffAction $deleteStaffAction,
    ) {}

    public function create(array $validated): User
    {
        return $this->createStaffAction->execute($validated);
    }

    public function update(User $staff, array $validated): array
    {
        return $this->updateStaffAction->execute($staff, $validated);
    }

    public function delete(User $staff): bool
    {
        return $this->deleteStaffAction->execute($staff);
    }
}
