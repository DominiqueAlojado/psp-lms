<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define global roles
        $roles = [
            'System Admin',
            'Admin',
            'Training Officer',
            'BOP', // Board of Pathology
            'Resident',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(
                [
                    'name' => $roleName,
                    'guard_name' => 'web',
                ]
            );
        }

        $this->command->info('Created '.count($roles).' global roles');
    }
}
