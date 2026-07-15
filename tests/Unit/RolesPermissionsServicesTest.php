<?php

namespace Tests\Unit;

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
            'category' => 'Assessments',
            'display_order' => 10,
        ]);
        $role->givePermissionTo($permission);

        $service = app(RolesPermissionsReadService::class);
        $payload = $service->indexPayload();

        $this->assertCount(1, $payload['roles']);
        $this->assertSame('Reviewer', $payload['roles'][0]['name']);
        $this->assertArrayHasKey('Assessments', $payload['groupedPermissions']->toArray());
    }

    public function test_management_service_blocks_system_role_deletion(): void
    {
        $service = app(RolesPermissionsManagementService::class);
        $role = Role::create(['name' => 'Admin', 'guard_name' => 'web']);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Cannot delete system roles');

        $service->deleteRole($role);
    }
}
