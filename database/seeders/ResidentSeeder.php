<?php

namespace Database\Seeders;

use App\Models\Resident;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class ResidentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all tenants (hospitals), excluding localhost/development tenants
        $tenants = Tenant::whereNotIn('domain', ['localhost', '127.0.0.1'])->get();

        if ($tenants->isEmpty()) {
            $this->command->warn('No tenants found. Please run TenantSeeder first.');

            return;
        }

        // Medical specialties
        $specialties = [
            'Internal Medicine',
            'General Surgery',
            'Emergency Medicine',
            'Pediatrics',
            'Obstetrics and Gynecology',
            'Psychiatry',
            'Anesthesiology',
            'Radiology',
            'Pathology',
            'Orthopedic Surgery',
            'Cardiology',
            'Neurology',
            'Dermatology',
            'Ophthalmology',
            'Urology',
        ];

        // Residency levels
        $levels = ['PGY-1', 'PGY-2', 'PGY-3', 'PGY-4', 'PGY-5', 'R1', 'R2', 'R3', 'R4'];

        // Departments
        $departments = [
            'Emergency Department',
            'Intensive Care Unit',
            'Operating Room',
            'Cardiology Ward',
            'Pediatric Ward',
            'Surgical Ward',
            'Medical Ward',
            'ICU',
            'Outpatient Clinic',
            'Radiology Department',
        ];

        // Statuses
        $statuses = ['active', 'active', 'active', 'active', 'inactive']; // Weighted towards active

        $this->command->info('Creating 50 residents...');

        // Create 50 residents
        for ($i = 1; $i <= 50; $i++) {
            // Randomly select a tenant
            $tenant = $tenants->random();

            // Step 1: Create a user account for this resident
            // Each resident must have a user account to login to the system
            $user = User::factory()->withoutTwoFactor()->create([
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
            ]);

            // Step 2: Link user to tenant (hospital) via pivot table
            // This establishes which hospital(s) the resident belongs to
            $tenant->residents()->attach($user->id);

            // Step 3: Assign "resident" role to this user for this tenant
            // Permissions will be configured later - for now just assign the role
            // Use withoutGlobalScopes to bypass tenant scoping
            $residentRole = \App\Models\Role::withoutGlobalScopes()
                ->where('name', 'resident')
                ->where('tenant_id', $tenant->id)
                ->first();

            if ($residentRole) {
                $user->assignRole($residentRole);
            } else {
                $this->command->warn("Resident role not found for tenant {$tenant->name}. Please ensure TenantSeeder runs first to create default roles.");
            }

            // Step 4: Create resident profile with detailed information
            // This stores resident-specific data separate from user account
            Resident::create([
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'level' => fake()->randomElement($levels),
                'specialty' => fake()->randomElement($specialties),
                'registration_number' => 'REG-'.str_pad((string) fake()->numberBetween(1000, 9999), 6, '0', STR_PAD_LEFT),
                'department' => fake()->randomElement($departments),
                'phone' => fake()->phoneNumber(),
                'start_date' => fake()->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
                'end_date' => fake()->boolean(30) ? fake()->dateTimeBetween('now', '+2 years')->format('Y-m-d') : null,
                'status' => fake()->randomElement($statuses),
                'notes' => fake()->boolean(40) ? fake()->sentence() : null,
            ]);

            if ($i % 10 === 0) {
                $this->command->info("Created {$i} residents...");
            }
        }

        $this->command->info('Successfully created 50 residents!');
    }
}
