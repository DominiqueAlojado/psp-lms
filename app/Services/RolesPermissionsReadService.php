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
                'module' => $permission->module ?? 'Other',
                'display_order' => $permission->display_order,
            ]);

        $groupedPermissions = $permissions
            ->groupBy('module')
            ->map(fn ($perms) => $perms->values());

        $optionCategories = $this->rolesPermissionsRepository
            ->getPermissionModuleOptions()
            ->map(fn ($module) => [
                'name' => $module->name,
                'permissions_count' => 0,
                'display_order' => $module->display_order,
            ]);

        $derivedCategories = $groupedPermissions
            ->map(fn ($perms, $module) => [
                'name' => $module,
                'permissions_count' => $perms->count(),
                'display_order' => $perms->min('display_order') ?? 999,
            ])
            ->values();

        $categories = $optionCategories
            ->concat($derivedCategories)
            ->groupBy('name')
            ->map(function ($entries, $moduleName) {
                $first = $entries->sortBy('display_order')->first();

                return [
                    'name' => $moduleName,
                    'permissions_count' => $entries->max('permissions_count') ?? 0,
                    'display_order' => $first['display_order'] ?? 999,
                ];
            })
            ->sortBy([
                ['display_order', 'asc'],
                ['name', 'asc'],
            ])
            ->values();

        return [
            'roles' => $roles,
            'permissions' => $permissions,
            'modules' => $categories,
            'groupedPermissions' => $groupedPermissions,
        ];
    }
}
