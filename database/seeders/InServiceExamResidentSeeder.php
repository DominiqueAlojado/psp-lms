<?php

namespace Database\Seeders;

use App\Models\National\NationalAssessment;
use App\Models\National\NationalAttempt;
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

        // Get all active in-service exams (Anatomic Pathology and Clinical Pathology Theoretical)
        $exams = NationalAssessment::whereIn('category', [
            'anatomic-pathology-theoretical',
            'clinical-pathology-theoretical',
        ])
            ->where('is_published', true)
            ->get();

        if ($exams->isEmpty()) {
            $this->command->warn('No active in-service exams found. Please run InServiceExamSeeder first.');

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

        $this->command->info("Found {$exams->count()} active in-service exam(s)");
        $this->command->info("Found {$residents->count()} active resident(s)");

        // Step 1: Add all residents to the In-Service Exams organization
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

        // Step 2: Create initial attempts for all residents
        $this->command->newLine();
        $this->command->info('Creating initial attempts for residents...');

        $totalCreated = 0;
        $totalSkipped = 0;

        foreach ($exams as $exam) {
            $this->command->info("Processing: {$exam->title}");

            foreach ($residents as $resident) {
                $user = $resident->user;

                if (! $user) {
                    $totalSkipped++;

                    continue;
                }

                // Check if attempt already exists
                $existingAttempt = NationalAttempt::where('assessment_id', $exam->id)
                    ->where('user_id', $user->id)
                    ->first();

                if ($existingAttempt) {
                    $totalSkipped++;

                    continue;
                }

                // Create initial attempt for resident
                // Status is 'in_progress' but started_at is null, so they can start when ready
                NationalAttempt::create([
                    'assessment_id' => $exam->id,
                    'user_id' => $user->id,
                    'year_level' => $resident->year_level,
                    'organization_id' => $resident->organization_id,
                    'started_at' => null, // Will be set when they actually start
                    'submitted_at' => null,
                    'score' => null,
                    'total_points' => $exam->total_points,
                    'national_rank' => null,
                    'institution_rank' => null,
                    'percentile' => null,
                    'status' => 'in_progress', // Enrolled but not started yet
                    'ip_address' => null,
                    'user_agent' => null,
                    'browser_metadata' => null,
                    'connection_type' => null,
                    'connection_speed' => null,
                    'last_activity_at' => null,
                ]);

                $totalCreated++;
            }
        }

        $this->command->newLine();
        $this->command->info("✅ Created {$totalCreated} initial attempts");
        if ($totalSkipped > 0) {
            $this->command->info("⏭️  Skipped {$totalSkipped} (already exist or missing user)");
        }
        $this->command->info("Total residents enrolled in in-service exams: {$totalCreated}");
    }
}
