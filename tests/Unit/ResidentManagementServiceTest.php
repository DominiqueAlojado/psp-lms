<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Resident;
use App\Models\User;
use App\Services\ResidentManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
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
        $this->assertSame(['passwordChanged' => true], $result);
    }
}
