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
        // Create main domain tenant (for psp-lms.test)
        $mainTenant = Tenant::firstOrCreate(
            ['domain' => 'psp-lms.test'],
            [
                'name' => 'PSP LMS Main',
                'domain' => 'psp-lms.test',
                'database' => 'psp_lms',
            ]
        );

        if ($mainTenant->wasRecentlyCreated) {
            $mainTenant->createDefaultRoles();
        }

        // Create hospital tenants with subdomains (e.g., central.psp-lms.test)
        $hospitals = [
            ['name' => 'City General Hospital', 'domain' => 'citygeneral.psp-lms.test', 'database' => 'citygeneral'],
            ['name' => 'Metropolitan Medical Center', 'domain' => 'metropolitan.psp-lms.test', 'database' => 'metropolitan'],
            ['name' => 'Riverside Community Hospital', 'domain' => 'riverside.psp-lms.test', 'database' => 'riverside'],
            ['name' => 'Memorial Hospital', 'domain' => 'memorial.psp-lms.test', 'database' => 'memorial'],
            ['name' => 'St. Mary\'s Hospital', 'domain' => 'stmarys.psp-lms.test', 'database' => 'stmarys'],
            ['name' => 'University Medical Center', 'domain' => 'university.psp-lms.test', 'database' => 'university'],
            ['name' => 'Regional Hospital', 'domain' => 'regional.psp-lms.test', 'database' => 'regional'],
            ['name' => 'Central Medical Center', 'domain' => 'central.psp-lms.test', 'database' => 'central'],
            ['name' => 'Westside Hospital', 'domain' => 'westside.psp-lms.test', 'database' => 'westside'],
            ['name' => 'Eastside Medical Center', 'domain' => 'eastside.psp-lms.test', 'database' => 'eastside'],
            ['name' => 'Northshore Hospital', 'domain' => 'northshore.psp-lms.test', 'database' => 'northshore'],
            ['name' => 'Southview Medical Center', 'domain' => 'southview.psp-lms.test', 'database' => 'southview'],
            ['name' => 'Parkview Hospital', 'domain' => 'parkview.psp-lms.test', 'database' => 'parkview'],
            ['name' => 'Lakeside Medical Center', 'domain' => 'lakeside.psp-lms.test', 'database' => 'lakeside'],
            ['name' => 'Hillside Community Hospital', 'domain' => 'hillside.psp-lms.test', 'database' => 'hillside'],
            ['name' => 'Sunset Medical Center', 'domain' => 'sunset.psp-lms.test', 'database' => 'sunset'],
            ['name' => 'Sunrise Hospital', 'domain' => 'sunrise.psp-lms.test', 'database' => 'sunrise'],
            ['name' => 'Oakwood Medical Center', 'domain' => 'oakwood.psp-lms.test', 'database' => 'oakwood'],
            ['name' => 'Pineview Hospital', 'domain' => 'pineview.psp-lms.test', 'database' => 'pineview'],
            ['name' => 'Greenwood Medical Center', 'domain' => 'greenwood.psp-lms.test', 'database' => 'greenwood'],
            ['name' => 'Blue Ridge Hospital', 'domain' => 'blueridge.psp-lms.test', 'database' => 'blueridge'],
            ['name' => 'Mountain View Medical Center', 'domain' => 'mountainview.psp-lms.test', 'database' => 'mountainview'],
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
        // Note: Use a unique database name to avoid conflicts with main tenant
        $dbName = env('DB_DATABASE', 'laravel');

        // Check if main tenant already uses this database name
        $mainTenantUsesDb = Tenant::where('domain', 'psp-lms.test')
            ->where('database', $dbName)
            ->exists();

        // Use a different database name for localhost if main tenant uses it
        $localhostDb = $mainTenantUsesDb ? $dbName.'_localhost' : $dbName;

        $localhostTenant = Tenant::firstOrCreate(
            ['domain' => 'localhost'],
            [
                'name' => 'Local Development',
                'domain' => 'localhost',
                'database' => $localhostDb,
            ]
        );

        if ($localhostTenant->wasRecentlyCreated) {
            $localhostTenant->createDefaultRoles();
        }

        // Also create for 127.0.0.1 (if needed)
        // Use different database name to avoid unique constraint
        $ipDb = $dbName.'_ip';

        $ipTenant = Tenant::firstOrCreate(
            ['domain' => '127.0.0.1'],
            [
                'name' => 'Local Development (IP)',
                'domain' => '127.0.0.1',
                'database' => $ipDb,
            ]
        );

        if ($ipTenant->wasRecentlyCreated) {
            $ipTenant->createDefaultRoles();
        }
    }
}
