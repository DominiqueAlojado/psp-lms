<?php

namespace Database\Seeders;

use App\Models\National\NationalAssessment;
use App\Models\National\NationalQuestion;
use App\Models\National\NationalQuestionChoice;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Database\Seeder;

class InServiceExamPart2Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $creator = User::query()->first();

        if (! $creator) {
            $this->command->error('No user found. Please seed users first (SystemAdminSeeder, StaffSeeder, or ResidentSeeder).');

            return;
        }

        $creatorId = $creator->id;
        $year = (int) now()->year;

        $exams = [
            [
                'title' => 'Anatomic Pathology (Theoretical) 2',
                'exam_period' => 'Annual',
                'category' => 'anatomic-pathology-theoretical',
            ],
            [
                'title' => 'Clinical Pathology (Theoretical) 2',
                'exam_period' => 'Annual',
                'category' => 'clinical-pathology-theoretical',
            ],
        ];

        foreach ($exams as $exam) {
            $assessment = NationalAssessment::query()->firstOrCreate(
                [
                    'title' => $exam['title'],
                    'exam_year' => $year,
                    'exam_period' => $exam['exam_period'],
                ],
                [
                    'description' => null,
                    'category' => $exam['category'],
                    'duration_minutes' => 60,
                    'total_points' => 0,
                    'passing_score' => 0,
                    'randomize_questions' => false,
                    'randomize_choices' => false,
                    'show_results_immediately' => true,
                    'allow_review' => true,
                    'is_published' => true,
                    'national_ranking_enabled' => true,
                    'institution_comparison_enabled' => true,
                    'scheduled_date' => null,
                    'results_release_date' => null,
                    'created_by' => $creatorId,
                ],
            );

            // Ensure existing exams are also set to published/active
            if (! $assessment->is_published) {
                $assessment->update(['is_published' => true]);
            }

            // Only add questions if the exam doesn't have any yet
            if ($assessment->questions()->count() === 0) {
                $this->command->info("Adding questions from question bank to: {$exam['title']}");

                // Get questions from national question bank
                // Filter by category-related topics if possible, or just get approved national questions
                $bankQuestions = QuestionBank::with('choices')
                    ->where('owner_type', 'national')
                    ->where('is_approved', true)
                    ->inRandomOrder()
                    ->limit(30)
                    ->get();

                if ($bankQuestions->isEmpty()) {
                    $this->command->warn("No approved questions found in question bank for {$exam['title']}. Please run InServiceQuestionBankSeeder first.");

                    continue;
                }

                $totalPoints = 0;
                $addedCount = 0;

                foreach ($bankQuestions as $index => $bankQuestion) {
                    try {
                        // Create question in national_questions table
                        $question = NationalQuestion::create([
                            'assessment_id' => $assessment->id,
                            'question_type' => $bankQuestion->question_type,
                            'question_text' => $bankQuestion->question_text,
                            'points' => $bankQuestion->points ?? 1,
                            'explanation' => $bankQuestion->explanation,
                            'image_path' => $bankQuestion->image_path,
                            'difficulty_level' => $bankQuestion->difficulty_level,
                            'topic' => $bankQuestion->topic?->name,
                            'order' => $index,
                        ]);

                        // Copy choices from question bank
                        foreach ($bankQuestion->choices as $bankChoice) {
                            NationalQuestionChoice::create([
                                'question_id' => $question->id,
                                'choice_text' => $bankChoice->choice_text,
                                'is_correct' => $bankChoice->is_correct,
                                'order' => $bankChoice->order,
                            ]);
                        }

                        // Increment usage counter in question bank
                        try {
                            $bankQuestion->incrementUsage();
                        } catch (\Exception $e) {
                            $this->command->warn("Failed to increment usage for question {$bankQuestion->id}: {$e->getMessage()}");
                        }

                        $totalPoints += $question->points;
                        $addedCount++;
                    } catch (\Exception $e) {
                        $this->command->error("Failed to add question {$bankQuestion->id} to exam: {$e->getMessage()}");

                        continue;
                    }
                }

                // Update assessment total points
                $assessment->update(['total_points' => $totalPoints]);

                $this->command->info("✅ Added {$addedCount} questions to {$exam['title']} (Total points: {$totalPoints})");
            } else {
                $this->command->info("⏭️  {$exam['title']} already has questions, skipping...");
            }
        }
    }
}
