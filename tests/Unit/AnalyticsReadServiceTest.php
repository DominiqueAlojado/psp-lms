<?php

namespace Tests\Unit;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Organization;
use App\Models\User;
use App\Repositories\Contracts\AnalyticsRepositoryInterface;
use App\Services\AnalyticsReadService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class AnalyticsReadServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_builds_exam_analytics_payload_for_institution_exam(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->current_organization_id = 10;
        $user->currentOrganization = (object) ['id' => 10, 'type' => 'institution'];
        $user->shouldReceive('hasPermissionTo')->with('view-all-assessment-reports')->andReturn(false);

        $exam = new class extends InstitutionAssessment
        {
            public Collection $questions;

            public function __construct()
            {
                parent::__construct([
                    'id' => 7,
                    'title' => 'General Nursing',
                    'exam_category' => 'In-Service',
                    'total_points' => 10,
                    'passing_score' => 6,
                ]);

                $question = new class
                {
                    public int $id = 99;
                    public string $question_text = 'Question 1';
                    public int $points = 5;
                    public object $topic;

                    public function __construct()
                    {
                        $this->topic = (object) ['name' => 'Anatomy'];
                    }
                };

                $this->questions = collect([$question]);
            }
        };

        $attempts = collect([
            new class
            {
                public int $score = 8;
                public string $year_level = 'R1';
                public object $assessment;

                public function __construct()
                {
                    $this->assessment = (object) ['passing_score' => 6];
                }

                public function answers()
                {
                    return new class
                    {
                        public function where($field, $value)
                        {
                            return $this;
                        }

                        public function first()
                        {
                            return (object) ['is_correct' => true];
                        }
                    };
                }
            },
        ]);

        $repo = Mockery::mock(AnalyticsRepositoryInterface::class);
        $repo->shouldReceive('getPublishedInstitutionExams')->once()->andReturn(collect([(object) [
            'id' => 7,
            'title' => 'General Nursing',
            'exam_category' => 'In-Service',
        ]]));
        $repo->shouldReceive('findInstitutionAssessmentForAnalytics')->once()->with(7)->andReturn($exam);
        $repo->shouldReceive('getCompletedInstitutionAttemptsForExam')->once()->andReturn($attempts);

        $service = new AnalyticsReadService($repo);
        $request = Request::create('/analytics/exam-analytics', 'GET', ['exam' => 'institution_7']);
        $request->setUserResolver(fn () => $user);

        $payload = $service->examAnalyticsPayload($request);

        $this->assertSame('General Nursing', $payload['analytics']['exam']['title']);
        $this->assertSame(100.0, $payload['analytics']['pass_rate']);
        $this->assertSame('Anatomy', $payload['analytics']['question_stats'][0]['topic']);
    }

    public function test_it_builds_question_bank_payload_with_scoped_statistics(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->currentOrganization = new Organization(['id' => 10, 'type' => 'institution']);

        $question = (object) [
            'allStatistics' => collect([
                (object) ['scope' => 'institution', 'institution_id' => null, 'success_rate' => 75],
                (object) ['scope' => 'institution', 'institution_id' => 11, 'success_rate' => 25],
            ]),
        ];

        $paginator = new LengthAwarePaginator(collect([$question]), 1, 20, 1);

        $repo = Mockery::mock(AnalyticsRepositoryInterface::class);
        $repo->shouldReceive('paginateQuestionBankAnalytics')->once()->andReturn($paginator);
        $repo->shouldReceive('getQuestionBankSummary')->once()->with(null, false)->andReturn(['total_questions' => 1]);
        $repo->shouldReceive('getQuestionBankTopics')->once()->with(null, false)->andReturn(collect([(object) ['id' => 1, 'name' => 'Anatomy']]));

        $service = new AnalyticsReadService($repo);
        $request = Request::create('/analytics/question-bank', 'GET');
        $request->setUserResolver(fn () => $user);

        $payload = $service->questionBankPayload($request);

        $this->assertSame(75, $payload['questions']->items()[0]->statistics->success_rate);
        $this->assertSame(1, $payload['summary']['total_questions']);
        $this->assertSame('created_at', $payload['filters']['sort_by']);
    }
}
