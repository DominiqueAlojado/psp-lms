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
            $createdCount = 0;
            $skippedCount = 0;

            for ($i = 0; $i < $residentCount; $i++) {
                try {
                    // Check if we've already created enough residents for this institution
                    $existingCount = Resident::where('organization_id', $institution->id)->count();
                    if ($existingCount >= $residentCount) {
                        $skippedCount = $residentCount - $createdCount;
                        break;
                    }

                    // Create resident
                    $resident = Resident::factory()->create([
                        'organization_id' => $institution->id,
                    ]);

                    // Check if user with this email already exists
                    $existingUser = User::where('email', $resident->email)->first();
                    if ($existingUser) {
                        // Link existing user to resident
                        $resident->update(['user_id' => $existingUser->id]);
                        $skippedCount++;

                        continue;
                    }

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

                    // Attach user to organization (only if not already attached)
                    if (! $user->organizations()->where('organizations.id', $institution->id)->exists()) {
                        $user->organizations()->attach($institution->id, [
                            'joined_at' => now(),
                            'is_active' => $resident->status === 'active',
                        ]);
                    }

                    // Set permission context for this organization
                    setPermissionsTeamId($institution->id);

                    // Assign the global Resident role (only if not already assigned)
                    if (! $user->hasRole('Resident')) {
                        $user->assignRole('Resident');
                    }

                    $createdCount++;
                } catch (\Illuminate\Database\QueryException $e) {
                    // Handle unique constraint violations
                    if ($e->getCode() === '23505' || str_contains($e->getMessage(), 'duplicate key')) {
                        $skippedCount++;
                        $this->command->warn("Skipped duplicate resident: {$e->getMessage()}");

                        continue;
                    }
                    throw $e;
                } catch (\Exception $e) {
                    $this->command->error("Failed to create resident: {$e->getMessage()}");
                    $skippedCount++;

                    continue;
                }
            }

            if ($createdCount > 0) {
                $this->command->info("Created {$createdCount} residents for {$institution->name}");
            }
            if ($skippedCount > 0) {
                $this->command->info("Skipped {$skippedCount} duplicate/existing residents for {$institution->name}");
            }
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
