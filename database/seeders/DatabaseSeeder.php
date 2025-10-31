<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ensure landlord migrations have run
        if (! Schema::connection('landlord')->hasTable('tenants')) {
            $this->command->warn('Landlord database tables not found. Running landlord migrations...');
            Artisan::call('migrate', [
                '--database' => 'landlord',
                '--path' => 'database/migrations/landlord',
                '--force' => true,
            ]);
            $this->command->info('Landlord migrations completed.');
        }

        $this->call([
            RoleSeeder::class,
            YearLevelSeeder::class,
            TenantSeeder::class,
            AdminSeeder::class,
            ResidentSeeder::class,
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
