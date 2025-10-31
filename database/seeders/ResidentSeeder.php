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

            // Create a user without two-factor auth for seeding
            $user = User::factory()->withoutTwoFactor()->create([
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
            ]);

            // Add user to tenant_user pivot table
            $tenant->residents()->attach($user->id);

            // Assign "resident" role to this user for this tenant
            $residentRole = \App\Models\Role::where('name', 'resident')
                ->where('tenant_id', $tenant->id)
                ->first();

            if ($residentRole) {
                $user->assignRole($residentRole);
            }

            // Create resident profile
            Resident::create([
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'level' => fake()->randomElement($levels),
                'specialty' => fake()->randomElement($specialties),
                'registration_number' => 'REG-'.str_pad((string) fake()->numberBetween(1000, 9999), 6, '0', STR_PAD_LEFT),
                'department' => fake()->randomElement($departments),
                'phone' => fake()->phoneNumber(),
                'start_date' => fake()->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
                'end_date' => fake()->optional(0.3)->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
                'status' => fake()->randomElement($statuses),
                'notes' => fake()->optional(0.4)->sentence(),
            ]);

            if ($i % 10 === 0) {
                $this->command->info("Created {$i} residents...");
            }
        }

        $this->command->info('Successfully created 50 residents!');
    }
}
