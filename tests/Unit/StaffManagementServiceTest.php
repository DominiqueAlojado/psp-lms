<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\User;
use App\Services\StaffManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_staff_with_roles_and_organizations(): void
    {
        $service = app(StaffManagementService::class);

        $role = Role::create([
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $user = $service->create([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
            'password' => 'secret123',
            'roles' => [$role->id],
            'organizations' => [$organization->id],
            'current_organization_id' => $organization->id,
        ]);

        $this->assertSame('Staff User', $user->name);
        $this->assertSame('staff@example.com', $user->email);
        $this->assertSame($organization->id, $user->current_organization_id);
        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertTrue($user->fresh()->roles->contains('id', $role->id));
        $this->assertTrue($user->fresh()->organizations->contains('id', $organization->id));
    }

    public function test_it_updates_staff_and_returns_logging_context(): void
    {
        $service = app(StaffManagementService::class);

        $oldRole = Role::create([
            'name' => 'Coordinator',
            'guard_name' => 'web',
        ]);
        $newRole = Role::create([
            'name' => 'Manager',
            'guard_name' => 'web',
        ]);

        $oldOrganization = Organization::create([
            'name' => 'Beta Chapter',
            'slug' => 'beta-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $newOrganization = Organization::create([
            'name' => 'Gamma Chapter',
            'slug' => 'gamma-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $staff = User::factory()->create([
            'current_organization_id' => $oldOrganization->id,
        ]);
        $staff->syncRoles([$oldRole->id]);
        $staff->organizations()->attach($oldOrganization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        $result = $service->update($staff, [
            'name' => 'Updated Staff',
            'email' => 'updated-staff@example.com',
            'password' => 'new-secret123',
            'roles' => [$newRole->id],
            'organizations' => [$newOrganization->id],
            'current_organization_id' => $newOrganization->id,
        ]);

        $staff->refresh();

        $this->assertSame('Updated Staff', $staff->name);
        $this->assertSame('updated-staff@example.com', $staff->email);
        $this->assertSame($newOrganization->id, $staff->current_organization_id);
        $this->assertTrue(Hash::check('new-secret123', $staff->password));
        $this->assertTrue($staff->roles->contains('id', $newRole->id));
        $this->assertTrue($staff->organizations->contains('id', $newOrganization->id));
        $this->assertSame([
            'passwordChanged' => true,
            'newRoles' => ['Manager'],
            'newOrganizations' => ['Gamma Chapter'],
        ], $result);
    }

    public function test_it_deletes_staff(): void
    {
        $service = app(StaffManagementService::class);

        $staff = User::factory()->create();

        $this->assertTrue($service->delete($staff));
        $this->assertDatabaseMissing('users', [
            'id' => $staff->id,
        ]);
    }
}
