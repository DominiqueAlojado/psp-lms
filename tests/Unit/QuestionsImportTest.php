<?php

namespace Tests\Unit;

use App\Imports\QuestionsImport;
use App\Models\Institution\InstitutionAnswer;
use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionAttempt;
use App\Models\Institution\InstitutionQuestion;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class QuestionsImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_and_import_agree_that_invalid_true_false_rows_are_rejected(): void
    {
        [$organization, $assessment] = $this->createAssessmentFixture();

        $import = new QuestionsImport($assessment->id, $organization->id);

        $import->collection(new Collection([
            collect([
                'question_text' => 'True false sample',
                'type' => 'true_false',
                'points' => 1,
                'choice_1_correct_answer' => 'maybe',
                'choice_2' => 'False',
            ]),
        ]));

        $this->assertSame(0, $import->getSuccessCount());
        $this->assertSame([
            "Row 2: True/False questions must use 'true' or 'false' in choice_1_correct_answer",
        ], $import->getErrors());
    }

    public function test_it_imports_multiple_select_questions_with_multiple_correct_choices_and_grades_them(): void
    {
        [$organization, $assessment, $user] = $this->createAssessmentFixture(withUser: true);

        $import = new QuestionsImport($assessment->id, $organization->id, $user);

        $import->collection(new Collection([
            collect([
                'question_text' => 'Select the digestive organs',
                'type' => 'multiple_select',
                'points' => 2,
                'choice_1_correct_answer' => 'Stomach',
                'choice_2' => 'Liver',
                'choice_3' => 'Heart',
                'choice_4' => 'Lungs',
                'correct_choice_numbers_optional' => '1,2',
            ]),
        ]));

        $this->assertSame(1, $import->getSuccessCount());
        $this->assertSame([], $import->getErrors());

        $question = InstitutionQuestion::query()
            ->where('assessment_id', $assessment->id)
            ->firstOrFail();

        $correctChoices = $question->choices()
            ->where('is_correct', true)
            ->orderBy('order')
            ->pluck('choice_text')
            ->all();

        $this->assertSame(['Stomach', 'Liver'], $correctChoices);

        $attempt = InstitutionAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'total_points' => 2,
        ]);

        $answer = InstitutionAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'answer_data' => [
                'choice_ids' => $question->choices()
                    ->where('is_correct', true)
                    ->pluck('id')
                    ->all(),
            ],
            'answer_change_count' => 1,
        ]);

        $answer->load('question');
        $answer->autoGrade();

        $this->assertTrue($answer->fresh()->is_correct);
        $this->assertSame(2.0, (float) $answer->fresh()->points_earned);
    }

    /**
     * @return array{0: Organization, 1: InstitutionAssessment, 2?: User}
     */
    private function createAssessmentFixture(bool $withUser = false): array
    {
        $organization = Organization::create([
            'name' => 'Alpha Hospital',
            'slug' => 'alpha-hospital',
            'type' => 'institution',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $user->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        $assessment = InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'Import Test Exam',
            'description' => 'Import Test',
            'exam_category' => 'Quiz',
            'duration_minutes' => 30,
            'total_points' => 2,
            'passing_score' => 1,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'is_published' => true,
            'created_by' => $user->id,
        ]);

        return $withUser
            ? [$organization, $assessment, $user]
            : [$organization, $assessment];
    }
}
