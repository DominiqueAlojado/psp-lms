<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function update(User $user, array $attributes): bool
    {
        return $user->update($attributes);
    }

    public function updateProfile(User $user, array $attributes): bool
    {
        $user->fill($attributes);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        return $user->save();
    }

    public function updatePassword(User $user, string $password): bool
    {
        return $user->update([
            'password' => $password,
        ]);
    }

    public function delete(User $user): bool
    {
        return (bool) $user->delete();
    }
}
