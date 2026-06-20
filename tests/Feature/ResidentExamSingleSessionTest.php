<?php

namespace Tests\Feature;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionAttempt;
use App\Models\Institution\InstitutionQuestion;
use App\Models\Institution\InstitutionQuestionChoice;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResidentExamSingleSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_in_progress_exam_claims_the_first_active_session(): void
    {
        [$user, $organization] = $this->makeResidentUser();
        $assessment = $this->makeInstitutionAssessment($organization, $user);
        $question = $this->makeInstitutionQuestion($assessment);

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
            'question_id' => $question->id,
            'answer_data' => ['choice_id' => 1],
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotNull($attempt->fresh()->active_session_id);
    }

    public function test_in_progress_exam_is_reclaimed_when_the_old_session_no_longer_exists(): void
    {
        [$user, $organization] = $this->makeResidentUser();
        $assessment = $this->makeInstitutionAssessment($organization, $user);
        $question = $this->makeInstitutionQuestion($assessment);

        $attempt = InstitutionAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'year_level' => 'First Year',
            'organization_id' => $organization->id,
            'started_at' => now(),
            'total_points' => $assessment->total_points,
            'status' => 'in_progress',
            'active_session_id' => 'browser-a-session',
            'last_activity_at' => now(),
        ]);

        $jsonResponse = $this->actingAs($user)->postJson("/exams/institution/{$attempt->id}/save-answer", [
            'question_id' => $question->id,
            'answer_data' => ['choice_id' => 1],
        ]);

        $jsonResponse->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotSame('browser-a-session', $attempt->fresh()->active_session_id);
    }

    public function test_in_progress_exam_is_blocked_for_a_different_active_session(): void
    {
        [$user, $organization] = $this->makeResidentUser();
        $assessment = $this->makeInstitutionAssessment($organization, $user);
        $question = $this->makeInstitutionQuestion($assessment);

        DB::table('sessions')->insert([
            'id' => 'browser-a-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Browser A',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->timestamp,
        ]);

        $attempt = InstitutionAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'year_level' => 'First Year',
            'organization_id' => $organization->id,
            'started_at' => now(),
            'total_points' => $assessment->total_points,
            'status' => 'in_progress',
            'active_session_id' => 'browser-a-session',
            'last_activity_at' => now(),
        ]);

        $jsonResponse = $this->actingAs($user)->postJson("/exams/institution/{$attempt->id}/save-answer", [
            'question_id' => $question->id,
            'answer_data' => ['choice_id' => 1],
        ]);

        $jsonResponse->assertStatus(409)
            ->assertJson([
                'error' => 'This exam is already active in another browser or device.',
            ]);

        $pageResponse = $this->actingAs($user)->get("/exams/institution/{$assessment->id}/take?org={$organization->slug}");

        $pageResponse->assertStatus(409);
    }

    private function makeResidentUser(): array
    {
        $organization = Organization::create([
            'name' => 'Single Session Hospital',
            'slug' => 'single-session-hospital',
            'description' => 'Organization for exam single session tests',
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

    private function makeInstitutionAssessment(Organization $organization, User $user): InstitutionAssessment
    {
        return InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'Single Session Exam',
            'description' => 'Session enforcement test exam',
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

    private function makeInstitutionQuestion(InstitutionAssessment $assessment): InstitutionQuestion
    {
        $question = InstitutionQuestion::create([
            'assessment_id' => $assessment->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'What is 2 + 2?',
            'points' => 1,
            'order' => 1,
        ]);

        InstitutionQuestionChoice::create([
            'question_id' => $question->id,
            'choice_text' => '4',
            'is_correct' => true,
            'order' => 1,
        ]);

        return $question;
    }
}
