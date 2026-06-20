<?php

namespace Tests\Feature;

use App\Http\Middleware\SetOrganizationFromUrl;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffManagementFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_staff_member(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        Permission::create(['name' => 'create-staff', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'Admin', 'guard_name' => 'web']);

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $admin = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $admin->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);
        $admin->givePermissionTo('create-staff');

        $response = $this->actingAs($admin)
            ->from(route('staff.index'))
            ->post(route('staff.store'), [
                'name' => 'Staff User',
                'email' => 'staff@example.com',
                'password' => 'secret123',
                'roles' => [$role->id],
                'organizations' => [$organization->id],
                'current_organization_id' => $organization->id,
            ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('staff.index'));

        $staff = User::where('email', 'staff@example.com')->first();

        $this->assertNotNull($staff);
        $this->assertSame('Staff User', $staff->name);
        $this->assertSame($organization->id, $staff->current_organization_id);
        $this->assertTrue(Hash::check('secret123', $staff->password));
        $this->assertTrue($staff->roles->contains('id', $role->id));
        $this->assertTrue($staff->organizations->contains('id', $organization->id));
    }

    public function test_authorized_user_can_update_staff_member(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        Permission::create(['name' => 'edit-staff', 'guard_name' => 'web']);
        $oldRole = Role::create(['name' => 'Coordinator', 'guard_name' => 'web']);
        $newRole = Role::create(['name' => 'Manager', 'guard_name' => 'web']);

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

        $admin = User::factory()->create([
            'current_organization_id' => $oldOrganization->id,
        ]);
        $admin->organizations()->attach([$oldOrganization->id => [
            'joined_at' => now(),
            'is_active' => true,
        ]]);
        $admin->givePermissionTo('edit-staff');

        $staff = User::factory()->create([
            'name' => 'Old Staff',
            'email' => 'old-staff@example.com',
            'current_organization_id' => $oldOrganization->id,
        ]);
        $staff->syncRoles([$oldRole->id]);
        $staff->organizations()->attach([$oldOrganization->id => [
            'joined_at' => now(),
            'is_active' => true,
        ]]);

        $response = $this->actingAs($admin)
            ->from(route('staff.index'))
            ->patch(route('staff.update', $staff), [
                'name' => 'Updated Staff',
                'email' => 'updated-staff@example.com',
                'password' => 'new-secret123',
                'roles' => [$newRole->id],
                'organizations' => [$newOrganization->id],
                'current_organization_id' => $newOrganization->id,
            ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('staff.index'));

        $staff->refresh();

        $this->assertSame('Updated Staff', $staff->name);
        $this->assertSame('updated-staff@example.com', $staff->email);
        $this->assertSame($newOrganization->id, $staff->current_organization_id);
        $this->assertTrue(Hash::check('new-secret123', $staff->password));
        $this->assertTrue($staff->roles->contains('id', $newRole->id));
        $this->assertTrue($staff->organizations->contains('id', $newOrganization->id));
    }
}
