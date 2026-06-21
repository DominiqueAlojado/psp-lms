<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OrganizationManagementFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_an_institution(): void
    {
        Permission::create(['name' => 'create-institutions', 'guard_name' => 'web']);

        $admin = User::factory()->create();
        $admin->givePermissionTo('create-institutions');

        $response = $this->actingAs($admin)
            ->from(route('institutions.index'))
            ->post(route('institutions.store'), [
                'name' => 'Alpha Chapter',
                'description' => 'Primary chapter',
                'type' => 'chapter',
                'training_officers' => '[1,2]',
            ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('institutions.index'));

        $organization = Organization::where('name', 'Alpha Chapter')->first();

        $this->assertNotNull($organization);
        $this->assertSame('alpha-chapter', $organization->slug);
        $this->assertSame([1, 2], $organization->training_officers);
    }

    public function test_authorized_user_can_update_an_institution(): void
    {
        Permission::create(['name' => 'edit-institutions', 'guard_name' => 'web']);

        $admin = User::factory()->create();
        $admin->givePermissionTo('edit-institutions');

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'description' => 'Primary chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('institutions.index'))
            ->patch(route('institutions.update', $organization), [
                'name' => 'Beta Institution',
                'description' => 'Updated description',
                'type' => 'institution',
                'is_active' => false,
                'training_officers' => '[3]',
            ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('institutions.index'));

        $organization->refresh();

        $this->assertSame('Beta Institution', $organization->name);
        $this->assertSame('beta-institution', $organization->slug);
        $this->assertFalse($organization->is_active);
        $this->assertSame([3], $organization->training_officers);
    }

    public function test_user_can_switch_current_organization(): void
    {
        $oldOrganization = Organization::create([
            'name' => 'Old Chapter',
            'slug' => 'old-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $newOrganization = Organization::create([
            'name' => 'New Chapter',
            'slug' => 'new-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $oldOrganization->id,
        ]);
        $user->organizations()->attach($oldOrganization->id, ['joined_at' => now(), 'is_active' => true]);
        $user->organizations()->attach($newOrganization->id, ['joined_at' => now(), 'is_active' => true]);

        $response = $this->actingAs($user)
            ->post(route('organization.switch', $newOrganization), []);

        $response->assertRedirect('/dashboard?org=new-chapter');
        $this->assertSame($newOrganization->id, $user->fresh()->current_organization_id);
    }
}
