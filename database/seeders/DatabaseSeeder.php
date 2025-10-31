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
        // This is expected behavior when running migrate:fresh - landlord migrations need to run separately
        if (! Schema::connection('landlord')->hasTable('tenants')) {
            $this->command->info('Landlord database tables not found. Running landlord migrations automatically...');

            try {
                $exitCode = Artisan::call('migrate', [
                    '--database' => 'landlord',
                    '--path' => 'database/migrations/landlord',
                    '--force' => true,
                ]);

                if ($exitCode !== 0) {
                    $output = Artisan::output();
                    $this->command->error('Landlord migrations failed:');
                    $this->command->error($output);
                    throw new \RuntimeException('Failed to run landlord migrations. Exit code: '.$exitCode);
                }

                $this->command->info('Landlord migrations completed.');
            } catch (\Exception $e) {
                $this->command->error('Error running landlord migrations: '.$e->getMessage());
                throw $e;
            }
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
