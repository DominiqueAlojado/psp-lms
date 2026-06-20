<?php

namespace Tests\Feature\Settings;

use App\Http\Middleware\SetOrganizationFromUrl;
use App\Models\Organization;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OrganizationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_view_organization_settings(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        Permission::create(['name' => 'manage-organization-settings', 'guard_name' => 'web']);

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
        $user->givePermissionTo('manage-organization-settings');

        Resident::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'resident@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('organization.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/organization')
                ->where('organization.name', 'Alpha Chapter')
                ->has('residents', 1)
                ->where('residents.0.email', 'resident@example.com')
            );
    }

    public function test_authorized_user_can_update_organization_resident_and_linked_user(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        Permission::create(['name' => 'manage-organization-settings', 'guard_name' => 'web']);

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

        $response = $this->actingAs($admin)
            ->from(route('organization.edit'))
            ->patch(route('organization.residents.update', $resident), [
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
            ->assertRedirect(route('organization.edit'));

        $resident->refresh();
        $residentUser->refresh();

        $this->assertSame('New', $resident->first_name);
        $this->assertSame('new@example.com', $resident->email);
        $this->assertSame('Second Year', $resident->year_level);
        $this->assertSame('inactive', $resident->status);
        $this->assertSame('new@example.com', $residentUser->email);
        $this->assertSame('New Middle Resident', $residentUser->name);
        $this->assertTrue(Hash::check('new-secret123', $residentUser->password));
    }
}
