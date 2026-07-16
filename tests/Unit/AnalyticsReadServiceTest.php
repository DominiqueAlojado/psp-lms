<?php

namespace Tests\Unit;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Organization;
use App\Models\User;
use App\Repositories\Contracts\AnalyticsRepositoryInterface;
use App\Services\AnalyticsReadService;
use Carbon\Carbon;
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

    public function test_it_treats_all_exam_filter_as_no_specific_exam(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->current_organization_id = 10;
        $user->currentOrganization = (object) ['id' => 10, 'type' => 'institution'];
        $user->shouldReceive('hasPermissionTo')->with('view-all-assessment-reports')->andReturn(false);

        $repo = Mockery::mock(AnalyticsRepositoryInterface::class);
        $repo->shouldReceive('getPublishedInstitutionExams')->once()->andReturn(collect());
        $repo->shouldNotReceive('findInstitutionAssessmentForAnalytics');
        $repo->shouldNotReceive('getCompletedInstitutionAttemptsForExam');

        $service = new AnalyticsReadService($repo);
        $request = Request::create('/analytics/exam-analytics', 'GET', ['exam' => 'all']);
        $request->setUserResolver(fn () => $user);

        $payload = $service->examAnalyticsPayload($request);

        $this->assertNull($payload['analytics']);
        $this->assertNull($payload['filters']['exam']);
    }

    public function test_it_builds_topic_performance_payload_across_scoped_exams(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->current_organization_id = 10;
        $user->currentOrganization = (object) ['id' => 10, 'type' => 'institution'];
        $user->shouldReceive('hasPermissionTo')->with('view-all-assessment-reports')->andReturn(false);

        $firstExam = new class extends InstitutionAssessment
        {
            public Collection $questions;

            public function __construct()
            {
                parent::__construct([
                    'id' => 7,
                    'title' => 'Exam A',
                    'exam_category' => 'In-Service',
                ]);

                $anatomy = new class
                {
                    public int $id = 101;
                    public int $points = 2;
                    public object $topic;

                    public function __construct()
                    {
                        $this->topic = (object) ['name' => 'Anatomy'];
                    }
                };

                $pharma = new class
                {
                    public int $id = 102;
                    public int $points = 3;
                    public object $topic;

                    public function __construct()
                    {
                        $this->topic = (object) ['name' => 'Pharmacology'];
                    }
                };

                $this->questions = collect([$anatomy, $pharma]);
            }
        };

        $secondExam = new class extends InstitutionAssessment
        {
            public Collection $questions;

            public function __construct()
            {
                parent::__construct([
                    'id' => 8,
                    'title' => 'Exam B',
                    'exam_category' => 'In-Service',
                ]);

                $anatomy = new class
                {
                    public int $id = 201;
                    public int $points = 4;
                    public object $topic;

                    public function __construct()
                    {
                        $this->topic = (object) ['name' => 'Anatomy'];
                    }
                };

                $this->questions = collect([$anatomy]);
            }
        };

        $attemptForExamA = new class
        {
            public Collection $answers;

            public function __construct()
            {
                $this->answers = collect([
                    (object) ['question_id' => 101, 'is_correct' => true],
                    (object) ['question_id' => 102, 'is_correct' => false],
                ]);
            }
        };

        $attemptForExamB = new class
        {
            public Collection $answers;

            public function __construct()
            {
                $this->answers = collect([
                    (object) ['question_id' => 201, 'is_correct' => true],
                ]);
            }
        };

        $repo = Mockery::mock(AnalyticsRepositoryInterface::class);
        $repo->shouldReceive('getPublishedInstitutionExams')->once()->andReturn(collect([
            (object) ['id' => 7, 'title' => 'Exam A', 'exam_category' => 'In-Service'],
            (object) ['id' => 8, 'title' => 'Exam B', 'exam_category' => 'In-Service'],
        ]));
        $repo->shouldReceive('findInstitutionAssessmentForAnalytics')->once()->with(7)->andReturn($firstExam);
        $repo->shouldReceive('findInstitutionAssessmentForAnalytics')->once()->with(8)->andReturn($secondExam);
        $repo->shouldReceive('getCompletedInstitutionAttemptsForExam')->once()->with(7, 10, false, Mockery::type('array'))->andReturn(collect([$attemptForExamA]));
        $repo->shouldReceive('getCompletedInstitutionAttemptsForExam')->once()->with(8, 10, false, Mockery::type('array'))->andReturn(collect([$attemptForExamB]));

        $service = new AnalyticsReadService($repo);
        $request = Request::create('/analytics/topic-performance', 'GET', ['exam' => 'all']);
        $request->setUserResolver(fn () => $user);

        $payload = $service->topicPerformancePayload($request);

        $this->assertNull($payload['filters']['exam']);
        $this->assertSame(2, $payload['topicPerformance']['summary']['topics_count']);
        $this->assertSame(2, $payload['topicPerformance']['summary']['exams_covered']);
        $this->assertSame(3, $payload['topicPerformance']['summary']['total_responses']);
        $this->assertSame('Anatomy', $payload['topicPerformance']['topics'][0]['topic']);
        $this->assertSame(2, $payload['topicPerformance']['topics'][0]['exams_covered']);
        $this->assertSame(100.0, $payload['topicPerformance']['topics'][0]['success_rate']);
    }

    public function test_it_builds_category_performance_payload_across_categories(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->current_organization_id = 10;
        $user->currentOrganization = (object) ['id' => 10, 'type' => 'institution'];
        $user->shouldReceive('hasPermissionTo')->with('view-all-assessment-reports')->andReturn(false);

        $firstExam = new class extends InstitutionAssessment
        {
            public Collection $questions;

            public function __construct()
            {
                parent::__construct([
                    'id' => 7,
                    'title' => 'Exam A',
                    'exam_category' => 'In-Service',
                    'total_points' => 10,
                    'passing_score' => 6,
                ]);

                $this->questions = collect([(object) ['id' => 1], (object) ['id' => 2]]);
            }
        };

        $secondExam = new class extends InstitutionAssessment
        {
            public Collection $questions;

            public function __construct()
            {
                parent::__construct([
                    'id' => 8,
                    'title' => 'Exam B',
                    'exam_category' => 'Mock Exam',
                    'total_points' => 20,
                    'passing_score' => 12,
                ]);

                $this->questions = collect([(object) ['id' => 3]]);
            }
        };

        $repo = Mockery::mock(AnalyticsRepositoryInterface::class);
        $repo->shouldReceive('getPublishedInstitutionExams')->once()->andReturn(collect([
            (object) ['id' => 7, 'title' => 'Exam A', 'exam_category' => 'In-Service'],
            (object) ['id' => 8, 'title' => 'Exam B', 'exam_category' => 'Mock Exam'],
        ]));
        $repo->shouldReceive('findInstitutionAssessmentForAnalytics')->once()->with(7)->andReturn($firstExam);
        $repo->shouldReceive('findInstitutionAssessmentForAnalytics')->once()->with(8)->andReturn($secondExam);
        $repo->shouldReceive('getCompletedInstitutionAttemptsForExam')->once()->with(7, 10, false, Mockery::type('array'))->andReturn(collect([
            (object) ['score' => 8],
            (object) ['score' => 4],
        ]));
        $repo->shouldReceive('getCompletedInstitutionAttemptsForExam')->once()->with(8, 10, false, Mockery::type('array'))->andReturn(collect([
            (object) ['score' => 16],
        ]));

        $service = new AnalyticsReadService($repo);
        $request = Request::create('/analytics/category-performance', 'GET');
        $request->setUserResolver(fn () => $user);

        $payload = $service->categoryPerformancePayload($request);

        $this->assertSame(2, $payload['categoryPerformance']['summary']['categories_count']);
        $this->assertSame(2, $payload['categoryPerformance']['summary']['exams_covered']);
        $this->assertSame(3, $payload['categoryPerformance']['summary']['total_attempts']);
        $this->assertSame('Mock Exam', $payload['categoryPerformance']['categories'][0]['category']);
        $this->assertSame(100.0, $payload['categoryPerformance']['categories'][0]['pass_rate']);
        $this->assertSame(50.0, $payload['categoryPerformance']['categories'][1]['pass_rate']);
    }

    public function test_it_builds_trends_payload_grouped_by_period(): void
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
                    'title' => 'Exam A',
                    'exam_category' => 'In-Service',
                    'total_points' => 10,
                    'passing_score' => 6,
                ]);

                $this->questions = collect();
            }
        };

        $repo = Mockery::mock(AnalyticsRepositoryInterface::class);
        $repo->shouldReceive('getPublishedInstitutionExams')->once()->andReturn(collect([
            (object) ['id' => 7, 'title' => 'Exam A', 'exam_category' => 'In-Service'],
        ]));
        $repo->shouldReceive('findInstitutionAssessmentForAnalytics')->once()->with(7)->andReturn($exam);
        $repo->shouldReceive('getCompletedInstitutionAttemptsForExam')->once()->with(7, 10, false, Mockery::type('array'))->andReturn(collect([
            (object) ['score' => 4, 'submitted_at' => Carbon::parse('2026-01-10')],
            (object) ['score' => 8, 'submitted_at' => Carbon::parse('2026-01-20')],
            (object) ['score' => 9, 'submitted_at' => Carbon::parse('2026-02-11')],
        ]));

        $service = new AnalyticsReadService($repo);
        $request = Request::create('/analytics/trends', 'GET', ['exam' => 'all']);
        $request->setUserResolver(fn () => $user);

        $payload = $service->trendsPayload($request);

        $this->assertNull($payload['filters']['exam']);
        $this->assertSame(2, $payload['trends']['summary']['periods_count']);
        $this->assertSame(1, $payload['trends']['summary']['exams_covered']);
        $this->assertSame(3, $payload['trends']['summary']['total_attempts']);
        $this->assertSame('improving', $payload['trends']['summary']['direction']);
        $this->assertSame('Jan 2026', $payload['trends']['periods'][0]['period_label']);
        $this->assertSame(50.0, $payload['trends']['periods'][0]['pass_rate']);
        $this->assertSame(100.0, $payload['trends']['periods'][1]['pass_rate']);
    }

    public function test_it_builds_exam_filters_for_all_organizations_context(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->current_organization_id = 10;
        $user->currentOrganization = (object) [
            'id' => 0,
            'type' => 'all',
            'slug' => 'all-organizations',
        ];
        $user->shouldReceive('hasPermissionTo')->with('view-all-assessment-reports')->andReturn(true);

        $repo = Mockery::mock(AnalyticsRepositoryInterface::class);
        $repo->shouldReceive('getPublishedInstitutionExams')->once()->with(10, true)->andReturn(collect([
            (object) ['id' => 7, 'title' => 'Institution Exam', 'exam_category' => 'Mock Exam'],
        ]));
        $repo->shouldReceive('getPublishedNationalExams')->once()->andReturn(collect([
            (object) ['id' => 3, 'title' => 'National Exam', 'category' => 'In-Service'],
        ]));
        $repo->shouldReceive('getActiveInstitutionOrganizations')->once()->andReturn(collect([
            (object) ['id' => 10, 'name' => 'Alpha Hospital'],
        ]));
        $repo->shouldNotReceive('findInstitutionAssessmentForAnalytics');
        $repo->shouldNotReceive('findNationalAssessmentForAnalytics');

        $service = new AnalyticsReadService($repo);
        $request = Request::create('/analytics/exam-analytics', 'GET', ['exam' => 'all']);
        $request->setUserResolver(fn () => $user);

        $payload = $service->examAnalyticsPayload($request);

        $this->assertCount(2, $payload['exams']);
        $this->assertSame('institution', $payload['exams'][0]['type']);
        $this->assertSame('national', $payload['exams'][1]['type']);
        $this->assertCount(1, $payload['organizations']);
        $this->assertNull($payload['analytics']);
    }
}
