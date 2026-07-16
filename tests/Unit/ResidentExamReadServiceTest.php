<?php

namespace Tests\Unit;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionAttempt;
use App\Models\Institution\InstitutionQuestion;
use App\Models\Institution\InstitutionQuestionChoice;
use App\Repositories\Contracts\ResidentExamRepositoryInterface;
use App\Services\ResidentExamAttemptService;
use App\Models\Organization;
use App\Models\User;
use App\Services\ResidentExamReadService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class ResidentExamReadServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_builds_take_payload_for_institution_exam(): void
    {
        $service = app(ResidentExamReadService::class);

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

        $request = Request::create('/exams/institution/' . $assessment->id . '/take', 'GET');
        $request->setLaravelSession(app('session')->driver());
        $request->session()->start();
        $request->setUserResolver(fn () => $user);

        $payload = $service->takePayload($request, 'institution', $assessment->id);

        $this->assertSame('Quiz', $payload['exam']['title']);
        $this->assertSame('institution', $payload['exam']['type']);
        $this->assertCount(1, $payload['exam']['questions']);
    }

    public function test_it_builds_index_payload_from_bulk_attempt_summaries(): void
    {
        Carbon::setTestNow('2026-07-16 12:00:00');

        $organization = new Organization([
            'id' => 10,
            'name' => 'Alpha Hospital',
            'slug' => 'alpha-hospital',
            'type' => 'institution',
        ]);

        $user = new User([
            'current_organization_id' => 10,
        ]);
        $user->id = 99;
        $user->setRelation('currentOrganization', $organization);

        $exam = new InstitutionAssessment([
            'organization_id' => 10,
            'title' => 'Quiz',
            'description' => 'Desc',
            'exam_category' => 'Quiz',
            'total_points' => 20,
            'passing_score' => 10,
            'duration_minutes' => 30,
            'is_published' => true,
            'available_from' => Carbon::parse('2026-07-15 08:00:00'),
            'available_until' => Carbon::parse('2026-07-20 08:00:00'),
        ]);
        $exam->id = 5;
        $exam->questions_count = 10;

        $summaryRow = (object) [
            'assessment_id' => 5,
            'attempt_count' => 2,
            'best_score' => 18,
            'last_submitted_at' => Carbon::parse('2026-07-16 10:00:00'),
        ];

        $repo = Mockery::mock(ResidentExamRepositoryInterface::class);
        $repo->shouldReceive('getPublishedInstitutionExamsForOrganization')
            ->once()
            ->with(10)
            ->andReturn(collect([$exam]));
        $repo->shouldReceive('getCompletedInstitutionAttemptSummariesForUser')
            ->once()
            ->with([5], 99)
            ->andReturn(collect([5 => $summaryRow]));
        $repo->shouldReceive('getInProgressInstitutionAssessmentIdsForUser')
            ->once()
            ->with([5], 99)
            ->andReturn([5]);
        $repo->shouldReceive('getCompletedInstitutionAttempts')->never();
        $repo->shouldReceive('hasStartedInProgressInstitutionAttempt')->never();
        $repo->shouldReceive('getCompletedNationalAttemptSummariesForUser')
            ->once()
            ->with([], 99)
            ->andReturn(collect());
        $repo->shouldReceive('getInProgressNationalAssessmentIdsForUser')
            ->once()
            ->with([], 99)
            ->andReturn([]);

        $attemptService = Mockery::mock(ResidentExamAttemptService::class);

        $service = new ResidentExamReadService($repo, $attemptService);
        $payload = $service->indexPayload($user);

        $this->assertCount(1, $payload['availableExams']);
        $this->assertSame(2, $payload['availableExams'][0]['attempt_count']);
        $this->assertSame(90.0, $payload['availableExams'][0]['best_score']);
        $this->assertTrue($payload['availableExams'][0]['has_in_progress_attempt']);
        $this->assertSame('2 hours ago', $payload['availableExams'][0]['last_attempted']);

        Carbon::setTestNow();
    }
}
