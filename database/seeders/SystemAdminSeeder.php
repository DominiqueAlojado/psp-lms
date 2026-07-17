<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class SystemAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create System Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@unified-lms.test'],
            [
                'name' => 'System Administrator',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        // Add admin to all organizations (since they're a system admin)
        $organizations = Organization::all();
        foreach ($organizations as $organization) {
            $admin->organizations()->syncWithoutDetaching([
                $organization->id => [
                    'joined_at' => now(),
                    'is_active' => true,
                ],
            ]);
        }

        // Keep a real organization fallback for org-scoped write actions.
        $fallbackOrganization = Organization::query()
            ->where('is_active', true)
            ->orderByRaw("CASE WHEN type = 'national' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->first();

        if ($fallbackOrganization) {
            $admin->update(['current_organization_id' => $fallbackOrganization->id]);

            // Assign System Admin role within the fallback real organization context.
            setPermissionsTeamId($fallbackOrganization->id);
            $systemAdminRole = Role::where('name', 'System Admin')->first();
            if ($systemAdminRole && ! $admin->hasRole('System Admin')) {
                $admin->assignRole($systemAdminRole);
            }
            setPermissionsTeamId(null);
        }

        $this->command->info('✓ System Admin user created:');
        $this->command->table(
            ['Field', 'Value'],
            [
                ['Email', $admin->email],
                ['Password', 'password'],
                ['Name', $admin->name],
                ['Role', 'System Admin'],
                ['Organizations', $organizations->count() . ' (All)'],
                ['Current Org Fallback', $fallbackOrganization?->name ?? 'N/A'],
                ['Default View Context', 'All Organizations'],
            ]
        );
    }
}
