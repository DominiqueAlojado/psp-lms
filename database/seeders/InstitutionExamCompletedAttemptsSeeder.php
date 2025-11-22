<?php

namespace Database\Seeders;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionAttempt;
use App\Models\Institution\InstitutionQuestion;
use App\Models\Organization;
use App\Models\Resident;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\ProgressBar;

class InstitutionExamCompletedAttemptsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only seed for Bataan General Hospital residents
        $bataanOrg = Organization::where('slug', 'bataan-general-hospital')->first();

        if (! $bataanOrg) {
            $this->command->warn('Bataan General Hospital not found. Please run OrganizationSeeder first.');

            return;
        }

        // Get only active residents from Bataan General Hospital with user accounts
        $residents = Resident::with('user')
            ->whereHas('user')
            ->where('status', 'active')
            ->where('organization_id', $bataanOrg->id)
            ->get();

        if ($residents->isEmpty()) {
            $this->command->warn('No active residents found for Bataan General Hospital. Please run ResidentSeeder first.');

            return;
        }

        // Get all published institution exams for Bataan General Hospital
        $exams = InstitutionAssessment::where('organization_id', $bataanOrg->id)
            ->where('is_published', true)
            ->with('questions.choices')
            ->get();

        if ($exams->isEmpty()) {
            $this->command->warn('No published institution exams found for Bataan General Hospital. Please run BataanGeneralHospitalExamsSeeder first.');

            return;
        }

        $this->command->info("Found {$residents->count()} active resident(s) for Bataan General Hospital");
        $this->command->info("Found {$exams->count()} published exam(s)");
        $this->command->newLine();

        $totalAttemptsCreated = 0;
        $totalAnswersCreated = 0;
        $totalResidents = $residents->count();

        $bar = new ProgressBar($this->command->getOutput(), $totalResidents);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $bar->start();

        foreach ($residents as $resident) {
            $user = $resident->user;

            if (! $user) {
                $bar->advance();

                continue;
            }

            // Process each exam for this resident
            foreach ($exams as $exam) {
                $attempt = $this->createCompletedAttempt($user, $resident, $exam);
                if ($attempt) {
                    $totalAttemptsCreated++;
                    $totalAnswersCreated += $attempt->answers()->count();
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine(2);

        $this->command->info("✅ Created {$totalAttemptsCreated} completed institution exam attempts");
        $this->command->info("✅ Created {$totalAnswersCreated} answers");
    }

    /**
     * Create a completed exam attempt with answers for a resident.
     */
    private function createCompletedAttempt($user, $resident, InstitutionAssessment $exam): ?InstitutionAttempt
    {
        // Check if a completed attempt already exists
        $existingCompleted = InstitutionAttempt::where('assessment_id', $exam->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['completed', 'graded'])
            ->first();

        if ($existingCompleted) {
            return null; // Skip if already completed
        }

        // Delete any unstarted attempts for this exam/user
        InstitutionAttempt::where('assessment_id', $exam->id)
            ->where('user_id', $user->id)
            ->whereNull('started_at')
            ->delete();

        // Also delete any started attempts with no answers (abandoned attempts)
        InstitutionAttempt::where('assessment_id', $exam->id)
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->whereNotNull('started_at')
            ->whereDoesntHave('answers')
            ->delete();

        // Get all questions with choices FIRST before creating attempt
        $questions = $exam->questions()->with('choices')->orderBy('order')->get();

        if ($questions->isEmpty()) {
            return null;
        }

        // Validate we can create answers before creating the attempt
        $validQuestionsCount = 0;
        foreach ($questions as $question) {
            if (in_array($question->question_type, ['multiple_choice', 'multiple_select', 'true_false'])) {
                if ($question->choices->isNotEmpty()) {
                    $validQuestionsCount++;
                }
            } else {
                $validQuestionsCount++; // Other question types don't need choices
            }
        }

        if ($validQuestionsCount === 0) {
            return null;
        }

        // Create a new completed attempt
        $startedAt = now()->subMinutes(rand(30, 60)); // Started 30-60 minutes ago
        $submittedAt = $startedAt->copy()->addMinutes(rand(20, 50)); // Submitted 20-50 minutes after start

        $attempt = InstitutionAttempt::create([
            'assessment_id' => $exam->id,
            'user_id' => $user->id,
            'year_level' => $resident->year_level,
            'organization_id' => $resident->organization_id,
            'started_at' => $startedAt,
            'submitted_at' => $submittedAt,
            'score' => 0, // Will be calculated
            'total_points' => $exam->total_points,
            'status' => 'in_progress', // Will be updated to 'completed'
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Seeder/1.0',
            'last_activity_at' => $submittedAt,
        ]);

        $answersToInsert = [];

        // Prepare answers for batch insert
        foreach ($questions as $question) {
            try {
                // Skip questions without choices (for multiple choice questions)
                if (in_array($question->question_type, ['multiple_choice', 'multiple_select', 'true_false'])) {
                    $choices = $question->choices;
                    if ($choices->isEmpty()) {
                        continue;
                    }
                }

                $isCorrect = (rand(1, 100) <= 65); // 65% chance of correct answer (realistic performance)
                $answerData = $this->generateAnswerData($question, $isCorrect);

                if (empty($answerData) || (isset($answerData['choice_id']) && $answerData['choice_id'] === null)) {
                    continue;
                }

                $pointsEarned = $isCorrect ? $question->points : 0;

                $answerRow = [
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'answer_data' => json_encode($answerData), // Encode to JSON for DB
                    'is_correct' => $isCorrect,
                    'points_earned' => $pointsEarned,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Add answer_change_count if column exists
                if (DB::getSchemaBuilder()->hasColumn('institution_answers', 'answer_change_count')) {
                    $answerRow['answer_change_count'] = rand(0, 2);
                }

                $answersToInsert[] = $answerRow;
            } catch (\Exception $e) {
                $this->command->warn("Failed to prepare answer for question {$question->id}: {$e->getMessage()}");

                continue;
            }
        }

        // Batch insert answers
        if (! empty($answersToInsert)) {
            // Chunk inserts to avoid exceeding parameter limits for very large datasets
            foreach (array_chunk($answersToInsert, 500) as $chunk) {
                DB::table('institution_answers')->insert($chunk);
            }
            $answersCreated = count($answersToInsert);
        } else {
            $answersCreated = 0;
        }

        // If no answers were created, delete the attempt
        if ($answersCreated === 0) {
            $attempt->delete();

            return null;
        }

        // Calculate final score and mark as completed
        $attempt->calculateScore();

        return $attempt;
    }

    /**
     * Generate answer data for a question.
     */
    private function generateAnswerData(InstitutionQuestion $question, bool $shouldBeCorrect): array
    {
        switch ($question->question_type) {
            case 'multiple_choice':
            case 'true_false':
                $choices = $question->choices; // Already eager loaded
                if ($choices->isEmpty()) {
                    return [];
                }

                if ($shouldBeCorrect) {
                    $correctChoice = $choices->firstWhere('is_correct', true);
                    $choiceId = $correctChoice?->id ?? $choices->first()?->id;
                } else {
                    $wrongChoices = $choices->where('is_correct', false);
                    $choiceId = $wrongChoices->isNotEmpty()
                        ? $wrongChoices->random()->id
                        : $choices->first()?->id;
                }

                if ($choiceId === null) {
                    return [];
                }

                return ['choice_id' => $choiceId];

            case 'multiple_select':
                $choices = $question->choices; // Already eager loaded
                if ($choices->isEmpty()) {
                    return [];
                }

                $correctChoiceIds = $choices->where('is_correct', true)->pluck('id')->toArray();

                if ($shouldBeCorrect) {
                    return ['choice_ids' => $correctChoiceIds];
                } else {
                    $wrongChoices = $choices->where('is_correct', false);
                    if ($wrongChoices->isNotEmpty() && count($correctChoiceIds) > 1) {
                        $selectedIds = array_slice($correctChoiceIds, 0, -1);
                    } elseif ($wrongChoices->isNotEmpty()) {
                        $wrongId = $wrongChoices->first()?->id;
                        $selectedIds = $wrongId ? array_merge($correctChoiceIds, [$wrongId]) : $correctChoiceIds;
                    } else {
                        $selectedIds = $correctChoiceIds;
                    }

                    $filteredIds = array_filter($selectedIds);
                    if (empty($filteredIds)) {
                        return [];
                    }

                    return ['choice_ids' => $filteredIds];
                }

            case 'fill_blank':
                return ['answer' => $shouldBeCorrect ? 'correct_answer' : 'wrong_answer'];

            default:
                return [];
        }
    }
}
