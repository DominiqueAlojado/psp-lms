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
        $hospitals = [
            ['name' => 'City General Hospital', 'domain' => 'citygeneral.test', 'database' => 'citygeneral'],
            ['name' => 'Metropolitan Medical Center', 'domain' => 'metropolitan.test', 'database' => 'metropolitan'],
            ['name' => 'Riverside Community Hospital', 'domain' => 'riverside.test', 'database' => 'riverside'],
            ['name' => 'Memorial Hospital', 'domain' => 'memorial.test', 'database' => 'memorial'],
            ['name' => 'St. Mary\'s Hospital', 'domain' => 'stmarys.test', 'database' => 'stmarys'],
            ['name' => 'University Medical Center', 'domain' => 'university.test', 'database' => 'university'],
            ['name' => 'Regional Hospital', 'domain' => 'regional.test', 'database' => 'regional'],
            ['name' => 'Central Medical Center', 'domain' => 'central.test', 'database' => 'central'],
            ['name' => 'Westside Hospital', 'domain' => 'westside.test', 'database' => 'westside'],
            ['name' => 'Eastside Medical Center', 'domain' => 'eastside.test', 'database' => 'eastside'],
            ['name' => 'Northshore Hospital', 'domain' => 'northshore.test', 'database' => 'northshore'],
            ['name' => 'Southview Medical Center', 'domain' => 'southview.test', 'database' => 'southview'],
            ['name' => 'Parkview Hospital', 'domain' => 'parkview.test', 'database' => 'parkview'],
            ['name' => 'Lakeside Medical Center', 'domain' => 'lakeside.test', 'database' => 'lakeside'],
            ['name' => 'Hillside Community Hospital', 'domain' => 'hillside.test', 'database' => 'hillside'],
            ['name' => 'Sunset Medical Center', 'domain' => 'sunset.test', 'database' => 'sunset'],
            ['name' => 'Sunrise Hospital', 'domain' => 'sunrise.test', 'database' => 'sunrise'],
            ['name' => 'Oakwood Medical Center', 'domain' => 'oakwood.test', 'database' => 'oakwood'],
            ['name' => 'Pineview Hospital', 'domain' => 'pineview.test', 'database' => 'pineview'],
            ['name' => 'Greenwood Medical Center', 'domain' => 'greenwood.test', 'database' => 'greenwood'],
            ['name' => 'Blue Ridge Hospital', 'domain' => 'blueridge.test', 'database' => 'blueridge'],
            ['name' => 'Mountain View Medical Center', 'domain' => 'mountainview.test', 'database' => 'mountainview'],
        ];

        foreach ($hospitals as $hospital) {
            $tenant = Tenant::firstOrCreate(
                ['domain' => $hospital['domain']],
                [
                    'name' => $hospital['name'],
                    'domain' => $hospital['domain'],
                    'database' => $hospital['database'],
                ]
            );

            // Only create roles if tenant was just created
            if ($tenant->wasRecentlyCreated) {
                $tenant->createDefaultRoles();
            }
        }

        // Create a tenant for localhost development (if needed)
        // Note: Only create if it doesn't exist to avoid unique constraint violations
        $localhostTenant = Tenant::firstOrCreate(
            ['domain' => 'localhost'],
            [
                'name' => 'Local Development',
                'domain' => 'localhost',
                'database' => env('DB_DATABASE', 'laravel'),
            ]
        );

        if ($localhostTenant->wasRecentlyCreated) {
            $localhostTenant->createDefaultRoles();
        }

        // Also create for 127.0.0.1 (if needed)
        // Use different database name or skip if localhost already exists with same database
        $dbName = env('DB_DATABASE', 'laravel');
        $existingTenant = Tenant::where('database', $dbName)->where('domain', '!=', 'localhost')->first();

        if (! $existingTenant || $existingTenant->domain === '127.0.0.1') {
            $ipTenant = Tenant::firstOrCreate(
                ['domain' => '127.0.0.1'],
                [
                    'name' => 'Local Development (IP)',
                    'domain' => '127.0.0.1',
                    'database' => $dbName.'_ip', // Use different database to avoid unique constraint
                ]
            );

            if ($ipTenant->wasRecentlyCreated) {
                $ipTenant->createDefaultRoles();
            }
        }
    }
}
