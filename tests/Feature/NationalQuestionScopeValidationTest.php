<?php

namespace Tests\Feature;

use App\Models\National\NationalAssessment;
use App\Models\National\NationalQuestion;
use App\Models\National\NationalQuestionChoice;
use App\Models\Organization;
use App\Models\QuestionBank;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NationalQuestionScopeValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_one_question_rejects_foreign_assessment_question_id(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        [$user, $assessment] = $this->createNationalEditorFixture();

        $otherAssessment = NationalAssessment::create([
            'title' => 'Other National Exam',
            'description' => 'Other',
            'exam_year' => now()->year,
            'exam_period' => 'Q1',
            'category' => 'anatomic-pathology-theoretical',
            'duration_minutes' => 30,
            'total_points' => 0,
            'passing_score' => 1,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => false,
            'national_ranking_enabled' => true,
            'institution_comparison_enabled' => true,
            'created_by' => $user->id,
        ]);

        $foreignQuestion = NationalQuestion::create([
            'assessment_id' => $otherAssessment->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Foreign national question',
            'points' => 1,
            'order' => 1,
        ]);

        $response = $this->actingAs($user)
            ->from(route('inservice-exams.edit', $assessment))
            ->post(route('inservice-exams.questions.save-one', $assessment), [
                'id' => $foreignQuestion->id,
                'question_type' => 'multiple_choice',
                'question_text' => 'Should fail',
                'points' => 1,
                'choices' => [
                    ['choice_text' => 'A', 'is_correct' => true],
                    ['choice_text' => 'B', 'is_correct' => false],
                ],
            ]);

        $response->assertSessionHasErrors('id');
    }

    public function test_save_one_question_rejects_foreign_assessment_choice_id(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        [$user, $assessment] = $this->createNationalEditorFixture();

        $otherAssessment = NationalAssessment::create([
            'title' => 'Other National Exam',
            'description' => 'Other',
            'exam_year' => now()->year,
            'exam_period' => 'Q1',
            'category' => 'anatomic-pathology-theoretical',
            'duration_minutes' => 30,
            'total_points' => 0,
            'passing_score' => 1,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => false,
            'national_ranking_enabled' => true,
            'institution_comparison_enabled' => true,
            'created_by' => $user->id,
        ]);

        $foreignQuestion = NationalQuestion::create([
            'assessment_id' => $otherAssessment->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Foreign national question',
            'points' => 1,
            'order' => 1,
        ]);

        $foreignChoice = NationalQuestionChoice::create([
            'question_id' => $foreignQuestion->id,
            'choice_text' => 'Foreign choice',
            'is_correct' => true,
            'order' => 1,
        ]);

        $response = $this->actingAs($user)
            ->from(route('inservice-exams.edit', $assessment))
            ->post(route('inservice-exams.questions.save-one', $assessment), [
                'question_type' => 'multiple_choice',
                'question_text' => 'Should fail',
                'points' => 1,
                'choices' => [
                    ['id' => $foreignChoice->id, 'choice_text' => 'A', 'is_correct' => true],
                    ['choice_text' => 'B', 'is_correct' => false],
                ],
            ]);

        $response->assertSessionHasErrors('choices.0.id');
    }

    public function test_add_from_bank_rejects_institution_question_bank_ids(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        [$user, $assessment] = $this->createNationalEditorFixture();

        $institutionOrganization = Organization::create([
            'name' => 'Institution Org',
            'slug' => 'institution-org',
            'type' => 'institution',
            'is_active' => true,
        ]);

        $foreignQuestion = QuestionBank::create([
            'organization_id' => $institutionOrganization->id,
            'owner_type' => 'institution',
            'created_by' => $user->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Institution bank question',
            'points' => 1,
            'is_approved' => true,
        ]);

        $response = $this->actingAs($user)
            ->from(route('inservice-exams.edit', $assessment))
            ->post(route('inservice-exams.questions.from-bank', $assessment), [
                'question_ids' => [$foreignQuestion->id],
            ]);

        $response->assertSessionHasErrors('question_ids.0');
    }

    public function test_save_one_question_rejects_foreign_topic_id(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        [$user, $assessment] = $this->createNationalEditorFixture();

        $foreignOrganization = Organization::create([
            'name' => 'Foreign Institution',
            'slug' => 'foreign-institution',
            'type' => 'institution',
            'is_active' => true,
        ]);

        $foreignTopic = Topic::create([
            'name' => 'Foreign Topic',
            'slug' => 'foreign-topic',
            'organization_id' => $foreignOrganization->id,
            'is_global' => false,
        ]);

        $response = $this->actingAs($user)
            ->from(route('inservice-exams.edit', $assessment))
            ->post(route('inservice-exams.questions.save-one', $assessment), [
                'topic_id' => $foreignTopic->id,
                'question_type' => 'multiple_choice',
                'question_text' => 'Should fail',
                'points' => 1,
                'choices' => [
                    ['choice_text' => 'A', 'is_correct' => true],
                    ['choice_text' => 'B', 'is_correct' => false],
                ],
            ]);

        $response->assertSessionHasErrors('topic_id');
    }

    /**
     * @return array{0: User, 1: NationalAssessment}
     */
    private function createNationalEditorFixture(): array
    {
        $organization = Organization::create([
            'name' => 'In-Service Exams',
            'slug' => 'in-service-exams',
            'type' => 'national',
            'is_active' => true,
        ]);

        $role = Role::firstOrCreate(['name' => 'System Admin', 'guard_name' => 'web']);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $user->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);
        $user->assignRole($role);

        $assessment = NationalAssessment::create([
            'title' => 'National Scoped Exam',
            'description' => 'Scoped',
            'exam_year' => now()->year,
            'exam_period' => 'Q1',
            'category' => 'anatomic-pathology-theoretical',
            'duration_minutes' => 30,
            'total_points' => 0,
            'passing_score' => 1,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => false,
            'national_ranking_enabled' => true,
            'institution_comparison_enabled' => true,
            'created_by' => $user->id,
        ]);

        return [$user, $assessment];
    }
}
