<?php

namespace App\Console\Commands;

use App\Models\QuestionBank;
use App\Models\QuestionBankStatistic;
use Illuminate\Console\Command;

class RecalculateDiscriminationIndices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'questions:recalculate-discrimination';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate discrimination indices for all question bank statistics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting to recalculate discrimination indices...');

        // Get all statistics with 10+ attempts (both national and institution)
        $statistics = QuestionBankStatistic::where('times_answered', '>=', 10)
            ->with('question')
            ->get();

        $nationalCount = $statistics->where('scope', 'national')->count();
        $institutionCount = $statistics->where('scope', 'institution')->count();

        $this->info("Found {$statistics->count()} statistics with 10+ attempts to process ({$nationalCount} national, {$institutionCount} institution).");

        $processed = 0;
        $updated = 0;

        foreach ($statistics as $stat) {
            $question = $stat->question;

            if (! $question) {
                continue;
            }

            try {
                // Call the public method to recalculate discrimination index
                $question->recalculateDiscriminationIndex($stat, $stat->scope, $stat->institution_id);

                $processed++;

                // Reload to check if it was updated
                $stat->refresh();
                if ($stat->discrimination_index !== null) {
                    $updated++;
                }
            } catch (\Exception $e) {
                $this->warn("Failed to calculate discrimination for question {$question->id}: {$e->getMessage()}");
                continue;
            }
        }

        $this->info("Processed {$processed} statistics and updated discrimination indices for {$updated} questions.");
        $this->info('Done!');

        return Command::SUCCESS;
    }
}

