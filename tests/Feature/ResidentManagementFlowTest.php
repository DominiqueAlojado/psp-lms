<?php

namespace Tests\Feature;

use App\Http\Middleware\SetOrganizationFromUrl;
use App\Models\Organization;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
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
}
