<?php

namespace App\Services;

use App\Actions\Staff\CreateStaffAction;
use App\Actions\Staff\DeleteStaffAction;
use App\Actions\Staff\UpdateStaffAction;
use App\Models\Organization;
use App\Models\User;
use App\Repositories\Contracts\StaffRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class StaffManagementService
{
    public function __construct(
        private readonly CreateStaffAction $createStaffAction,
        private readonly UpdateStaffAction $updateStaffAction,
        private readonly DeleteStaffAction $deleteStaffAction,
        private readonly StaffRepositoryInterface $staffRepository,
    ) {}

    public function create(User $actor, array $validated): User
    {
        $this->ensureRolesAreManageable($actor, $validated['roles'] ?? []);
        $this->ensureOrganizationsAreManageable($actor, $validated['organizations'] ?? []);
        $this->ensureCurrentOrganizationIsAllowed(
            $validated['current_organization_id'] ?? null,
            $validated['organizations'] ?? []
        );

        return $this->createStaffAction->execute($validated);
    }

    public function update(User $actor, User $staff, array $validated): array
    {
        $this->ensureCanManageStaff($actor, $staff);
        $this->ensureRolesAreManageable($actor, $validated['roles'] ?? []);

        if (array_key_exists('organizations', $validated)) {
            $this->ensureOrganizationsAreManageable($actor, $validated['organizations'] ?? []);
        }

        $effectiveOrganizationIds = array_key_exists('organizations', $validated)
            ? ($validated['organizations'] ?? [])
            : $staff->organizations()->pluck('organizations.id')->all();

        $this->ensureCurrentOrganizationIsAllowed(
            $validated['current_organization_id'] ?? null,
            $effectiveOrganizationIds
        );

        return $this->updateStaffAction->execute($staff, $validated);
    }

    public function delete(User $staff): bool
    {
        return $this->deleteStaffAction->execute($staff);
    }

    private function ensureCanManageStaff(User $actor, User $staff): void
    {
        if ($actor->hasRole('System Admin')) {
            return;
        }

        $manageableOrganizationIds = $this->manageableOrganizationIds($actor);

        $staffOrganizationIds = $staff->organizations()
            ->pluck('organizations.id')
            ->all();

        if (empty(array_intersect($manageableOrganizationIds, $staffOrganizationIds))) {
            abort(403, 'You do not have access to manage this staff member.');
        }
    }

    private function ensureRolesAreManageable(User $actor, array $roleIds): void
    {
        if ($roleIds === []) {
            return;
        }

        $allowedRoleIds = $this->manageableRoles($actor)->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (array_diff($roleIds, $allowedRoleIds) !== []) {
            abort(403, 'You are not allowed to assign one or more selected roles.');
        }
    }

    private function ensureOrganizationsAreManageable(User $actor, array $organizationIds): void
    {
        if ($organizationIds === []) {
            return;
        }

        $allowedOrganizationIds = $this->manageableOrganizationIds($actor);

        if (array_diff($organizationIds, $allowedOrganizationIds) !== []) {
            abort(403, 'You are not allowed to assign one or more selected organizations.');
        }
    }

    private function ensureCurrentOrganizationIsAllowed(?int $currentOrganizationId, array $organizationIds): void
    {
        if ($currentOrganizationId === null) {
            return;
        }

        if (! in_array($currentOrganizationId, $organizationIds, true)) {
            throw ValidationException::withMessages([
                'current_organization_id' => 'The current organization must be included in the selected organizations.',
            ]);
        }
    }

    private function manageableRoles(User $actor): Collection
    {
        return $this->staffRepository->getSelectableRoles($actor, $actor->hasRole('System Admin'));
    }

    private function manageableOrganizationIds(User $actor): array
    {
        if ($actor->hasRole('System Admin')) {
            return Organization::query()
                ->where('is_active', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return $actor->organizations()
            ->wherePivot('organization_user.is_active', true)
            ->pluck('organizations.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
