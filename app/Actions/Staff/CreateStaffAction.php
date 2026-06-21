<?php

namespace App\Actions\Staff;

use App\Models\User;
use App\Repositories\Contracts\StaffRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateStaffAction
{
    public function __construct(
        private readonly StaffRepositoryInterface $staffRepository,
    ) {}

    public function execute(array $validated): User
    {
        $user = $this->staffRepository->create([
            'uuid' => Str::uuid(),
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
            'current_organization_id' => $validated['current_organization_id'] ?? null,
        ]);

        $this->staffRepository->syncRoles($user, $validated['roles']);

        if (! empty($validated['organizations'])) {
            $this->staffRepository->attachOrganizations($user, $validated['organizations']);
        }

        return $user;
    }
}
