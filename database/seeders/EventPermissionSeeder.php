<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EventPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔐 Creating event permissions...');

        $permissions = [
            'view-events' => 'View events management page',
            'create-events' => 'Create organization events',
            'edit-events' => 'Edit events',
            'delete-events' => 'Delete events',
        ];

        foreach ($permissions as $name => $description) {
            $permission = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
            );
            $this->command->info("  ✓ {$name}");
        }

        // Assign permissions to roles
        $this->command->info('🎯 Assigning permissions to roles...');

        // System Admin and BOP get all permissions
        $adminRoles = Role::whereIn('name', ['System Admin', 'BOP'])->get();
        foreach ($adminRoles as $role) {
            $role->givePermissionTo(array_keys($permissions));
            $this->command->info("  ✓ Assigned all permissions to {$role->name}");
        }

        // Training Officers get organization-level permissions
        $staffRoles = Role::whereIn('name', ['Training Officer'])->get();
        foreach ($staffRoles as $role) {
            $role->givePermissionTo([
                'view-events',
                'create-events',
                'edit-events',
                'delete-events',
            ]);
            $this->command->info("  ✓ Assigned organization permissions to {$role->name}");
        }

        $this->command->info('✅ Event permissions setup complete!');
    }
}
