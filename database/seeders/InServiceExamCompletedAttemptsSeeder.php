<?php

namespace Database\Seeders;

use App\Models\National\NationalAnswer;
use App\Models\National\NationalAssessment;
use App\Models\National\NationalAttempt;
use App\Models\National\NationalQuestion;
use App\Models\QuestionBank;
use App\Models\Resident;
use Illuminate\Database\Seeder;

class InServiceExamCompletedAttemptsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all active residents with user accounts
        $residents = Resident::with('user')
            ->whereHas('user')
            ->where('status', 'active')
            ->get();

        if ($residents->isEmpty()) {
            $this->command->warn('No active residents found. Please run ResidentSeeder first.');

            return;
        }

        // Get one Anatomic Pathology exam and one Clinical Pathology exam
        $anatomicExam = NationalAssessment::where('category', 'anatomic-pathology-theoretical')
            ->where('is_published', true)
            ->with('questions.choices')
            ->first();

        $clinicalExam = NationalAssessment::where('category', 'clinical-pathology-theoretical')
            ->where('is_published', true)
            ->with('questions.choices')
            ->first();

        if (! $anatomicExam || ! $clinicalExam) {
            $this->command->error('Required exams not found. Please run InServiceExamSeeder first.');

            return;
        }

        if ($anatomicExam->questions()->count() === 0 || $clinicalExam->questions()->count() === 0) {
            $this->command->error('Exams do not have questions. Please ensure exams have questions.');

            return;
        }

        $this->command->info("Found {$residents->count()} active resident(s)");
        $this->command->info("Anatomic Pathology exam: {$anatomicExam->title} ({$anatomicExam->questions()->count()} questions)");
        $this->command->info("Clinical Pathology exam: {$clinicalExam->title} ({$clinicalExam->questions()->count()} questions)");
        $this->command->newLine();

        $totalAttemptsCreated = 0;
        $totalAnswersCreated = 0;

        foreach ($residents as $resident) {
            $user = $resident->user;

            if (! $user) {
                continue;
            }

            // Process Anatomic Pathology exam
            $anatomicAttempt = $this->createCompletedAttempt($user, $resident, $anatomicExam);
            if ($anatomicAttempt) {
                $totalAttemptsCreated++;
                $totalAnswersCreated += $anatomicAttempt->answers()->count();
            }

            // Process Clinical Pathology exam
            $clinicalAttempt = $this->createCompletedAttempt($user, $resident, $clinicalExam);
            if ($clinicalAttempt) {
                $totalAttemptsCreated++;
                $totalAnswersCreated += $clinicalAttempt->answers()->count();
            }
        }

        $this->command->newLine();
        $this->command->info("✅ Created {$totalAttemptsCreated} completed exam attempts");
        $this->command->info("✅ Created {$totalAnswersCreated} answers");
        $this->command->info('✅ Question bank statistics have been updated');

        // Recalculate discrimination indices for all questions with enough attempts
        $this->command->newLine();
        $this->command->info('Calculating discrimination indices...');
        $this->recalculateDiscriminationIndices();
    }

    /**
     * Recalculate discrimination indices for all questions with 10+ attempts.
     */
    private function recalculateDiscriminationIndices(): void
    {
        // Process national questions
        $nationalStats = \App\Models\QuestionBankStatistic::where('scope', 'national')
            ->whereNull('institution_id')
            ->where('times_answered', '>=', 10)
            ->with('question')
            ->get();

        // Process institution questions
        $institutionStats = \App\Models\QuestionBankStatistic::where('scope', 'institution')
            ->whereNotNull('institution_id')
            ->where('times_answered', '>=', 10)
            ->with('question')
            ->get();

        $totalStats = $nationalStats->count() + $institutionStats->count();
        $this->command->info("Found {$totalStats} question statistics with 10+ attempts to process ({$nationalStats->count()} national, {$institutionStats->count()} institution).");

        $processed = 0;
        $updated = 0;

        // Process all statistics (national and institution)
        foreach ($nationalStats->concat($institutionStats) as $stat) {
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
                $this->command->warn("Failed to calculate discrimination for question {$question->id}: {$e->getMessage()}");
                continue;
            }
        }

        $this->command->info("✅ Processed {$processed} statistics and updated discrimination indices for {$updated} questions.");
    }

    /**
     * Create a completed exam attempt with answers for a resident.
     */
    private function createCompletedAttempt($user, $resident, NationalAssessment $exam): ?NationalAttempt
    {
        // Check if a completed attempt already exists
        $existingCompleted = NationalAttempt::where('assessment_id', $exam->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['completed', 'graded'])
            ->first();

        if ($existingCompleted) {
            return null; // Skip if already completed
        }

        // Delete any unstarted attempts for this exam/user
        NationalAttempt::where('assessment_id', $exam->id)
            ->where('user_id', $user->id)
            ->whereNull('started_at')
            ->delete();

        // Also delete any started attempts with no answers (abandoned attempts)
        NationalAttempt::where('assessment_id', $exam->id)
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->whereNotNull('started_at')
            ->whereDoesntHave('answers')
            ->delete();

        // Get all questions with choices FIRST before creating attempt
        $questions = $exam->questions()->with('choices')->orderBy('order')->get();

        if ($questions->isEmpty()) {
            $this->command->warn("No questions found for exam: {$exam->title}, skipping...");

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
            $this->command->warn("No valid questions with choices found for exam: {$exam->title}, skipping...");

            return null;
        }

        // Create a new completed attempt
        $startedAt = now()->subMinutes(rand(30, 60)); // Started 30-60 minutes ago
        $submittedAt = $startedAt->copy()->addMinutes(rand(20, 50)); // Submitted 20-50 minutes after start

        $attempt = NationalAttempt::create([
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

        $answersCreated = 0;

        // Create answers for each question (mix of correct and incorrect)
        foreach ($questions as $question) {
            try {
                // Skip questions without choices (for multiple choice questions)
                if (in_array($question->question_type, ['multiple_choice', 'multiple_select', 'true_false'])) {
                    $choices = $question->choices;
                    if ($choices->isEmpty()) {
                        $this->command->warn("Skipping question {$question->id} - no choices found");
                        continue;
                    }
                }

                $isCorrect = (rand(1, 100) <= 65); // 65% chance of correct answer (realistic performance)

                $answerData = $this->generateAnswerData($question, $isCorrect);

                // Validate answer data is not empty
                if (empty($answerData) || (isset($answerData['choice_id']) && $answerData['choice_id'] === null)) {
                    $this->command->warn("Skipping question {$question->id} - invalid answer data generated");
                    continue;
                }

                $answer = NationalAnswer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'answer_data' => $answerData,
                    'answer_change_count' => rand(0, 2), // 0-2 changes
                ]);

                // Auto-grade the answer
                $answer->autoGrade();

                // Update question bank statistics if question exists in bank
                $this->updateQuestionBankStatistics($question, $answer->is_correct);

                $answersCreated++;
            } catch (\Exception $e) {
                $this->command->error("Failed to create answer for question {$question->id}: {$e->getMessage()}");
                continue; // Skip this question and continue with others
            }
        }

        // If no answers were created, delete the attempt
        if ($answersCreated === 0) {
            $this->command->warn("No answers created for attempt {$attempt->id}, deleting attempt");
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
    private function generateAnswerData(NationalQuestion $question, bool $shouldBeCorrect): array
    {
        switch ($question->question_type) {
            case 'multiple_choice':
            case 'true_false':
                $choices = $question->choices()->orderBy('order')->get();

                if ($choices->isEmpty()) {
                    return []; // Return empty if no choices
                }

                if ($shouldBeCorrect) {
                    $correctChoice = $choices->firstWhere('is_correct', true);
                    $choiceId = $correctChoice?->id ?? $choices->first()?->id;
                } else {
                    // Pick a wrong answer
                    $wrongChoices = $choices->where('is_correct', false);
                    $choiceId = $wrongChoices->isNotEmpty()
                        ? $wrongChoices->random()->id
                        : $choices->first()?->id;
                }

                if ($choiceId === null) {
                    return []; // Return empty if no valid choice ID
                }

                return ['choice_id' => $choiceId];

            case 'multiple_select':
                $choices = $question->choices()->orderBy('order')->get();

                if ($choices->isEmpty()) {
                    return []; // Return empty if no choices
                }

                $correctChoiceIds = $choices->where('is_correct', true)->pluck('id')->toArray();

                if ($shouldBeCorrect) {
                    return ['choice_ids' => $correctChoiceIds];
                } else {
                    // Pick some wrong answers (maybe missing one or adding wrong one)
                    $wrongChoices = $choices->where('is_correct', false);
                    if ($wrongChoices->isNotEmpty() && count($correctChoiceIds) > 1) {
                        // Remove one correct answer
                        $selectedIds = array_slice($correctChoiceIds, 0, -1);
                    } elseif ($wrongChoices->isNotEmpty()) {
                        // Add a wrong answer
                        $wrongId = $wrongChoices->first()?->id;
                        $selectedIds = $wrongId ? array_merge($correctChoiceIds, [$wrongId]) : $correctChoiceIds;
                    } else {
                        // No wrong choices, just use correct ones
                        $selectedIds = $correctChoiceIds;
                    }

                    $filteredIds = array_filter($selectedIds);
                    if (empty($filteredIds)) {
                        return []; // Return empty if no valid IDs
                    }

                    return ['choice_ids' => $filteredIds];
                }

            case 'fill_blank':
                // For fill_blank, we'd need the correct answer stored somewhere
                // For now, just return a placeholder
                return ['answer' => $shouldBeCorrect ? 'correct_answer' : 'wrong_answer'];

            default:
                return [];
        }
    }

    /**
     * Update question bank statistics for a question.
     */
    private function updateQuestionBankStatistics(NationalQuestion $question, bool $wasCorrect): void
    {
        // Try to find the question in the question bank by matching question text
        $bankQuestion = QuestionBank::where('question_text', $question->question_text)
            ->where('owner_type', 'national')
            ->first();

        if (! $bankQuestion) {
            return; // Question not in bank, skip
        }

        // Ensure statistics record exists
        $stats = $bankQuestion->statistics()->firstOrCreate(
            [
                'question_id' => $bankQuestion->id,
                'scope' => 'national',
                'institution_id' => null,
            ],
            [
                'times_used_in_exams' => 0,
                'times_answered' => 0,
                'times_correct' => 0,
                'times_incorrect' => 0,
                'success_rate' => 0,
            ]
        );

        // Update statistics
        $timeSeconds = rand(30, 180); // Random time between 30-180 seconds per question
        $bankQuestion->updateStatistics($wasCorrect, $timeSeconds);
    }
}
