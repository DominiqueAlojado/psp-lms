<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Resident;
use App\Models\User;
use App\Repositories\Contracts\GradebookRepositoryInterface;
use App\Services\GradebookReadService;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class GradebookReadServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_builds_my_grades_payload_for_institution_user(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 55;
        $user->currentOrganization = (object) ['id' => 10, 'type' => 'institution'];

        $institutionAttempts = collect([
            $this->makeAttempt([
                'title' => 'Quiz 1',
                'category' => 'Pretest',
                'score' => 8,
                'total_points' => 10,
                'percentage' => 80,
                'passed' => true,
                'submitted_at' => now()->subDay(),
            ]),
        ]);

        $topicRows = collect([
            (object) [
                'topic_name' => 'Anatomy',
                'total_questions' => 5,
                'correct_answers' => 4,
                'total_points_earned' => 4,
                'total_possible_points' => 5,
            ],
        ]);

        $repo = Mockery::mock(GradebookRepositoryInterface::class);
        $repo->shouldReceive('getCompletedInstitutionAttemptsForUser')->with(55)->andReturn($institutionAttempts);
        $repo->shouldReceive('getCompletedInstitutionAttemptsForUser')->with(55, true)->andReturn($institutionAttempts);
        $repo->shouldReceive('getInstitutionTopicPerformanceRows')->once()->with(55)->andReturn($topicRows);

        $service = new GradebookReadService($repo);
        $payload = $service->myGradesPayload($user);

        $this->assertSame(1, $payload['stats']['total_exams']);
        $this->assertSame('Pretest', $payload['categoryPerformance'][0]['category']);
        $this->assertSame('Anatomy', $payload['topicPerformance'][0]['topic']);
        $this->assertSame('Quiz 1', $payload['recentExams'][0]['title']);
    }

    public function test_it_builds_index_payload_for_organization_residents(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->currentOrganization = (object) ['id' => 10, 'type' => 'institution'];

        $resident = new Resident([
            'id' => 1,
            'user_id' => 77,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'year_level' => 'R1',
            'status' => 'active',
        ]);

        $repo = Mockery::mock(GradebookRepositoryInterface::class);
        $repo->shouldReceive('getResidentsForOrganization')->once()->with(10)->andReturn(collect([$resident]));
        $repo->shouldReceive('getCompletedInstitutionAttemptsForUser')->once()->with(77)->andReturn(collect());

        $service = new GradebookReadService($repo);
        $payload = $service->indexPayload($user);

        $this->assertCount(1, $payload['residents']);
        $this->assertSame('Jane Doe', $payload['residents'][0]['name']);
        $this->assertSame(0, $payload['residents'][0]['stats']['total_exams']);
    }

    private function makeAttempt(array $data): object
    {
        return new class($data)
        {
            public object $assessment;
            public int $score;
            public int $total_points;
            public float $percentage;
            public $submitted_at;
            private bool $passed;

            public function __construct(array $data)
            {
                $this->assessment = (object) [
                    'title' => $data['title'],
                    'exam_category' => $data['category'],
                    'passing_score' => 6,
                    'exam_year' => 2026,
                ];
                $this->score = $data['score'];
                $this->total_points = $data['total_points'];
                $this->percentage = $data['percentage'];
                $this->submitted_at = $data['submitted_at'];
                $this->passed = $data['passed'];
            }

            public function isPassed(): bool
            {
                return $this->passed;
            }
        };
    }
}
