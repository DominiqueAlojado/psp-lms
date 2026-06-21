<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_organization_with_normalized_attributes(): void
    {
        $service = app(OrganizationManagementService::class);

        $organization = $service->create([
            'name' => 'Alpha Chapter',
            'description' => 'Primary chapter',
            'type' => 'chapter',
            'training_officers' => '[1,2]',
        ]);

        $this->assertSame('alpha-chapter', $organization->slug);
        $this->assertTrue($organization->is_active);
        $this->assertSame([1, 2], $organization->training_officers);
    }

    public function test_it_updates_an_organization_and_returns_normalized_attributes(): void
    {
        $service = app(OrganizationManagementService::class);

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'description' => 'Old description',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $attributes = $service->update($organization, [
            'name' => 'Beta Institution',
            'description' => 'New description',
            'type' => 'institution',
            'is_active' => false,
            'training_officers' => '[3]',
        ]);

        $organization->refresh();

        $this->assertSame('beta-institution', $organization->slug);
        $this->assertFalse($organization->is_active);
        $this->assertSame([3], $organization->training_officers);
        $this->assertSame('beta-institution', $attributes['slug']);
        $this->assertSame([3], $attributes['training_officers']);
    }

    public function test_it_deletes_an_organization(): void
    {
        $service = app(OrganizationManagementService::class);

        $organization = Organization::create([
            'name' => 'Delete Me',
            'slug' => 'delete-me',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $this->assertTrue($service->delete($organization));
        $this->assertSoftDeleted('organizations', ['id' => $organization->id]);
    }

    public function test_it_switches_the_users_current_organization(): void
    {
        $service = app(OrganizationManagementService::class);

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

        $this->assertTrue($service->switchUserOrganization($user, $newOrganization));
        $this->assertSame($newOrganization->id, $user->fresh()->current_organization_id);
    }
}
