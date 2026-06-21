<?php

namespace Tests\Feature\Auth;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
    }

    public function test_new_users_can_register()
    {
        Role::create([
            'name' => 'Resident',
            'guard_name' => 'web',
        ]);

        $organization = Organization::create([
            'name' => 'Test Hospital',
            'slug' => 'test-hospital',
            'description' => 'Registration test organization',
            'type' => 'institution',
            'is_active' => true,
        ]);

        $response = $this->post(route('register.store'), [
            'first_name' => 'Test',
            'middle_name' => 'Q',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'organization_id' => $organization->id,
            'contact_number' => '09123456789',
            'course' => 'BS Nursing',
            'year_level' => 'First Year',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
