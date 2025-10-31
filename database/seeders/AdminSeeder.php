<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Step 1: Create System Admin User
        // System admin has global access across all hospitals (tenants)
        $systemAdmin = User::firstOrCreate(
            ['email' => 'systemadmin@example.com'],
            [
                'name' => 'System Administrator',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        // Assign system admin role (global role, tenant_id = null)
        $systemAdminRole = Role::where('name', 'system admin')
            ->whereNull('tenant_id')
            ->first();

        if ($systemAdminRole) {
            if (! $systemAdmin->hasRole($systemAdminRole)) {
                $systemAdmin->assignRole($systemAdminRole);
                $this->command->info('System Admin created and assigned system admin role.');
            } else {
                $this->command->info('System Admin already has system admin role.');
            }
        } else {
            $this->command->warn('System admin role not found. Please run RoleSeeder first.');
        }

        // Step 2: Create Hospital Admin User
        // Hospital admin has admin role for multiple hospitals
        $hospitalAdmin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Hospital Administrator',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        // Get all tenants (hospitals), excluding localhost/development
        $tenants = Tenant::whereNotIn('domain', ['localhost', '127.0.0.1'])->get();

        if ($tenants->isEmpty()) {
            $this->command->warn('No tenants found. Please run TenantSeeder first.');

            return;
        }

        // Assign admin role to multiple hospitals (randomly select 5-10 hospitals)
        $hospitalsCount = min(10, max(5, (int) ceil($tenants->count() / 2)));
        $selectedTenants = $tenants->random($hospitalsCount);

        $assignedCount = 0;
        foreach ($selectedTenants as $tenant) {
            // Link user to tenant via pivot table
            if (! $tenant->residents()->where('users.id', $hospitalAdmin->id)->exists()) {
                $tenant->residents()->attach($hospitalAdmin->id);
            }

            // Get admin role for this tenant
            // Use withoutGlobalScopes to bypass tenant scoping
            $adminRole = Role::withoutGlobalScopes()
                ->where('name', 'admin')
                ->where('tenant_id', $tenant->id)
                ->first();

            if ($adminRole) {
                // Check if user already has this specific role
                $hasRole = $hospitalAdmin->roles()
                    ->withoutGlobalScopes()
                    ->where('roles.id', $adminRole->id)
                    ->exists();

                if (! $hasRole) {
                    $hospitalAdmin->assignRole($adminRole);
                    $assignedCount++;
                }
            }
        }

        if ($assignedCount > 0) {
            $this->command->info("Hospital Admin created and assigned admin role to {$assignedCount} hospitals:");
            foreach ($selectedTenants as $tenant) {
                $this->command->line("  - {$tenant->name}");
            }
        } else {
            $this->command->warn('Admin roles not found for tenants. Please ensure TenantSeeder runs first.');
        }
    }
}
