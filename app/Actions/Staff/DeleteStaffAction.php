<?php

namespace App\Actions\Staff;

use App\Models\User;
use App\Repositories\Contracts\StaffRepositoryInterface;

class DeleteStaffAction
{
    public function __construct(
        private readonly StaffRepositoryInterface $staffRepository,
    ) {}

    public function execute(User $staff): bool
    {
        return $this->staffRepository->delete($staff);
    }
}
