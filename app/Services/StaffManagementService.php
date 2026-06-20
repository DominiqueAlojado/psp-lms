<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\StaffRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class StaffManagementService
{
    public function __construct(
        private readonly StaffRepositoryInterface $staffRepository,
    ) {}

    public function create(array $validated): User
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

    public function update(User $staff, array $validated): array
    {
        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'current_organization_id' => $validated['current_organization_id'] ?? null,
        ];

        $passwordChanged = ! empty($validated['password']);

        if ($passwordChanged) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $this->staffRepository->update($staff, $updateData);

        $newRoleIds = $validated['roles'];
        $newRoles = Role::query()
            ->whereIn('id', $newRoleIds)
            ->pluck('name')
            ->sort()
            ->values()
            ->toArray();
        $this->staffRepository->syncRoles($staff, $newRoleIds);

        $newOrganizations = $staff->organizations->pluck('name')->sort()->values()->toArray();
        if (array_key_exists('organizations', $validated)) {
            $newOrgIds = $validated['organizations'] ?? [];
            $newOrganizations = $this->staffRepository->getOrganizationNamesByIds($newOrgIds);
            $this->staffRepository->syncOrganizations($staff, $newOrgIds);
        }

        return [
            'passwordChanged' => $passwordChanged,
            'newRoles' => $newRoles,
            'newOrganizations' => $newOrganizations,
        ];
    }
}
