<?php

namespace Database\Seeders;

use App\Models\National\NationalAssessment;
use App\Models\National\NationalQuestion;
use App\Models\National\NationalQuestionChoice;
use App\Models\QuestionBank;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;

class InServiceExamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $creatorId = User::query()->value('id') ?? 1;
        $year = (int) now()->year;

        $exams = [
            [
                'title' => 'Anatomic Pathology (Theoretical)',
                'exam_period' => 'Annual',
                'category' => 'anatomic-pathology-theoretical',
            ],
            [
                'title' => 'Clinical Pathology (Theoretical)',
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
                    'is_published' => true, // Set to true to make exams active
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

            // Seed 50 realistic MCQ items if none exist yet
            if ($assessment->questions()->count() === 0) {
                $this->command->info("Adding questions from question bank to: {$exam['title']}");

                // Get or create topics
                $topics = $exam['category'] === 'anatomic-pathology-theoretical'
                    ? [
                        'Breast pathology',
                        'GI tract',
                        'Liver',
                        'Kidney',
                        'Lung',
                        'Bone and soft tissue',
                        'Gyn pathology',
                        'Neuropathology',
                        'Dermatopathology',
                        'Endocrine',
                    ]
                    : [
                        'Hematology',
                        'Clinical chemistry',
                        'Microbiology',
                        'Immunology',
                        'Transfusion medicine',
                        'Toxicology',
                        'Urinalysis',
                        'Parasitology',
                        'Virology',
                        'Molecular diagnostics',
                    ];

                $stems = [
                    'Which of the following is most consistent with {topic} diagnosis shown in routine practice?',
                    'A key feature of {topic} is which of the following findings?',
                    'Which marker is most specific for the following scenario in {topic}?',
                    'In {topic}, the most likely complication of the described process is:',
                    'Which organism or analyte best explains the laboratory pattern in {topic}?',
                    'Which statement about {topic} is TRUE?',
                    'Which histologic change is expected in {topic}?',
                    'Which test is most appropriate next step in {topic}?',
                ];

                $choiceBanks = [
                    ['Keratin 7 positive', 'CDX2 positive', 'TTF-1 positive', 'PAX8 positive'],
                    ['Neutrophilia', 'Eosinophilia', 'Basophilia', 'Lymphocytosis'],
                    ['Gram-positive cocci in clusters', 'Gram-negative rods', 'Acid-fast bacilli', 'Encapsulated yeasts'],
                    ['ALT predominance', 'AST predominance', 'Alkaline phosphatase predominance', 'Normal transaminases'],
                    ['PAS-positive diastase-resistant inclusions', 'Congo red birefringence', 'Ziehl–Neelsen positivity', 'Grocott methenamine silver negativity'],
                    ['KRAS mutation', 'BRAF V600E mutation', 'EGFR exon 19 deletion', 'JAK2 V617F mutation'],
                ];

                // Get or create global topics
                $topicModels = Topic::where('is_global', true)->get();
                $topicLookup = $topicModels->pluck('id', 'name');
                
                // Ensure topics exist
                foreach ($topics as $topicName) {
                    if (! $topicLookup->has($topicName)) {
                        $topic = Topic::create([
                            'name' => $topicName,
                            'slug' => \Illuminate\Support\Str::slug($topicName),
                            'description' => null,
                            'organization_id' => null,
                            'is_global' => true,
                        ]);
                        $topicLookup->put($topicName, $topic->id);
                    }
                }

                $totalPoints = 0;
                $addedCount = 0;

                // First, ensure questions exist in question bank
                for ($i = 0; $i < 50; $i++) {
                    $topic = $topics[$i % count($topics)];
                    $stemTemplate = $stems[$i % count($stems)];
                    $stem = str_replace('{topic}', $topic, $stemTemplate);
                    $topicId = $topicLookup->get($topic);

                    // Check if question already exists in question bank (must be national type)
                    $bankQuestion = QuestionBank::where('question_text', $stem)
                        ->where('owner_type', 'national')
                        ->whereNull('organization_id')
                        ->first();

                    // Create question in question bank if it doesn't exist
                    if (! $bankQuestion) {
                        $bankQuestion = QuestionBank::create([
                            'organization_id' => null, // National questions don't belong to a specific organization
                            'owner_type' => 'national', // Explicitly set as national/in-service exam question
                            'topic_id' => $topicId,
                            'created_by' => $creatorId,
                            'question_type' => 'multiple_choice',
                            'question_text' => $stem,
                            'points' => 1,
                            'explanation' => null,
                            'difficulty_level' => ['easy', 'medium', 'hard'][$i % 3],
                            'is_approved' => true,
                            'approved_by' => $creatorId,
                            'approved_at' => now(),
                        ]);

                        // Create choices in question bank
                        $bank = $choiceBanks[$i % count($choiceBanks)];
                        $correctIndex = $i % 4;
                        foreach ($bank as $ci => $text) {
                            $bankQuestion->choices()->create([
                                'choice_text' => $text,
                                'is_correct' => $ci === $correctIndex,
                                'order' => $ci + 1,
                            ]);
                        }

                        // Initialize statistics in question bank (must match owner_type)
                        $bankQuestion->statistics()->create([
                            'question_id' => $bankQuestion->id,
                            'scope' => 'national', // Must match owner_type ('national')
                            'institution_id' => null, // National questions have no institution
                        ]);
                    }

                    // Now create question in exam from question bank
                    $question = NationalQuestion::create([
                        'assessment_id' => $assessment->id,
                        'question_type' => $bankQuestion->question_type,
                        'question_text' => $bankQuestion->question_text,
                        'points' => $bankQuestion->points ?? 1,
                        'explanation' => $bankQuestion->explanation,
                        'image_path' => $bankQuestion->image_path,
                        'difficulty_level' => $bankQuestion->difficulty_level,
                        'topic' => $topic,
                        'order' => $i,
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
                    $bankQuestion->incrementUsage();

                    $totalPoints += $question->points;
                    $addedCount++;
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
