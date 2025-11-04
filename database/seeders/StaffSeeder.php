<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating staff members...');

        // Get roles (excluding Resident)
        $adminRole = Role::where('name', 'Admin')->first();
        $trainingOfficerRole = Role::where('name', 'Training Officer')->first();
        $bopRole = Role::where('name', 'BOP')->first();

        // Get some organizations
        $organizations = Organization::where('type', 'institution')
            ->inRandomOrder()
            ->limit(10)
            ->get();

        if ($organizations->isEmpty()) {
            $this->command->warn('No institutions found. Please run OrganizationSeeder first.');

            return;
        }

        // Create Admins (one per institution)
        foreach ($organizations->take(5) as $organization) {
            $admin = User::create([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'current_organization_id' => $organization->id,
            ]);

            $admin->assignRole($adminRole);
            $admin->organizations()->attach($organization->id, [
                'joined_at' => now(),
                'is_active' => true,
            ]);

            $this->command->info("Created Admin: {$admin->name} at {$organization->name}");
        }

        // Create Training Officers (one per institution)
        foreach ($organizations->take(8) as $organization) {
            $officer = User::create([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'current_organization_id' => $organization->id,
            ]);

            $officer->assignRole($trainingOfficerRole);
            $officer->organizations()->attach($organization->id, [
                'joined_at' => now(),
                'is_active' => true,
            ]);

            $this->command->info("Created Training Officer: {$officer->name} at {$organization->name}");
        }

        // Create BOP Members (fewer, multi-organization access)
        for ($i = 0; $i < 3; $i++) {
            $bop = User::create([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'current_organization_id' => $organizations->first()->id,
            ]);

            $bop->assignRole($bopRole);

            // Attach to multiple organizations
            foreach ($organizations->take(rand(3, 5)) as $org) {
                $bop->organizations()->attach($org->id, [
                    'joined_at' => now(),
                    'is_active' => true,
                ]);
            }

            $this->command->info("Created BOP Member: {$bop->name}");
        }

        $totalStaff = User::whereHas('roles', function ($query) {
            $query->where('name', '!=', 'Resident');
        })->count();

        $this->command->newLine();
        $this->command->info("Total staff members created: {$totalStaff}");

        // Display statistics
        $this->command->newLine();
        $this->command->info('Staff Statistics:');
        $this->command->table(
            ['Role', 'Count'],
            [
                ['Admin', User::role('Admin')->count()],
                ['Training Officer', User::role('Training Officer')->count()],
                ['BOP', User::role('BOP')->count()],
                ['System Admin', User::role('System Admin')->count()],
            ]
        );
    }
}

