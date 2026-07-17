<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\RolesPermissionsManagementService;
use App\Services\RolesPermissionsReadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsController extends Controller
{
    public function __construct(
        private readonly RolesPermissionsReadService $readService,
        private readonly RolesPermissionsManagementService $managementService,
    ) {}

    /**
     * Display roles and permissions management page.
     */
    public function index(): Response
    {
        return Inertia::render('settings/roles-permissions', $this->readService->indexPayload());
    }

    /**
     * Create a new role.
     */
    public function storeRole(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
        ]);

        $this->managementService->createRole($validated);

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

        $this->managementService->updateRole($role, $validated);

        return back()->with('success', 'Role updated successfully');
    }

    /**
     * Delete a role.
     */
    public function deleteRole(Role $role): RedirectResponse
    {
        try {
            $this->managementService->deleteRole($role);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            if ($exception->getStatusCode() === 422) {
                return back()->with('error', 'Cannot delete system roles');
            }

            throw $exception;
        }

        return back()->with('success', 'Role deleted successfully');
    }

    /**
     * Create a new permission.
     */
    public function storePermission(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
            'module' => ['required', 'string', 'max:255'],
        ]);

        $this->managementService->createPermission($validated);

        return back()->with('success', 'Permission created successfully');
    }

    /**
     * Update a permission.
     */
    public function updatePermission(Request $request, Permission $permission): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name,'.$permission->id],
            'module' => ['required', 'string', 'max:255'],
        ]);

        $this->managementService->updatePermission($permission, $validated);

        return back()->with('success', 'Permission updated successfully');
    }

    /**
     * Delete a permission.
     */
    public function deletePermission(Permission $permission): RedirectResponse
    {
        $this->managementService->deletePermission($permission);

        return back()->with('success', 'Permission deleted successfully');
    }

    public function storePermissionModule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->managementService->createPermissionModule($validated['name']);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            if ($exception->getStatusCode() === 422) {
                return back()->with('error', $exception->getMessage());
            }

            throw $exception;
        }

        return back()->with('success', 'Permission module created successfully');
    }

    public function renamePermissionModule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->managementService->renamePermissionModule(
                $validated['current_name'],
                $validated['name'],
            );
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            if (in_array($exception->getStatusCode(), [404, 422], true)) {
                return back()->with('error', $exception->getMessage());
            }

            throw $exception;
        }

        return back()->with('success', 'Permission module updated successfully');
    }

    public function deletePermissionModule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->managementService->deletePermissionModule($validated['name']);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            if (in_array($exception->getStatusCode(), [404, 422], true)) {
                return back()->with('error', $exception->getMessage());
            }

            throw $exception;
        }

        return back()->with('success', 'Permission module deleted successfully');
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

        $this->managementService->syncRolePermissions($role, $validated['permissions']);

        return back()->with('success', 'Role permissions updated successfully');
    }
}
