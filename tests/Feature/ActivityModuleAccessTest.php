<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ActivityModuleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_permission_can_access_activity_module(): void
    {
        Permission::findOrCreate('view-activity-logs', 'web');

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $user->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);
        $user->givePermissionTo('view-activity-logs');

        $this->actingAs($user)
            ->get(route('activities.index'))
            ->assertOk();
    }

    public function test_user_without_permission_cannot_access_activity_module(): void
    {
        Permission::findOrCreate('view-activity-logs', 'web');

        $organization = Organization::create([
            'name' => 'Beta Chapter',
            'slug' => 'beta-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $user->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('activities.index'))
            ->assertStatus(302);
    }
}
