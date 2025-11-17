<?php

namespace Database\Seeders;

use App\Models\National\NationalAttempt;
use Illuminate\Database\Seeder;

class DiagnoseAbandonedAttemptsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This seeder diagnoses attempts that have started_at but no answers.
     */
    public function run(): void
    {
        $this->command->info('Diagnosing abandoned exam attempts...');
        $this->command->newLine();

        // Find all attempts with started_at but no answers
        $abandonedAttempts = NationalAttempt::whereNotNull('started_at')
            ->whereDoesntHave('answers')
            ->with(['user:id,name,email', 'assessment:id,title'])
            ->get();

        if ($abandonedAttempts->isEmpty()) {
            $this->command->info('✅ No abandoned attempts found (all attempts have answers or are not started).');

            return;
        }

        $this->command->warn("Found {$abandonedAttempts->count()} attempt(s) with started_at but no answers:");
        $this->command->newLine();

        $byStatus = [];
        $bySource = [];

        foreach ($abandonedAttempts as $attempt) {
            $status = $attempt->status ?? 'unknown';
            $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;

            // Try to determine source
            $source = 'Unknown';
            if ($attempt->user_agent === 'Seeder/1.0') {
                $source = 'Seeder (InServiceExamCompletedAttemptsSeeder)';
            } elseif ($attempt->ip_address === '127.0.0.1' && $attempt->user_agent === 'Seeder/1.0') {
                $source = 'Seeder';
            } else {
                $source = 'User Started (via UI)';
            }
            $bySource[$source] = ($bySource[$source] ?? 0) + 1;

            $this->command->line("  Attempt ID: {$attempt->id}");
            $this->command->line("    User: {$attempt->user->name} ({$attempt->user->email})");
            $this->command->line("    Exam: {$attempt->assessment->title}");
            $this->command->line("    Status: {$attempt->status}");
            $this->command->line("    Started: {$attempt->started_at}");
            $this->command->line("    Submitted: ".($attempt->submitted_at ?? 'Not submitted'));
            $this->command->line("    User Agent: ".($attempt->user_agent ?? 'N/A'));
            $this->command->line("    IP: ".($attempt->ip_address ?? 'N/A'));
            $this->command->line("    Age: ".$attempt->started_at->diffForHumans());
            $this->command->newLine();
        }

        $this->command->info('Summary by Status:');
        foreach ($byStatus as $status => $count) {
            $this->command->line("  {$status}: {$count}");
        }

        $this->command->newLine();
        $this->command->info('Summary by Source:');
        foreach ($bySource as $source => $count) {
            $this->command->line("  {$source}: {$count}");
        }

        $this->command->newLine();
        $this->command->warn('💡 Tip: Run CleanupAbandonedAttemptsSeeder to clean up these attempts.');
    }
}

