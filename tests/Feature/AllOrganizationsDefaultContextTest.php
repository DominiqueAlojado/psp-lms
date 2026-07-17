<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AllOrganizationsDefaultContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_admin_defaults_to_all_organizations_on_supported_pages(): void
    {
        $organization = $this->createOrganization('alpha-hospital', 'Alpha Hospital');
        $user = $this->createUserWithRole($organization, 'System Admin');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/dashboard?org=all-organizations');
    }

    public function test_system_admin_uses_real_organization_on_blocked_pages(): void
    {
        $organization = $this->createOrganization('alpha-hospital', 'Alpha Hospital');
        $user = $this->createUserWithRole($organization, 'System Admin');

        $this->actingAs($user)
            ->get('/settings/profile')
            ->assertRedirect('/settings/profile?org=alpha-hospital');
    }

    public function test_resident_does_not_default_to_all_organizations(): void
    {
        $organization = $this->createOrganization('beta-hospital', 'Beta Hospital');
        $user = $this->createUserWithRole($organization, 'Resident');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/dashboard?org=beta-hospital');
    }

    private function createOrganization(string $slug, string $name): Organization
    {
        return Organization::create([
            'name' => $name,
            'slug' => $slug,
            'type' => 'institution',
            'is_active' => true,
        ]);
    }

    private function createUserWithRole(Organization $organization, string $roleName): User
    {
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $user->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        $role = Role::findOrCreate($roleName, 'web');
        $user->assignRole($role);

        return $user;
    }
}
