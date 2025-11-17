<?php

namespace Database\Seeders;

use App\Models\National\NationalAttempt;
use Illuminate\Database\Seeder;

class CleanupAbandonedAttemptsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This seeder cleans up exam attempts that were started but have no answers.
     */
    public function run(): void
    {
        $this->command->info('Cleaning up abandoned exam attempts (started but no answers)...');
        $this->command->newLine();

        // Find ALL attempts that:
        // 1. Have started_at set (actually started)
        // 2. Are still in_progress
        // 3. Have no answers
        // (No time limit - clean up all of them)
        $abandonedAttempts = NationalAttempt::where('status', 'in_progress')
            ->whereNotNull('started_at')
            ->whereDoesntHave('answers')
            ->with(['user:id,name,email', 'assessment:id,title'])
            ->get();

        $count = $abandonedAttempts->count();

        if ($count === 0) {
            $this->command->info('✅ No abandoned attempts found (all attempts have answers or are not started).');

            return;
        }

        $this->command->warn("Found {$count} abandoned attempt(s) with started_at but no answers");
        $this->command->newLine();

        // Show summary before deletion
        $byExam = [];
        foreach ($abandonedAttempts as $attempt) {
            $examTitle = $attempt->assessment->title ?? 'Unknown';
            $byExam[$examTitle] = ($byExam[$examTitle] ?? 0) + 1;
        }

        $this->command->info('Summary by exam:');
        foreach ($byExam as $examTitle => $examCount) {
            $this->command->line("  {$examTitle}: {$examCount} abandoned attempt(s)");
        }

        $this->command->newLine();
        $this->command->info('Deleting abandoned attempts...');

        $deletedCount = 0;
        foreach ($abandonedAttempts as $attempt) {
            $attempt->delete();
            $deletedCount++;
        }

        $this->command->newLine();
        $this->command->info("✅ Deleted {$deletedCount} abandoned attempt(s)");
    }
}
