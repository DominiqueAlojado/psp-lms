<?php

namespace Tests\Feature\Settings;

use App\Repositories\Contracts\RolesPermissionsRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesPermissionsRepositoryFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_and_permissions_crud_flows_work_through_repository(): void
    {
        $repository = app(RolesPermissionsRepositoryInterface::class);

        $role = $repository->createRole([
            'name' => 'Reviewer',
            'guard_name' => 'web',
        ]);

        $permission = $repository->createPermission([
            'name' => 'review-submissions',
            'guard_name' => 'web',
            'module' => 'Assessments',
            'display_order' => 10,
        ]);

        $repository->syncRolePermissions($role, [$permission->id]);

        $this->assertDatabaseHas('roles', ['name' => 'Reviewer']);
        $this->assertDatabaseHas('permissions', ['name' => 'review-submissions']);
        $this->assertTrue($role->fresh()->permissions->contains('id', $permission->id));

        $repository->updateRole($role, ['name' => 'Senior Reviewer']);
        $repository->updatePermission($permission, ['name' => 'review-submissions-advanced']);
        $repository->renamePermissionModule('Assessments', 'Exam Tools');

        $this->assertDatabaseHas('roles', ['name' => 'Senior Reviewer']);
        $this->assertDatabaseHas('permissions', [
            'name' => 'review-submissions-advanced',
            'module' => 'Exam Tools',
        ]);

        $repository->movePermissionsToModule('Exam Tools', 'Other');
        $repository->deletePermission($permission->fresh());
        $repository->deleteRole($role);

        $this->assertDatabaseMissing('permissions', ['name' => 'review-submissions-advanced']);
        $this->assertDatabaseMissing('roles', ['name' => 'Senior Reviewer']);
    }
}
