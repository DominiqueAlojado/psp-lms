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
        $this->rolesPermissionsRepository->ensurePermissionModuleExists($validated['module']);

        $this->rolesPermissionsRepository->createPermission([
            'name' => $validated['name'],
            'guard_name' => 'web',
            'module' => $validated['module'],
            'display_order' => 999,
        ]);
    }

    public function updatePermission(Permission $permission, array $validated): void
    {
        $this->rolesPermissionsRepository->ensurePermissionModuleExists($validated['module']);

        $this->rolesPermissionsRepository->updatePermission($permission, $validated);
    }

    public function deletePermission(Permission $permission): void
    {
        $this->rolesPermissionsRepository->deletePermission($permission);
    }

    public function createPermissionModule(string $name): void
    {
        $trimmedName = trim($name);

        if ($trimmedName === '') {
            abort(422, 'Permission module name is required.');
        }

        if ($this->rolesPermissionsRepository->permissionModuleExists($trimmedName)) {
            abort(422, 'Permission module already exists.');
        }

        $nextOrder = $this->rolesPermissionsRepository->getPermissionModuleOptions()->count() + 1000;

        $this->rolesPermissionsRepository->createPermissionModuleOption([
            'name' => $trimmedName,
            'display_order' => $nextOrder,
        ]);
    }

    public function renamePermissionModule(string $fromModule, string $toModule): void
    {
        if ($fromModule === $toModule) {
            return;
        }

        $trimmedName = trim($toModule);

        if ($trimmedName === '') {
            abort(422, 'Permission module name is required.');
        }

        if ($fromModule !== $trimmedName && $this->rolesPermissionsRepository->permissionModuleExists($trimmedName)) {
            abort(422, 'Permission module already exists.');
        }

        $updated = $this->rolesPermissionsRepository->renamePermissionModule(
            $fromModule,
            $trimmedName,
        );

        if ($updated === 0 && ! $this->rolesPermissionsRepository->permissionModuleExists($fromModule)) {
            abort(404, 'Permission module not found.');
        }
    }

    public function deletePermissionModule(string $module): void
    {
        if ($module === 'Other') {
            abort(422, 'The Other module cannot be deleted.');
        }

        $updated = $this->rolesPermissionsRepository->movePermissionsToModule(
            $module,
            'Other',
        );

        if ($updated === 0) {
            abort(404, 'Permission module not found.');
        }
    }

    public function syncPermissionModulesFromPermissions(): int
    {
        return $this->rolesPermissionsRepository->syncPermissionModuleOptionsFromPermissions();
    }

    public function syncRolePermissions(Role $role, array $permissionIds): void
    {
        $this->rolesPermissionsRepository->syncRolePermissions($role, $permissionIds);
    }
}
