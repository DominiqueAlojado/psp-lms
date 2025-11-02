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
            // ResidentSeeder::class,    // Uncomment to seed residents
        ]);

        // User::factory(10)->create();

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );
    }
}
