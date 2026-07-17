<?php

namespace App\Repositories\Eloquent;

use App\Models\PermissionModuleOption;
use App\Repositories\Contracts\RolesPermissionsRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
            ->orderBy('module')
            ->orderBy('display_order')
            ->get();
    }

    public function getPermissionModuleOptions(): Collection
    {
        return PermissionModuleOption::query()
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    public function syncPermissionModuleOptionsFromPermissions(): int
    {
        $modules = Permission::query()
            ->select('module', DB::raw('MIN(display_order) as display_order'))
            ->whereNotNull('module')
            ->where('module', '!=', '')
            ->groupBy('module')
            ->orderByRaw('MIN(display_order)')
            ->orderBy('module')
            ->get();

        $synced = 0;

        foreach ($modules as $module) {
            PermissionModuleOption::query()->updateOrCreate(
                ['name' => $module->module],
                ['display_order' => (int) $module->display_order],
            );
            $synced++;
        }

        PermissionModuleOption::query()->firstOrCreate(
            ['name' => 'Other'],
            ['display_order' => 999],
        );

        return $synced;
    }

    public function renamePermissionModule(string $fromModule, string $toModule): int
    {
        $updated = Permission::query()
            ->where('module', $fromModule)
            ->update(['module' => $toModule]);

        $this->renamePermissionModuleOption($fromModule, $toModule);

        if ($updated > 0) {
            $this->clearPermissionCache();
        }

        return $updated;
    }

    public function movePermissionsToModule(string $fromModule, string $toModule): int
    {
        $updated = Permission::query()
            ->where('module', $fromModule)
            ->update(['module' => $toModule]);

        $this->deletePermissionModuleOption($fromModule);

        if ($updated > 0) {
            $this->clearPermissionCache();
        }

        return $updated;
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

    public function ensurePermissionModuleExists(string $module): void
    {
        $exists = $this->permissionModuleExists($module);

        if (! $exists) {
            abort(422, 'Selected permission module does not exist.');
        }
    }

    public function permissionModuleExists(string $module): bool
    {
        if ($module === 'Other') {
            return true;
        }

        return Permission::query()
            ->where('module', $module)
            ->exists()
            || PermissionModuleOption::query()->where('name', $module)->exists();
    }

    public function createPermissionModuleOption(array $attributes): void
    {
        PermissionModuleOption::query()->create($attributes);
    }

    public function renamePermissionModuleOption(string $fromModule, string $toModule): void
    {
        PermissionModuleOption::query()
            ->where('name', $fromModule)
            ->update(['name' => $toModule]);
    }

    public function deletePermissionModuleOption(string $module): void
    {
        PermissionModuleOption::query()
            ->where('name', $module)
            ->delete();
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
