<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\RolesPermissionsRepositoryInterface;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsRepository implements RolesPermissionsRepositoryInterface
{
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
        return Role::create($attributes);
    }

    public function updateRole(Role $role, array $attributes): bool
    {
        return $role->update($attributes);
    }

    public function deleteRole(Role $role): bool
    {
        return (bool) $role->delete();
    }

    public function createPermission(array $attributes): Permission
    {
        return Permission::create($attributes);
    }

    public function updatePermission(Permission $permission, array $attributes): bool
    {
        return $permission->update($attributes);
    }

    public function deletePermission(Permission $permission): bool
    {
        return (bool) $permission->delete();
    }

    public function syncRolePermissions(Role $role, array $permissionIds): void
    {
        $role->syncPermissions($permissionIds);
    }
}
