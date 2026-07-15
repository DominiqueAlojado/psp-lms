<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Resident;
use App\Models\ResidentOrganizationMembership;
use App\Models\User;
use App\Services\ResidentManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ResidentManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_resident_with_linked_user_and_role(): void
    {
        $service = app(ResidentManagementService::class);

        Role::create([
            'name' => 'Resident',
            'guard_name' => 'web',
        ]);

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $resident = $service->create([
            'organization_id' => $organization->id,
            'first_name' => 'Jane',
            'middle_name' => 'Santos',
            'last_name' => 'Doe',
            'email' => 'resident@example.com',
            'contact_number' => '09123456789',
            'course' => 'Anatomic and Clinical Pathology',
            'year_level' => 'First Year',
            'status' => 'active',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $resident->refresh();
        $user = $resident->user;

        $this->assertNotNull($user);
        $this->assertSame($organization->id, $resident->organization_id);
        $this->assertSame($user->id, $resident->user_id);
        $this->assertSame($organization->id, $user->current_organization_id);
        $this->assertSame('Jane Santos Doe', $user->name);
        $this->assertSame('resident@example.com', $user->email);
        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertTrue($user->roles->contains('name', 'Resident'));
        $this->assertTrue($user->organizations->contains('id', $organization->id));
        $this->assertDatabaseHas('resident_organization_memberships', [
            'resident_id' => $resident->id,
            'organization_id' => $organization->id,
            'is_primary' => true,
            'ended_at' => null,
        ]);
    }

    public function test_it_updates_resident_and_linked_user_details(): void
    {
        $service = app(ResidentManagementService::class);

        $organization = Organization::create([
            'name' => 'Beta Chapter',
            'slug' => 'beta-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $resident = Resident::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'first_name' => 'Old',
            'middle_name' => 'Middle',
            'last_name' => 'Name',
            'email' => 'old@example.com',
        ]);

        $result = $service->update($resident, [
            'first_name' => 'New',
            'middle_name' => 'Middle',
            'last_name' => 'Resident',
            'email' => 'new@example.com',
            'contact_number' => '09999999999',
            'course' => 'Clinical Pathology',
            'year_level' => 'Second Year',
            'status' => 'inactive',
            'password' => 'new-secret123',
            'password_confirmation' => 'new-secret123',
        ]);

        $resident->refresh();
        $user->refresh();

        $this->assertSame('New', $resident->first_name);
        $this->assertSame('new@example.com', $resident->email);
        $this->assertSame('Second Year', $resident->year_level);
        $this->assertSame('inactive', $resident->status);
        $this->assertSame('New Middle Resident', $user->name);
        $this->assertSame('new@example.com', $user->email);
        $this->assertTrue(Hash::check('new-secret123', $user->password));
        $this->assertSame([
            'passwordChanged' => true,
            'organizationChanged' => false,
        ], $result);
    }

    public function test_it_transfers_a_resident_and_preserves_membership_history(): void
    {
        $service = app(ResidentManagementService::class);

        $oldOrganization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $newOrganization = Organization::create([
            'name' => 'Beta Chapter',
            'slug' => 'beta-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $oldOrganization->id,
            'name' => 'Transfer Me',
            'email' => 'transfer@example.com',
        ]);
        $user->organizations()->attach($oldOrganization->id, [
            'joined_at' => now()->subMonth(),
            'is_active' => true,
        ]);

        $resident = Resident::factory()->create([
            'organization_id' => $oldOrganization->id,
            'user_id' => $user->id,
            'first_name' => 'Transfer',
            'middle_name' => null,
            'last_name' => 'Resident',
            'email' => 'transfer@example.com',
            'year_level' => 'Second Year',
            'status' => 'active',
        ]);

        ResidentOrganizationMembership::create([
            'resident_id' => $resident->id,
            'organization_id' => $oldOrganization->id,
            'started_at' => now()->subMonth(),
            'ended_at' => null,
            'is_primary' => true,
            'year_level' => 'Second Year',
            'status' => 'active',
        ]);

        $result = $service->update($resident, [
            'organization_id' => $newOrganization->id,
            'first_name' => 'Transfer',
            'middle_name' => null,
            'last_name' => 'Resident',
            'email' => 'transfer@example.com',
            'contact_number' => '09999999999',
            'course' => $resident->course,
            'year_level' => 'Second Year',
            'status' => 'active',
        ]);

        $resident->refresh();
        $user->refresh();

        $this->assertSame($newOrganization->id, $resident->organization_id);
        $this->assertSame($newOrganization->id, $user->current_organization_id);
        $this->assertTrue($result['organizationChanged']);

        $oldMembership = ResidentOrganizationMembership::query()
            ->where('resident_id', $resident->id)
            ->where('organization_id', $oldOrganization->id)
            ->latest('id')
            ->firstOrFail();
        $newMembership = ResidentOrganizationMembership::query()
            ->where('resident_id', $resident->id)
            ->where('organization_id', $newOrganization->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertNotNull($oldMembership->ended_at);
        $this->assertFalse($oldMembership->is_primary);
        $this->assertNull($newMembership->ended_at);
        $this->assertTrue($newMembership->is_primary);
        $this->assertTrue($user->organizations()->where('organizations.id', $newOrganization->id)->exists());
    }

    public function test_transfer_log_data_contains_organization_names(): void
    {
        $organizationA = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $organizationB = Organization::create([
            'name' => 'Beta Chapter',
            'slug' => 'beta-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $resident = Resident::factory()->create([
            'organization_id' => $organizationA->id,
        ]);

        $logData = app(\App\Services\ActivityLog\ResidentActivityLogService::class)->buildUpdateLogData(
            $resident,
            [
                'organization_id' => $organizationB->id,
                'first_name' => $resident->first_name,
                'middle_name' => $resident->middle_name,
                'last_name' => $resident->last_name,
                'email' => $resident->email,
                'contact_number' => $resident->contact_number,
                'course' => $resident->course,
                'year_level' => $resident->year_level,
                'status' => $resident->status,
            ],
            $resident->first_name,
            $resident->middle_name,
            $resident->last_name,
            $resident->email,
            $resident->contact_number,
            $resident->course,
            $resident->year_level,
            $resident->status,
            $organizationA->id,
            false,
        );

        $this->assertSame('Beta Chapter', $logData['attributes']['organization_name']);
        $this->assertSame('Alpha Chapter', $logData['oldValues']['organization_name']);
    }
}
