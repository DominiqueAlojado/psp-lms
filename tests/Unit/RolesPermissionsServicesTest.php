<?php

namespace Tests\Unit;

use App\Models\PermissionModuleOption;
use App\Services\RolesPermissionsManagementService;
use App\Services\RolesPermissionsReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RolesPermissionsServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_read_service_builds_roles_permissions_payload(): void
    {
        $role = Role::create(['name' => 'Reviewer', 'guard_name' => 'web']);
        $permission = Permission::create([
            'name' => 'review-submissions',
            'guard_name' => 'web',
            'module' => 'Assessments',
            'display_order' => 10,
        ]);
        $role->givePermissionTo($permission);

        $service = app(RolesPermissionsReadService::class);
        $payload = $service->indexPayload();

        $this->assertCount(1, $payload['roles']);
        $this->assertSame('Reviewer', $payload['roles'][0]['name']);
        $this->assertContains(
            'Assessments',
            collect($payload['modules'])->pluck('name')->all(),
        );
        $this->assertArrayHasKey('Assessments', $payload['groupedPermissions']->toArray());
    }

    public function test_read_service_includes_added_module_without_permissions(): void
    {
        PermissionModuleOption::create([
            'name' => 'Compliance',
            'display_order' => 999,
        ]);

        $payload = app(RolesPermissionsReadService::class)->indexPayload();

        $this->assertContains(
            'Compliance',
            collect($payload['modules'])->pluck('name')->all(),
        );
    }

    public function test_management_service_blocks_system_role_deletion(): void
    {
        $service = app(RolesPermissionsManagementService::class);
        $role = Role::create(['name' => 'Admin', 'guard_name' => 'web']);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Cannot delete system roles');

        $service->deleteRole($role);
    }

    public function test_management_service_can_rename_permission_module(): void
    {
        $service = app(RolesPermissionsManagementService::class);

        Permission::create([
            'name' => 'review-submissions',
            'guard_name' => 'web',
            'module' => 'Assessments',
            'display_order' => 10,
        ]);

        $service->renamePermissionModule('Assessments', 'Exam Tools');

        $this->assertDatabaseHas('permissions', [
            'name' => 'review-submissions',
            'module' => 'Exam Tools',
        ]);
    }

    public function test_management_service_deletes_module_by_moving_permissions_to_other(): void
    {
        $service = app(RolesPermissionsManagementService::class);

        Permission::create([
            'name' => 'review-submissions',
            'guard_name' => 'web',
            'module' => 'Assessments',
            'display_order' => 10,
        ]);

        $service->deletePermissionModule('Assessments');

        $this->assertDatabaseHas('permissions', [
            'name' => 'review-submissions',
            'module' => 'Other',
        ]);
    }

    public function test_management_service_can_create_empty_permission_module(): void
    {
        $service = app(RolesPermissionsManagementService::class);

        $service->createPermissionModule('Compliance');

        $this->assertDatabaseHas('permission_module_options', [
            'name' => 'Compliance',
        ]);
    }
}
