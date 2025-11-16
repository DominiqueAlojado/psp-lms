<?php

namespace Database\Seeders;

use App\Models\National\NationalAssessment;
use App\Models\National\NationalQuestion;
use App\Models\National\NationalQuestionChoice;
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
                    'is_published' => false,
                    'national_ranking_enabled' => true,
                    'institution_comparison_enabled' => true,
                    'scheduled_date' => null,
                    'results_release_date' => null,
                    'created_by' => $creatorId,
                ],
            );

            // Seed 50 realistic MCQ items if none exist yet
            if ($assessment->questions()->count() === 0) {
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

                $totalPoints = 0;
                for ($i = 0; $i < 50; $i++) {
                    $topic = $topics[$i % count($topics)];
                    $stemTemplate = $stems[$i % count($stems)];
                    $stem = str_replace('{topic}', $topic, $stemTemplate);

                    $question = NationalQuestion::create([
                        'assessment_id' => $assessment->id,
                        'question_type' => 'multiple_choice',
                        'question_text' => $stem,
                        'points' => 1,
                        'explanation' => null,
                        'difficulty_level' => ['easy', 'medium', 'hard'][$i % 3],
                        'topic' => $topic,
                        'order' => $i,
                    ]);

                    // Build 4 choices with one correct
                    $bank = $choiceBanks[$i % count($choiceBanks)];
                    $correctIndex = $i % 4;
                    foreach ($bank as $ci => $text) {
                        NationalQuestionChoice::create([
                            'question_id' => $question->id,
                            'choice_text' => $text,
                            'is_correct' => $ci === $correctIndex,
                            'order' => $ci,
                        ]);
                    }

                    $totalPoints += 1;
                }

                // Update assessment total points
                $assessment->update(['total_points' => $totalPoints]);
            }
        }
    }
}
