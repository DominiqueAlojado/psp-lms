<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class ResidentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all institutions (hospitals) - these are the training sites
        $institutions = Organization::where('type', 'institution')->get();

        if ($institutions->isEmpty()) {
            $this->command->warn('No institutions found. Please run OrganizationSeeder first.');

            return;
        }

        $this->command->info('Creating residents across '.count($institutions).' institutions...');

        // Create 3-8 residents per institution (randomized)
        foreach ($institutions as $institution) {
            $residentCount = rand(3, 8);

            for ($i = 0; $i < $residentCount; $i++) {
                // Create resident
                $resident = Resident::factory()->create([
                    'organization_id' => $institution->id,
                ]);

                // Create user account for the resident
                $user = User::create([
                    'name' => $resident->full_name,
                    'email' => $resident->email,
                    'password' => 'password', // Default password
                    'email_verified_at' => now(),
                    'current_organization_id' => $institution->id,
                ]);

                // Link user to resident
                $resident->update(['user_id' => $user->id]);

                // Attach user to organization
                $user->organizations()->attach($institution->id, [
                    'joined_at' => now(),
                    'is_active' => $resident->status === 'active',
                ]);

                // Set permission context for this organization
                setPermissionsTeamId($institution->id);

                // Assign the global Resident role
                $user->assignRole('Resident');
            }

            $this->command->info("Created {$residentCount} residents for {$institution->name}");
        }

        // Reset permission context
        setPermissionsTeamId(null);

        $totalResidents = Resident::count();
        $this->command->info("Total residents created: {$totalResidents}");

        // Display some statistics
        $this->command->newLine();
        $this->command->info('Resident Statistics:');
        $this->command->table(
            ['Year Level', 'Count'],
            [
                ['Pre-Resident', Resident::where('year_level', 'Pre-Resident')->count()],
                ['First Year', Resident::where('year_level', 'First Year')->count()],
                ['Second Year', Resident::where('year_level', 'Second Year')->count()],
                ['Third Year', Resident::where('year_level', 'Third Year')->count()],
                ['Fourth Year', Resident::where('year_level', 'Fourth Year')->count()],
                ['Graduate', Resident::where('year_level', 'Graduate')->count()],
            ]
        );

        $this->command->table(
            ['Status', 'Count'],
            [
                ['Active', Resident::where('status', 'active')->count()],
                ['Inactive', Resident::where('status', 'inactive')->count()],
            ]
        );
    }
}
