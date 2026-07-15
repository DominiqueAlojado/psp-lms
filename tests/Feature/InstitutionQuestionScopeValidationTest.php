<?php

namespace Tests\Feature;

use App\Http\Middleware\SetOrganizationFromUrl;
use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionQuestion;
use App\Models\Organization;
use App\Models\QuestionBank;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InstitutionQuestionScopeValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_one_question_rejects_foreign_assessment_question_id(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        [$user, $assessment] = $this->createEditorFixture();

        $otherAssessment = InstitutionAssessment::create([
            'organization_id' => $assessment->organization_id,
            'title' => 'Other Exam',
            'description' => 'Other',
            'exam_category' => 'Quiz',
            'duration_minutes' => 30,
            'total_points' => 1,
            'passing_score' => 1,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'is_published' => false,
            'created_by' => $user->id,
        ]);

        $foreignQuestion = InstitutionQuestion::create([
            'assessment_id' => $otherAssessment->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Foreign question',
            'points' => 1,
            'order' => 1,
        ]);

        $response = $this->actingAs($user)
            ->from(route('institution-exams.edit', $assessment))
            ->post(route('assessments.questions.save-one', $assessment), [
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

    public function test_save_one_question_rejects_foreign_topic_id(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        [$user, $assessment] = $this->createEditorFixture();

        $otherOrganization = Organization::create([
            'name' => 'Other Hospital',
            'slug' => 'other-hospital',
            'type' => 'institution',
            'is_active' => true,
        ]);

        $foreignTopic = Topic::create([
            'name' => 'Foreign Topic',
            'slug' => 'foreign-topic',
            'organization_id' => $otherOrganization->id,
            'is_global' => false,
        ]);

        $response = $this->actingAs($user)
            ->from(route('institution-exams.edit', $assessment))
            ->post(route('assessments.questions.save-one', $assessment), [
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

    public function test_add_from_bank_rejects_foreign_organization_question_bank_ids(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        [$user, $assessment] = $this->createEditorFixture();

        $otherOrganization = Organization::create([
            'name' => 'Other Hospital',
            'slug' => 'other-hospital',
            'type' => 'institution',
            'is_active' => true,
        ]);

        $foreignQuestion = QuestionBank::create([
            'organization_id' => $otherOrganization->id,
            'owner_type' => 'institution',
            'created_by' => $user->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Foreign bank question',
            'points' => 1,
            'is_approved' => true,
        ]);

        $response = $this->actingAs($user)
            ->from(route('institution-exams.edit', $assessment))
            ->post(route('assessments.questions.from-bank', $assessment), [
                'question_ids' => [$foreignQuestion->id],
            ]);

        $response->assertSessionHasErrors('question_ids.0');
    }

    /**
     * @return array{0: User, 1: InstitutionAssessment}
     */
    private function createEditorFixture(): array
    {
        $organization = Organization::create([
            'name' => 'Alpha Hospital',
            'slug' => 'alpha-hospital',
            'type' => 'institution',
            'is_active' => true,
        ]);

        Permission::firstOrCreate(['name' => 'edit-assessments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create-assessments', 'guard_name' => 'web']);

        $role = Role::firstOrCreate(['name' => 'Training Officer', 'guard_name' => 'web']);
        $role->givePermissionTo(['edit-assessments', 'create-assessments']);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $user->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);
        $user->assignRole($role);

        $assessment = InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'Scoped Exam',
            'description' => 'Scoped',
            'exam_category' => 'Quiz',
            'duration_minutes' => 30,
            'total_points' => 0,
            'passing_score' => 1,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'is_published' => false,
            'created_by' => $user->id,
        ]);

        return [$user, $assessment];
    }
}
