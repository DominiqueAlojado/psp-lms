<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

interface RolesPermissionsRepositoryInterface
{
    public function getRolesWithPermissions(): Collection;

    public function getPermissionsOrdered(): Collection;

    public function getPermissionModuleOptions(): Collection;

    public function syncPermissionModuleOptionsFromPermissions(): int;

    public function renamePermissionModule(string $fromModule, string $toModule): int;

    public function movePermissionsToModule(string $fromModule, string $toModule): int;

    public function createRole(array $attributes): Role;

    public function updateRole(Role $role, array $attributes): bool;

    public function deleteRole(Role $role): bool;

    public function createPermission(array $attributes): Permission;

    public function updatePermission(Permission $permission, array $attributes): bool;

    public function deletePermission(Permission $permission): bool;

    public function ensurePermissionModuleExists(string $module): void;

    public function permissionModuleExists(string $module): bool;

    public function createPermissionModuleOption(array $attributes): void;

    public function renamePermissionModuleOption(string $fromModule, string $toModule): void;

    public function deletePermissionModuleOption(string $module): void;

    public function syncRolePermissions(Role $role, array $permissionIds): void;
}
