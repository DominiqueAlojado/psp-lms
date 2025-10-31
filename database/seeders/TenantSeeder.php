<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a tenant for localhost development
        Tenant::firstOrCreate(
            ['domain' => 'localhost'],
            [
                'name' => 'Local Development',
                'domain' => 'localhost',
                'database' => env('DB_DATABASE', 'laravel'),
            ]
        );

        // Also create for 127.0.0.1
        Tenant::firstOrCreate(
            ['domain' => '127.0.0.1'],
            [
                'name' => 'Local Development (IP)',
                'domain' => '127.0.0.1',
                'database' => env('DB_DATABASE', 'laravel'),
            ]
        );
    }
}
