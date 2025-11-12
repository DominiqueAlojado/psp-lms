<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AssignmentPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔐 Creating assignment permissions...');

        $permissions = [
            'view-assignments' => 'View assignments management page',
            'create-assignments' => 'Create assignments for residents',
            'edit-assignments' => 'Edit assignments',
            'delete-assignments' => 'Delete assignments',
            'grade-assignments' => 'Grade and provide feedback on submissions',
            'view-all-submissions' => 'View all resident submissions',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
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
                'view-assignments',
                'create-assignments',
                'edit-assignments',
                'delete-assignments',
                'grade-assignments',
                'view-all-submissions',
            ]);
            $this->command->info("  ✓ Assigned organization permissions to {$role->name}");
        }

        $this->command->info('✅ Assignment permissions setup complete!');
    }
}
