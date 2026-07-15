<?php

namespace App\Services;

use App\Repositories\Contracts\RolesPermissionsRepositoryInterface;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsManagementService
{
    public function __construct(
        private readonly RolesPermissionsRepositoryInterface $rolesPermissionsRepository,
    ) {}

    public function createRole(array $validated): void
    {
        $this->rolesPermissionsRepository->createRole([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);
    }

    public function updateRole(Role $role, array $validated): void
    {
        $this->rolesPermissionsRepository->updateRole($role, $validated);
    }

    public function deleteRole(Role $role): void
    {
        if (in_array($role->name, ['System Admin', 'Admin', 'Resident'], true)) {
            abort(422, 'Cannot delete system roles');
        }

        $this->rolesPermissionsRepository->deleteRole($role);
    }

    public function createPermission(array $validated): void
    {
        $this->rolesPermissionsRepository->createPermission([
            'name' => $validated['name'],
            'guard_name' => 'web',
            'category' => $validated['category'],
            'display_order' => 999,
        ]);
    }

    public function updatePermission(Permission $permission, array $validated): void
    {
        $this->rolesPermissionsRepository->updatePermission($permission, $validated);
    }

    public function deletePermission(Permission $permission): void
    {
        $this->rolesPermissionsRepository->deletePermission($permission);
    }

    public function syncRolePermissions(Role $role, array $permissionIds): void
    {
        $this->rolesPermissionsRepository->syncRolePermissions($role, $permissionIds);
    }
}
