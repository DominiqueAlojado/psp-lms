<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\RolesPermissionsRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsController extends Controller
{
    public function __construct(
        private readonly RolesPermissionsRepositoryInterface $rolesPermissionsRepository,
    ) {}

    /**
     * Display roles and permissions management page.
     */
    public function index(): Response
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

        // Group permissions by category
        $groupedPermissions = $permissions->groupBy('category')->map(function ($perms) {
            return $perms->values();
        });

        return Inertia::render('settings/roles-permissions', [
            'roles' => $roles,
            'permissions' => $permissions,
            'groupedPermissions' => $groupedPermissions,
        ]);
    }

    /**
     * Create a new role.
     */
    public function storeRole(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
        ]);

        $this->rolesPermissionsRepository->createRole([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        return back()->with('success', 'Role created successfully');
    }

    /**
     * Update a role.
     */
    public function updateRole(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,'.$role->id],
        ]);

        $this->rolesPermissionsRepository->updateRole($role, $validated);

        return back()->with('success', 'Role updated successfully');
    }

    /**
     * Delete a role.
     */
    public function deleteRole(Role $role): RedirectResponse
    {
        // Prevent deletion of critical roles
        if (in_array($role->name, ['System Admin', 'Admin', 'Resident'])) {
            return back()->with('error', 'Cannot delete system roles');
        }

        $this->rolesPermissionsRepository->deleteRole($role);

        return back()->with('success', 'Role deleted successfully');
    }

    /**
     * Create a new permission.
     */
    public function storePermission(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
            'category' => ['required', 'string', 'max:255'],
        ]);

        $this->rolesPermissionsRepository->createPermission([
            'name' => $validated['name'],
            'guard_name' => 'web',
            'category' => $validated['category'],
            'display_order' => 999, // Put new permissions at the end
        ]);

        return back()->with('success', 'Permission created successfully');
    }

    /**
     * Update a permission.
     */
    public function updatePermission(Request $request, Permission $permission): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name,'.$permission->id],
            'category' => ['required', 'string', 'max:255'],
        ]);

        $this->rolesPermissionsRepository->updatePermission($permission, $validated);

        return back()->with('success', 'Permission updated successfully');
    }

    /**
     * Delete a permission.
     */
    public function deletePermission(Permission $permission): RedirectResponse
    {
        $this->rolesPermissionsRepository->deletePermission($permission);

        return back()->with('success', 'Permission deleted successfully');
    }

    /**
     * Sync permissions for a role.
     */
    public function syncRolePermissions(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $this->rolesPermissionsRepository->syncRolePermissions($role, $validated['permissions']);

        return back()->with('success', 'Role permissions updated successfully');
    }
}
