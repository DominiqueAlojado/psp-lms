<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    public function update(User $user, array $attributes): bool;

    public function updateProfile(User $user, array $attributes): bool;

    public function updatePassword(User $user, string $password): bool;

    public function delete(User $user): bool;
}
