<?php

namespace Tests\Unit;

use App\Models\ExamIdlePeriod;
use App\Models\ExamSessionChange;
use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionAnswer;
use App\Models\Institution\InstitutionAttempt;
use App\Models\Institution\InstitutionQuestion;
use App\Models\Institution\InstitutionQuestionChoice;
use App\Models\National\NationalAssessment;
use App\Models\National\NationalAttempt;
use App\Models\Organization;
use App\Models\User;
use App\Services\ResidentExamManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ResidentExamManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_an_answer_for_an_institution_attempt(): void
    {
        $service = app(ResidentExamManagementService::class);

        [$user, $attempt, $question] = $this->createInstitutionAttemptFixture();

        $request = Request::create('/exams/institution/' . $attempt->id . '/save-answer', 'POST', [
            'question_id' => $question->id,
            'answer_data' => ['choice_id' => $question->choices()->first()->id],
        ]);
        $request->setLaravelSession(app('session')->driver());
        $request->session()->start();
        $request->session()->setId('resident-test-session');
        $request->setUserResolver(fn () => $user);

        $attempt->update(['active_session_id' => 'resident-test-session']);

        $response = $service->saveAnswer($request, 'institution', $attempt->id);

        $this->assertSame(200, $response['status']);
        $this->assertDatabaseHas('institution_answers', [
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
        ]);
    }

    public function test_it_updates_the_existing_institution_answer_instead_of_creating_a_duplicate(): void
    {
        $service = app(ResidentExamManagementService::class);

        [$user, $attempt, $question] = $this->createInstitutionAttemptFixture();
        $choices = $question->choices()->orderBy('order')->get();

        InstitutionQuestionChoice::create([
            'question_id' => $question->id,
            'choice_text' => '5',
            'is_correct' => false,
            'order' => 2,
        ]);

        $request = Request::create('/exams/institution/' . $attempt->id . '/save-answer', 'POST', [
            'question_id' => $question->id,
            'answer_data' => ['choice_id' => $choices->first()->id],
        ]);
        $request->setLaravelSession(app('session')->driver());
        $request->session()->start();
        $request->session()->setId('resident-test-session');
        $request->setUserResolver(fn () => $user);

        $attempt->update(['active_session_id' => 'resident-test-session']);

        $firstResponse = $service->saveAnswer($request, 'institution', $attempt->id);
        $this->assertSame(200, $firstResponse['status']);

        $secondChoiceId = $question->choices()->orderByDesc('id')->value('id');

        $secondRequest = Request::create('/exams/institution/' . $attempt->id . '/save-answer', 'POST', [
            'question_id' => $question->id,
            'answer_data' => ['choice_id' => $secondChoiceId],
        ]);
        $secondRequest->setLaravelSession(app('session')->driver());
        $secondRequest->session()->start();
        $secondRequest->session()->setId('resident-test-session');
        $secondRequest->setUserResolver(fn () => $user);

        $secondResponse = $service->saveAnswer($secondRequest, 'institution', $attempt->id);

        $this->assertSame(200, $secondResponse['status']);
        $this->assertSame(1, InstitutionAnswer::query()
            ->where('attempt_id', $attempt->id)
            ->where('question_id', $question->id)
            ->count());

        $savedAnswer = InstitutionAnswer::query()
            ->where('attempt_id', $attempt->id)
            ->where('question_id', $question->id)
            ->firstOrFail();

        $this->assertSame($secondChoiceId, $savedAnswer->answer_data['choice_id']);
        $this->assertSame(2, $savedAnswer->answer_change_count);
    }

    public function test_it_submits_an_institution_attempt(): void
    {
        $service = app(ResidentExamManagementService::class);

        [$user, $attempt, $question] = $this->createInstitutionAttemptFixture();
        $choice = $question->choices()->first();

        InstitutionAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'answer_data' => ['choice_id' => $choice->id],
            'answer_change_count' => 1,
        ]);

        $request = Request::create('/exams/institution/' . $attempt->id . '/submit', 'POST');
        $request->setLaravelSession(app('session')->driver());
        $request->session()->start();
        $request->session()->setId('resident-submit-session');
        $request->setUserResolver(fn () => $user);

        $attempt->update(['active_session_id' => 'resident-submit-session']);
        $attempt->load(['answers.question.choices']);

        $response = $service->submit($request, 'institution', $attempt->id);

        $this->assertSame(200, $response['status']);
        $this->assertSame('completed', $attempt->fresh()->status);
        $this->assertNotNull($attempt->fresh()->submitted_at);
    }

    public function test_it_rejects_saving_answer_for_question_from_another_assessment(): void
    {
        $service = app(ResidentExamManagementService::class);

        [$user, $attempt, $question] = $this->createInstitutionAttemptFixture();
        $otherAssessment = InstitutionAssessment::create([
            'organization_id' => $attempt->organization_id,
            'title' => 'Other Quiz',
            'description' => 'Other desc',
            'exam_category' => 'Quiz',
            'duration_minutes' => 30,
            'total_points' => 1,
            'passing_score' => 1,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'is_published' => true,
            'created_by' => $user->id,
        ]);
        $foreignQuestion = InstitutionQuestion::create([
            'assessment_id' => $otherAssessment->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Foreign?',
            'points' => 1,
            'order' => 1,
        ]);
        $foreignChoice = InstitutionQuestionChoice::create([
            'question_id' => $foreignQuestion->id,
            'choice_text' => 'Yes',
            'is_correct' => true,
            'order' => 1,
        ]);

        $request = Request::create('/exams/institution/' . $attempt->id . '/save-answer', 'POST', [
            'question_id' => $foreignQuestion->id,
            'answer_data' => ['choice_id' => $foreignChoice->id],
        ]);
        $request->setLaravelSession(app('session')->driver());
        $request->session()->start();
        $request->session()->setId('resident-test-session');
        $request->setUserResolver(fn () => $user);

        $attempt->update(['active_session_id' => 'resident-test-session']);

        $response = $service->saveAnswer($request, 'institution', $attempt->id);

        $this->assertSame(422, $response['status']);
        $this->assertSame('The selected question does not belong to this exam attempt.', $response['error']);
        $this->assertDatabaseMissing('institution_answers', [
            'attempt_id' => $attempt->id,
            'question_id' => $foreignQuestion->id,
        ]);
    }

    public function test_out_of_scope_answers_do_not_affect_final_score(): void
    {
        $service = app(ResidentExamManagementService::class);

        [$user, $attempt, $question] = $this->createInstitutionAttemptFixture();
        $choice = $question->choices()->first();

        $otherAssessment = InstitutionAssessment::create([
            'organization_id' => $attempt->organization_id,
            'title' => 'Other Quiz',
            'description' => 'Other desc',
            'exam_category' => 'Quiz',
            'duration_minutes' => 30,
            'total_points' => 5,
            'passing_score' => 3,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'is_published' => true,
            'created_by' => $user->id,
        ]);
        $foreignQuestion = InstitutionQuestion::create([
            'assessment_id' => $otherAssessment->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Foreign?',
            'points' => 10,
            'order' => 1,
        ]);
        $foreignChoice = InstitutionQuestionChoice::create([
            'question_id' => $foreignQuestion->id,
            'choice_text' => 'Yes',
            'is_correct' => true,
            'order' => 1,
        ]);

        InstitutionAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'answer_data' => ['choice_id' => $choice->id],
            'answer_change_count' => 1,
        ]);
        $invalidAnswer = InstitutionAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $foreignQuestion->id,
            'answer_data' => ['choice_id' => $foreignChoice->id],
            'answer_change_count' => 1,
            'is_correct' => true,
            'points_earned' => 10,
        ]);

        $request = Request::create('/exams/institution/' . $attempt->id . '/submit', 'POST');
        $request->setLaravelSession(app('session')->driver());
        $request->session()->start();
        $request->session()->setId('resident-submit-session');
        $request->setUserResolver(fn () => $user);

        $attempt->update(['active_session_id' => 'resident-submit-session']);

        $response = $service->submit($request, 'institution', $attempt->id);

        $this->assertSame(200, $response['status']);
        $this->assertSame(1.0, (float) $attempt->fresh()->score);
        $this->assertFalse($invalidAnswer->fresh()->is_correct);
        $this->assertSame(0.0, (float) $invalidAnswer->fresh()->points_earned);
    }

    public function test_it_normalizes_national_monitoring_rows_and_sets_attempt_foreign_keys(): void
    {
        $service = app(ResidentExamManagementService::class);

        [$user, $attempt] = $this->createNationalAttemptFixture();

        $request = Request::create('/exams/inservice/' . $attempt->id . '/log-session-change', 'POST', [
            'change_type' => 'ip_address',
            'previous_ip' => '10.0.0.1',
            'new_ip' => '10.0.0.2',
        ]);
        $request->setLaravelSession(app('session')->driver());
        $request->session()->start();
        $request->session()->setId('resident-national-session');
        $request->setUserResolver(fn () => $user);

        $attempt->update(['active_session_id' => 'resident-national-session']);

        $service->logSessionChange($request, 'inservice', $attempt->id);

        $this->assertDatabaseHas('exam_session_changes', [
            'attempt_type' => 'national',
            'attempt_id' => $attempt->id,
            'national_attempt_id' => $attempt->id,
            'institution_attempt_id' => null,
            'user_id' => $user->id,
        ]);

        $activityRequest = Request::create('/exams/inservice/' . $attempt->id . '/log-activity', 'POST', [
            'idle_duration' => 120,
        ]);
        $activityRequest->setLaravelSession(app('session')->driver());
        $activityRequest->session()->start();
        $activityRequest->session()->setId('resident-national-session');
        $activityRequest->setUserResolver(fn () => $user);

        $service->logActivity($activityRequest, 'inservice', $attempt->id);

        $this->assertDatabaseHas('exam_idle_periods', [
            'attempt_type' => 'national',
            'attempt_id' => $attempt->id,
            'national_attempt_id' => $attempt->id,
            'institution_attempt_id' => null,
            'user_id' => $user->id,
            'duration_seconds' => 120,
        ]);

        $this->assertSame(1, ExamSessionChange::query()->where('national_attempt_id', $attempt->id)->count());
        $this->assertSame(1, ExamIdlePeriod::query()->where('national_attempt_id', $attempt->id)->count());
    }

    /**
     * @return array{0: User, 1: InstitutionAttempt, 2: InstitutionQuestion}
     */
    private function createInstitutionAttemptFixture(): array
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
        $user->organizations()->attach($organization->id, ['joined_at' => now(), 'is_active' => true]);

        $assessment = InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'Quiz',
            'description' => 'Desc',
            'exam_category' => 'Quiz',
            'duration_minutes' => 30,
            'total_points' => 1,
            'passing_score' => 1,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'is_published' => true,
            'created_by' => $user->id,
        ]);

        $attempt = InstitutionAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'total_points' => 1,
        ]);

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

        return [$user, $attempt, $question];
    }

    /**
     * @return array{0: User, 1: NationalAttempt}
     */
    private function createNationalAttemptFixture(): array
    {
        $organization = Organization::create([
            'name' => 'National Board',
            'slug' => 'national-board',
            'type' => 'national',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $user->organizations()->attach($organization->id, ['joined_at' => now(), 'is_active' => true]);

        $assessment = NationalAssessment::create([
            'title' => 'National Exam',
            'description' => 'Desc',
            'exam_year' => 2026,
            'exam_period' => 'Q3',
            'category' => 'anatomic-pathology-theoretical',
            'duration_minutes' => 60,
            'total_points' => 10,
            'passing_score' => 6,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => true,
            'national_ranking_enabled' => true,
            'institution_comparison_enabled' => true,
            'scheduled_date' => now()->subHour(),
            'results_release_date' => now()->addHour(),
            'created_by' => $user->id,
        ]);

        $attempt = NationalAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'total_points' => 10,
        ]);

        return [$user, $attempt];
    }
}
