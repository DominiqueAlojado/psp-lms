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
            ['email' => 'admin@psp.ph'],
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

        // Set PSP Main as default organization
        $pspMain = Organization::where('slug', 'psp-main')->first();
        if ($pspMain) {
            $admin->update(['current_organization_id' => $pspMain->id]);

            // Assign System Admin role with PSP Main context
            setPermissionsTeamId($pspMain->id);
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
                ['Organizations', $organizations->count().' (All)'],
                ['Current Org', $pspMain?->name ?? 'N/A'],
            ]
        );
    }
}
