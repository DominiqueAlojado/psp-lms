<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed in correct order
        $this->call([
            OrganizationSeeder::class,  // Create organizations
            RoleSeeder::class,           // Create roles
            PermissionSeeder::class,     // Create permissions
            RolePermissionSeeder::class, // Assign permissions to roles
            SystemAdminSeeder::class,    // Create system admin user
            ResidentSeeder::class,       // Seed residents with user accounts
        ]);

        // User::factory(10)->create();

        // Create test user if not exists
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'uuid' => \Illuminate\Support\Str::uuid(),
                'name' => 'Test User',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );
    }
}
