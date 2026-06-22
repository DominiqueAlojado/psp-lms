<?php

namespace Tests\Unit;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionAnswer;
use App\Models\Institution\InstitutionAttempt;
use App\Models\Institution\InstitutionQuestion;
use App\Models\Institution\InstitutionQuestionChoice;
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
}
