<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // System admin - global role (no tenant_id, accessible from all tenants)
        Role::firstOrCreate(
            [
                'name' => 'system admin',
                'guard_name' => 'web',
                'tenant_id' => null,
            ]
        );

        // Note: Tenant-specific roles (admin, resident, consultant, bop)
        // are created per tenant using Tenant::createDefaultRoles()
        // This ensures each hospital has its own set of roles
    }
}
