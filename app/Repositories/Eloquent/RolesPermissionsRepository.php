<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\RolesPermissionsRepositoryInterface;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsRepository implements RolesPermissionsRepositoryInterface
{
    public function __construct(
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    public function getRolesWithPermissions(): Collection
    {
        return Role::with('permissions')->get();
    }

    public function getPermissionsOrdered(): Collection
    {
        return Permission::query()
            ->orderBy('category')
            ->orderBy('display_order')
            ->get();
    }

    public function createRole(array $attributes): Role
    {
        $role = Role::create($attributes);
        $this->clearPermissionCache();

        return $role;
    }

    public function updateRole(Role $role, array $attributes): bool
    {
        $updated = $role->update($attributes);

        if ($updated) {
            $this->clearPermissionCache();
        }

        return $updated;
    }

    public function deleteRole(Role $role): bool
    {
        $deleted = (bool) $role->delete();

        if ($deleted) {
            $this->clearPermissionCache();
        }

        return $deleted;
    }

    public function createPermission(array $attributes): Permission
    {
        $permission = Permission::create($attributes);
        $this->clearPermissionCache();

        return $permission;
    }

    public function updatePermission(Permission $permission, array $attributes): bool
    {
        $updated = $permission->update($attributes);

        if ($updated) {
            $this->clearPermissionCache();
        }

        return $updated;
    }

    public function deletePermission(Permission $permission): bool
    {
        $deleted = (bool) $permission->delete();

        if ($deleted) {
            $this->clearPermissionCache();
        }

        return $deleted;
    }

    public function syncRolePermissions(Role $role, array $permissionIds): void
    {
        $role->syncPermissions($permissionIds);
        $this->clearPermissionCache();
    }

    private function clearPermissionCache(): void
    {
        $this->permissionRegistrar->forgetCachedPermissions();
    }
}
