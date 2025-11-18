<?php

namespace App\Console\Commands;

use App\Models\National\NationalAttempt;
use App\Models\QuestionBank;
use Illuminate\Console\Command;

class BackfillNationalQuestionStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'questions:backfill-national-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill question bank statistics from completed national exam attempts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting to backfill national question statistics...');

        // Get all completed national attempts
        $attempts = NationalAttempt::where('status', 'completed')
            ->with(['answers.question'])
            ->get();

        $this->info("Found {$attempts->count()} completed attempts to process.");

        $processed = 0;
        $updated = 0;
        $notFound = 0;

        foreach ($attempts as $attempt) {
            foreach ($attempt->answers as $answer) {
                $question = $answer->question;

                if (! $question) {
                    continue;
                }

                // Try exact match first
                $bankQuestion = QuestionBank::where('question_text', $question->question_text)
                    ->where('owner_type', 'national')
                    ->first();

                // If exact match fails, try fuzzy matching by question type and topic
                if (! $bankQuestion && $question->topic) {
                    $bankQuestion = QuestionBank::where('owner_type', 'national')
                        ->where('question_type', $question->question_type)
                        ->whereHas('topic', function ($q) use ($question) {
                            $q->where('name', 'like', '%'.$question->topic.'%');
                        })
                        ->first();
                }

                // If still not found, try matching by first part of question text (first 50 chars)
                if (! $bankQuestion) {
                    $questionStart = mb_substr(trim($question->question_text), 0, 50);
                    $bankQuestion = QuestionBank::where('owner_type', 'national')
                        ->where('question_type', $question->question_type)
                        ->whereRaw('SUBSTRING(TRIM(question_text), 1, 50) = ?', [$questionStart])
                        ->first();
                }

                if ($bankQuestion) {
                    // Update statistics for national scope
                    $bankQuestion->updateStatistics(
                        $answer->is_correct ?? false,
                        null, // Time not available for backfill
                        'national',
                        null
                    );
                    $updated++;
                } else {
                    $notFound++;
                    $this->warn("Could not find question bank entry for: ".mb_substr($question->question_text, 0, 50)."...");
                }
            }
            $processed++;
        }

        $this->info("Processed {$processed} attempts and updated statistics for {$updated} question answers.");
        if ($notFound > 0) {
            $this->warn("Could not match {$notFound} questions to question bank entries.");
        }
        $this->info('Done!');

        return Command::SUCCESS;
    }
}

