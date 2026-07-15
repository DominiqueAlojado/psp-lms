<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Resident;
use App\Models\User;
use App\Services\OrganizationSettingsManagementService;
use App\Services\OrganizationSettingsReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OrganizationSettingsServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_read_service_builds_organization_settings_payload(): void
    {
        Permission::create(['name' => 'manage-organization-settings', 'guard_name' => 'web']);

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $user = User::factory()->create(['current_organization_id' => $organization->id]);
        $user->organizations()->attach($organization->id, ['joined_at' => now(), 'is_active' => true]);
        $user->givePermissionTo('manage-organization-settings');

        Resident::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'resident@example.com',
        ]);

        $service = app(OrganizationSettingsReadService::class);
        $payload = $service->indexPayload($user);

        $this->assertSame('Alpha Chapter', $payload['organization']['name']);
        $this->assertCount(1, $payload['residents']);
        $this->assertSame('resident@example.com', $payload['residents'][0]['email']);
    }

    public function test_management_service_updates_resident_and_linked_user(): void
    {
        Permission::create(['name' => 'manage-organization-settings', 'guard_name' => 'web']);

        $organization = Organization::create([
            'name' => 'Beta Chapter',
            'slug' => 'beta-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $admin = User::factory()->create(['current_organization_id' => $organization->id]);
        $admin->organizations()->attach($organization->id, ['joined_at' => now(), 'is_active' => true]);
        $admin->givePermissionTo('manage-organization-settings');

        $residentUser = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'current_organization_id' => $organization->id,
        ]);

        $resident = Resident::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $residentUser->id,
            'first_name' => 'Old',
            'middle_name' => 'Middle',
            'last_name' => 'Name',
            'email' => 'old@example.com',
            'contact_number' => '09111111111',
            'course' => 'Anatomic and Clinical Pathology',
            'year_level' => 'First Year',
            'status' => 'active',
        ]);

        $service = app(OrganizationSettingsManagementService::class);
        $service->updateResident($admin, $resident->id, [
            'first_name' => 'New',
            'middle_name' => 'Middle',
            'last_name' => 'Resident',
            'email' => 'new@example.com',
            'contact_number' => '09999999999',
            'course' => 'Clinical Pathology',
            'year_level' => 'Second Year',
            'status' => 'inactive',
            'password' => 'new-secret123',
        ]);

        $resident->refresh();
        $residentUser->refresh();

        $this->assertSame('new@example.com', $resident->email);
        $this->assertSame('New Middle Resident', $residentUser->name);
        $this->assertTrue(Hash::check('new-secret123', $residentUser->password));
    }
}
