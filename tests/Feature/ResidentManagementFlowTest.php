<?php

namespace Tests\Feature;

use App\Http\Middleware\SetOrganizationFromUrl;
use App\Models\Organization;
use App\Models\Resident;
use App\Models\ResidentOrganizationMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ResidentManagementFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_resident(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        Permission::create(['name' => 'create-residents', 'guard_name' => 'web']);
        Role::create(['name' => 'Resident', 'guard_name' => 'web']);

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
        $admin->givePermissionTo('create-residents');

        $response = $this->actingAs($admin)
            ->from(route('residents.index'))
            ->post(route('residents.store'), [
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

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('residents.index'));

        $resident = Resident::where('email', 'resident@example.com')->first();

        $this->assertNotNull($resident);
        $this->assertSame($organization->id, $resident->organization_id);
        $this->assertNotNull($resident->user);
        $this->assertSame('Jane Santos Doe', $resident->user->name);
        $this->assertTrue(Hash::check('secret123', $resident->user->password));
        $this->assertTrue($resident->user->roles->contains('name', 'Resident'));
        $this->assertTrue($resident->user->organizations->contains('id', $organization->id));
    }

    public function test_authorized_user_can_update_resident(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        Permission::create(['name' => 'edit-residents', 'guard_name' => 'web']);

        $organization = Organization::create([
            'name' => 'Beta Chapter',
            'slug' => 'beta-chapter',
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
        $admin->givePermissionTo('edit-residents');

        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'current_organization_id' => $organization->id,
        ]);

        $resident = Resident::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'first_name' => 'Old',
            'middle_name' => 'Middle',
            'last_name' => 'Name',
            'email' => 'old@example.com',
            'contact_number' => '09111111111',
            'course' => 'Anatomic and Clinical Pathology',
            'year_level' => 'First Year',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('residents.index'))
            ->patch(route('residents.update', $resident), [
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

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('residents.index'));

        $resident->refresh();
        $user->refresh();

        $this->assertSame('New', $resident->first_name);
        $this->assertSame('new@example.com', $resident->email);
        $this->assertSame('Second Year', $resident->year_level);
        $this->assertSame('inactive', $resident->status);
        $this->assertSame('New Middle Resident', $user->name);
        $this->assertSame('new@example.com', $user->email);
        $this->assertTrue(Hash::check('new-secret123', $user->password));
    }

    public function test_authorized_user_can_transfer_resident_to_another_organization(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        Permission::create(['name' => 'edit-residents', 'guard_name' => 'web']);

        $oldOrganization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
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
        $admin->organizations()->attach($oldOrganization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);
        $admin->givePermissionTo('edit-residents');

        $user = User::factory()->create([
            'name' => 'Transfer Resident',
            'email' => 'transfer@example.com',
            'current_organization_id' => $oldOrganization->id,
        ]);
        $user->organizations()->attach($oldOrganization->id, [
            'joined_at' => now()->subMonth(),
            'is_active' => true,
        ]);

        $resident = Resident::factory()->create([
            'organization_id' => $oldOrganization->id,
            'user_id' => $user->id,
            'first_name' => 'Transfer',
            'middle_name' => 'Old',
            'last_name' => 'Resident',
            'email' => 'transfer@example.com',
            'contact_number' => '09111111111',
            'course' => 'Anatomic and Clinical Pathology',
            'year_level' => 'First Year',
            'status' => 'active',
        ]);

        ResidentOrganizationMembership::create([
            'resident_id' => $resident->id,
            'organization_id' => $oldOrganization->id,
            'started_at' => now()->subMonth(),
            'ended_at' => null,
            'is_primary' => true,
            'year_level' => 'First Year',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('residents.index'))
            ->patch(route('residents.update', $resident), [
                'organization_id' => $newOrganization->id,
                'first_name' => 'Transfer',
                'middle_name' => 'Old',
                'last_name' => 'Resident',
                'email' => 'transfer@example.com',
                'contact_number' => '09111111111',
                'course' => 'Anatomic and Clinical Pathology',
                'year_level' => 'First Year',
                'status' => 'active',
                'password' => '',
                'password_confirmation' => '',
            ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('residents.index'));

        $resident->refresh();
        $user->refresh();

        $this->assertSame($newOrganization->id, $resident->organization_id);
        $this->assertSame($newOrganization->id, $user->current_organization_id);
        $this->assertDatabaseHas('resident_organization_memberships', [
            'resident_id' => $resident->id,
            'organization_id' => $newOrganization->id,
            'is_primary' => true,
            'ended_at' => null,
        ]);

        $activity = Activity::query()
            ->where('log_name', 'residents')
            ->where('subject_type', Resident::class)
            ->where('subject_id', $resident->id)
            ->latest()
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('Resident transferred', $activity->description);
        $this->assertSame('Alpha Chapter', $activity->properties['old']['organization_name']);
        $this->assertSame('Gamma Chapter', $activity->properties['attributes']['organization_name']);
    }
}
