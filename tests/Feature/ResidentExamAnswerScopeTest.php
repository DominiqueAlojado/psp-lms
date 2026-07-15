<?php

namespace Tests\Feature;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionAttempt;
use App\Models\Institution\InstitutionQuestion;
use App\Models\Institution\InstitutionQuestionChoice;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentExamAnswerScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_answers_for_questions_from_another_exam(): void
    {
        [$user, $organization] = $this->makeResidentUser();
        $assessment = $this->makeAssessment($organization, $user, 'Exam A');
        $otherAssessment = $this->makeAssessment($organization, $user, 'Exam B');
        $foreignQuestion = $this->makeQuestion($otherAssessment, 'Foreign question');

        $attempt = InstitutionAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'year_level' => 'First Year',
            'organization_id' => $organization->id,
            'started_at' => now(),
            'total_points' => $assessment->total_points,
            'status' => 'in_progress',
            'last_activity_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson("/exams/institution/{$attempt->id}/save-answer", [
            'question_id' => $foreignQuestion->id,
            'answer_data' => ['choice_id' => $foreignQuestion->choices()->first()->id],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'The selected question does not belong to this exam attempt.',
            ]);

        $this->assertDatabaseMissing('institution_answers', [
            'attempt_id' => $attempt->id,
            'question_id' => $foreignQuestion->id,
        ]);
    }

    private function makeResidentUser(): array
    {
        $organization = Organization::create([
            'name' => 'Scope Hospital',
            'slug' => 'scope-hospital',
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

        return [$user, $organization];
    }

    private function makeAssessment(Organization $organization, User $user, string $title): InstitutionAssessment
    {
        return InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => $title,
            'description' => 'Scope test exam',
            'exam_category' => 'Quiz',
            'duration_minutes' => 30,
            'total_points' => 10,
            'passing_score' => 7,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => true,
            'created_by' => $user->id,
        ]);
    }

    private function makeQuestion(InstitutionAssessment $assessment, string $text): InstitutionQuestion
    {
        $question = InstitutionQuestion::create([
            'assessment_id' => $assessment->id,
            'question_type' => 'multiple_choice',
            'question_text' => $text,
            'points' => 5,
            'order' => 1,
        ]);

        InstitutionQuestionChoice::create([
            'question_id' => $question->id,
            'choice_text' => 'Correct',
            'is_correct' => true,
            'order' => 1,
        ]);

        return $question;
    }
}
