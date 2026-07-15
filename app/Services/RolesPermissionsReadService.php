<?php

namespace App\Services;

use App\Repositories\Contracts\RolesPermissionsRepositoryInterface;

class RolesPermissionsReadService
{
    public function __construct(
        private readonly RolesPermissionsRepositoryInterface $rolesPermissionsRepository,
    ) {}

    public function indexPayload(): array
    {
        $roles = $this->rolesPermissionsRepository
            ->getRolesWithPermissions()
            ->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions_count' => $role->permissions->count(),
                'permissions' => $role->permissions->pluck('name'),
            ]);

        $permissions = $this->rolesPermissionsRepository
            ->getPermissionsOrdered()
            ->map(fn ($permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
                'guard_name' => $permission->guard_name,
                'category' => $permission->category ?? 'Other',
                'display_order' => $permission->display_order,
            ]);

        return [
            'roles' => $roles,
            'permissions' => $permissions,
            'groupedPermissions' => $permissions->groupBy('category')->map(fn ($perms) => $perms->values()),
        ];
    }
}
