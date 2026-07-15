<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class PasswordSettingsManagementService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function updatePassword(User $user, string $password): void
    {
        $this->userRepository->updatePassword($user, $password);
    }
}
