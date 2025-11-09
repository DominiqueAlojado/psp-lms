<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AnnouncementPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔐 Creating announcement permissions...');

        $permissions = [
            'view-announcements' => 'View announcements management page',
            'create-announcements' => 'Create organization announcements',
            'create-system-announcements' => 'Create system-wide announcements',
            'edit-announcements' => 'Edit announcements',
            'delete-announcements' => 'Delete announcements',
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

        // Training Officers and Program Directors get organization-level permissions
        $staffRoles = Role::whereIn('name', ['Training Officer'])->get();
        foreach ($staffRoles as $role) {
            $role->givePermissionTo([
                'view-announcements',
                'create-announcements',
                'edit-announcements',
                'delete-announcements',
            ]);
            $this->command->info("  ✓ Assigned organization permissions to {$role->name}");
        }

        $this->command->info('✅ Announcement permissions setup complete!');
    }
}
