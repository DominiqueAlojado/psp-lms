<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Resident;
use Illuminate\Database\Seeder;

class InServiceExamResidentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the "In-Service Exams" organization (national type)
        $nationalOrg = Organization::where('type', 'national')
            ->where('slug', 'in-service-exams')
            ->first();

        if (! $nationalOrg) {
            $this->command->error('In-Service Exams organization not found. Please run OrganizationSeeder first.');

            return;
        }

        // Get all residents with user accounts
        $residents = Resident::with('user')
            ->whereHas('user')
            ->where('status', 'active')
            ->get();

        if ($residents->isEmpty()) {
            $this->command->warn('No active residents found. Please run ResidentSeeder first.');

            return;
        }

        $this->command->info("Found {$residents->count()} active resident(s)");

        // Add all residents to the In-Service Exams organization
        $this->command->info('Adding residents to In-Service Exams organization...');
        $orgAdded = 0;
        $orgSkipped = 0;

        foreach ($residents as $resident) {
            $user = $resident->user;

            if (! $user) {
                $orgSkipped++;

                continue;
            }

            // Check if user is already in the national organization
            if (! $user->organizations()->where('organizations.id', $nationalOrg->id)->exists()) {
                $user->organizations()->attach($nationalOrg->id, [
                    'joined_at' => now(),
                    'is_active' => true,
                ]);
                $orgAdded++;
            } else {
                $orgSkipped++;
            }
        }

        $this->command->info("✅ Added {$orgAdded} residents to In-Service Exams organization");
        if ($orgSkipped > 0) {
            $this->command->info("⏭️  {$orgSkipped} residents already in organization");
        }
    }
}
