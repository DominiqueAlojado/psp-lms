<?php

namespace Tests\Unit;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Organization;
use App\Models\QuestionBank;
use App\Models\User;
use App\Services\InstitutionAssessmentQuestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionAssessmentQuestionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_bank_questions_and_skips_duplicates(): void
    {
        $service = app(InstitutionAssessmentQuestionService::class);

        $organization = Organization::factory()->create([
            'name' => 'Question Service Org',
            'slug' => 'question-service-org',
            'type' => 'institution',
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $assessment = InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'Question Service Exam',
            'duration_minutes' => 60,
            'total_points' => 5,
            'passing_score' => 3,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => false,
            'created_by' => $user->id,
        ]);

        $assessment->questions()->create([
            'question_type' => 'multiple_choice',
            'question_text' => '<p>Existing question</p>',
            'points' => 5,
            'order' => 0,
        ]);

        $duplicateBankQuestion = QuestionBank::create([
            'organization_id' => $organization->id,
            'owner_type' => 'institution',
            'created_by' => $user->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Existing question',
            'points' => 5,
            'is_approved' => true,
        ]);

        $duplicateBankQuestion->choices()->createMany([
            ['choice_text' => 'Correct', 'is_correct' => true, 'order' => 0],
            ['choice_text' => 'Wrong', 'is_correct' => false, 'order' => 1],
        ]);

        $newBankQuestion = QuestionBank::create([
            'organization_id' => $organization->id,
            'owner_type' => 'institution',
            'created_by' => $user->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'New service question',
            'points' => 4,
            'is_approved' => true,
        ]);

        $newBankQuestion->choices()->createMany([
            ['choice_text' => 'Correct', 'is_correct' => true, 'order' => 0],
            ['choice_text' => 'Wrong', 'is_correct' => false, 'order' => 1],
        ]);

        $result = $service->addFromBank($assessment, [
            $duplicateBankQuestion->id,
            $newBankQuestion->id,
        ]);

        $this->assertSame([
            'added_count' => 1,
            'skipped_count' => 1,
        ], $result);
        $this->assertSame(2, $assessment->fresh()->questions()->count());
        $this->assertSame(9, (int) $assessment->fresh()->total_points);
        $this->assertDatabaseHas('institution_questions', [
            'assessment_id' => $assessment->id,
            'question_text' => 'New service question',
        ]);
    }

    public function test_it_bulk_deletes_questions_and_recomputes_total_points(): void
    {
        $service = app(InstitutionAssessmentQuestionService::class);

        $organization = Organization::factory()->create([
            'name' => 'Delete Service Org',
            'slug' => 'delete-service-org',
            'type' => 'institution',
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $assessment = InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'Delete Service Exam',
            'duration_minutes' => 60,
            'total_points' => 10,
            'passing_score' => 5,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => false,
            'created_by' => $user->id,
        ]);

        $questionOne = $assessment->questions()->create([
            'question_type' => 'multiple_choice',
            'question_text' => 'Question one',
            'points' => 4,
            'order' => 0,
        ]);

        $questionTwo = $assessment->questions()->create([
            'question_type' => 'multiple_choice',
            'question_text' => 'Question two',
            'points' => 6,
            'order' => 1,
        ]);

        $count = $service->deleteQuestions($assessment, [
            $questionOne->id,
            $questionTwo->id,
        ]);

        $this->assertSame(2, $count);
        $this->assertSoftDeleted('institution_questions', ['id' => $questionOne->id]);
        $this->assertSoftDeleted('institution_questions', ['id' => $questionTwo->id]);
        $this->assertSame(0, (int) $assessment->fresh()->total_points);
    }

    public function test_it_saves_one_question_and_syncs_it_to_question_bank(): void
    {
        $service = app(InstitutionAssessmentQuestionService::class);

        $organization = Organization::factory()->create([
            'name' => 'Save Question Org',
            'slug' => 'save-question-org',
            'type' => 'institution',
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $assessment = InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'Save Question Exam',
            'duration_minutes' => 60,
            'total_points' => 0,
            'passing_score' => 3,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => false,
            'created_by' => $user->id,
        ]);

        $question = $service->saveQuestion($assessment, [
            'topic_id' => null,
            'question_type' => 'multiple_choice',
            'question_text' => 'Saved service question',
            'points' => 4,
            'order' => 0,
            'choices' => [
                ['choice_text' => 'Correct', 'is_correct' => true],
                ['choice_text' => 'Wrong', 'is_correct' => false],
            ],
        ], $user);

        $this->assertNotNull($question->id);
        $this->assertSame(4, (int) $assessment->fresh()->total_points);
        $this->assertDatabaseHas('institution_questions', [
            'id' => $question->id,
            'assessment_id' => $assessment->id,
            'question_text' => 'Saved service question',
        ]);
        $this->assertDatabaseHas('question_bank', [
            'organization_id' => $organization->id,
            'owner_type' => 'institution',
            'created_by' => $user->id,
            'question_text' => 'Saved service question',
            'points' => 4,
        ]);
    }

    public function test_it_stores_questions_and_soft_deletes_missing_questions(): void
    {
        $service = app(InstitutionAssessmentQuestionService::class);

        $organization = Organization::factory()->create([
            'name' => 'Store Questions Org',
            'slug' => 'store-questions-org',
            'type' => 'institution',
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $assessment = InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'Store Questions Exam',
            'duration_minutes' => 60,
            'total_points' => 6,
            'passing_score' => 3,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => false,
            'created_by' => $user->id,
        ]);

        $keptQuestion = $assessment->questions()->create([
            'question_type' => 'multiple_choice',
            'question_text' => 'Keep me',
            'points' => 2,
            'order' => 0,
        ]);

        $removedQuestion = $assessment->questions()->create([
            'question_type' => 'multiple_choice',
            'question_text' => 'Remove me',
            'points' => 4,
            'order' => 1,
        ]);

        $service->storeQuestions($assessment, [[
            'id' => $keptQuestion->id,
            'topic_id' => null,
            'question_type' => 'multiple_choice',
            'question_text' => 'Keep me updated',
            'points' => 5,
            'order' => 0,
            'choices' => [
                ['choice_text' => 'Correct', 'is_correct' => true],
                ['choice_text' => 'Wrong', 'is_correct' => false],
            ],
        ]]);

        $this->assertDatabaseHas('institution_questions', [
            'id' => $keptQuestion->id,
            'question_text' => 'Keep me updated',
            'points' => 5,
        ]);
        $this->assertSoftDeleted('institution_questions', ['id' => $removedQuestion->id]);
        $this->assertSame(5, (int) $assessment->fresh()->total_points);
    }
}
