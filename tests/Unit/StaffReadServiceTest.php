<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\User;
use App\Services\StaffReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffReadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_index_payload(): void
    {
        $service = app(StaffReadService::class);

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $role = Role::create([
            'name' => 'Coordinator',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $admin->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        $staff = User::factory()->create([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
            'current_organization_id' => $organization->id,
        ]);
        $staff->syncRoles([$role->id]);
        $staff->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        $payload = $service->indexPayload($admin, []);
        $staffItem = collect($payload['staff']->items())
            ->firstWhere('email', 'staff@example.com');

        $this->assertNotNull($staffItem);
        $this->assertSame('Staff User', $staffItem['name']);
        $this->assertSame('Coordinator', $staffItem['primary_role']);
        $this->assertSame('Alpha Chapter', $staffItem['current_organization']);
        $this->assertStringContainsString('T', $staffItem['updated_at']);
        $this->assertTrue($payload['roleStats']->contains(fn ($stat) => $stat['role'] === 'Coordinator' && $stat['count'] >= 1));
        $this->assertTrue($payload['roles']->contains('name', 'Coordinator'));
        $this->assertTrue($payload['organizations']->contains('name', 'Alpha Chapter'));
    }

    public function test_it_builds_show_payload(): void
    {
        $service = app(StaffReadService::class);

        $organization = Organization::create([
            'name' => 'Beta Chapter',
            'slug' => 'beta-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $otherOrganization = Organization::create([
            'name' => 'Gamma Chapter',
            'slug' => 'gamma-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $role = Role::create([
            'name' => 'Manager',
            'guard_name' => 'web',
        ]);

        $staff = User::factory()->create([
            'name' => 'Shown Staff',
            'email' => 'shown-staff@example.com',
            'current_organization_id' => $organization->id,
        ]);
        $staff->syncRoles([$role->id]);
        $staff->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);
        $staff->organizations()->attach($otherOrganization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        $payload = $service->showPayload($staff);

        $this->assertSame($staff->id, $payload['staff']['id']);
        $this->assertSame('shown-staff@example.com', $payload['staff']['email']);
        $this->assertSame([$role->id], $payload['staff']['roles']);
        $this->assertSame(['Manager'], $payload['staff']['role_names']);
        $this->assertCount(2, $payload['staff']['organizations']);
    }

    public function test_non_system_admin_payload_excludes_protected_roles(): void
    {
        $service = app(StaffReadService::class);

        $organization = Organization::create([
            'name' => 'Delta Chapter',
            'slug' => 'delta-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        Role::create([
            'name' => 'System Admin',
            'guard_name' => 'web',
        ]);
        Role::create([
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);
        Role::create([
            'name' => 'Training Officer',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $user->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        $payload = $service->indexPayload($user, []);

        $this->assertFalse($payload['roles']->contains('name', 'System Admin'));
        $this->assertFalse($payload['roles']->contains('name', 'Admin'));
        $this->assertTrue($payload['roles']->contains('name', 'Training Officer'));
    }
}
