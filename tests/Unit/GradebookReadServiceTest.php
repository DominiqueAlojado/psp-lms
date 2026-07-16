<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Resident;
use App\Models\User;
use App\Repositories\Contracts\GradebookRepositoryInterface;
use App\Services\GradebookReadService;
use Mockery;
use Tests\TestCase;

class GradebookReadServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        request()->query->remove('exam');
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_builds_my_grades_payload_for_institution_user(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 55;
        $user->currentOrganization = (object) ['id' => 10, 'type' => 'institution'];
        $user->setRelation('resident', null);

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
        $this->assertNull($payload['comparison']);
        $this->assertNull($payload['nationalStanding']);
        $this->assertSame('overall', $payload['selectedComparisonExamId']);
        $this->assertCount(1, $payload['comparisonExamOptions']);
    }

    public function test_it_builds_resident_safe_comparison_payload_without_peer_names(): void
    {
        $organization = new Organization(['id' => 10, 'name' => 'Bataan General Hospital']);

        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 55;
        $user->currentOrganization = (object) ['id' => 10, 'type' => 'institution'];

        $resident = new Resident([
            'id' => 1,
            'user_id' => 55,
            'organization_id' => 10,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'year_level' => 'Second Year',
            'status' => 'active',
        ]);
        $resident->setRelation('organization', $organization);
        $user->setRelation('resident', $resident);

        $peer = new Resident([
            'id' => 2,
            'user_id' => 77,
            'organization_id' => 10,
            'first_name' => 'Jane',
            'last_name' => 'Cruz',
            'year_level' => 'Second Year',
            'status' => 'active',
        ]);

        $selfAttempts = collect([
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
        $peerAttempts = collect([
            $this->makeAttempt([
                'title' => 'Quiz 2',
                'category' => 'Posttest',
                'score' => 9,
                'total_points' => 10,
                'percentage' => 90,
                'passed' => true,
                'submitted_at' => now()->subDays(2),
            ]),
        ]);

        $topicRows = collect();

        $repo = Mockery::mock(GradebookRepositoryInterface::class);
        $repo->shouldReceive('getCompletedInstitutionAttemptsForUser')->with(55)->times(3)->andReturn($selfAttempts);
        $repo->shouldReceive('getCompletedInstitutionAttemptsForUser')->with(55, true)->andReturn($selfAttempts);
        $repo->shouldReceive('getCompletedInstitutionAttemptsForUser')->with(77)->once()->andReturn($peerAttempts);
        $repo->shouldReceive('getResidentsForOrganization')->once()->with(10)->andReturn(collect([$resident, $peer]));
        $repo->shouldReceive('getAllResidents')->never();
        $repo->shouldReceive('getInstitutionTopicPerformanceRows')->once()->with(55)->andReturn($topicRows);

        $service = new GradebookReadService($repo);
        $payload = $service->myGradesPayload($user);

        $this->assertNotNull($payload['comparison']);
        $this->assertFalse($payload['comparison']['peer_names_visible']);
        $this->assertSame('Bataan General Hospital', $payload['comparison']['organization_name']);
        $this->assertSame('Same Year Level', $payload['comparison']['comparison_group_label']);
        $this->assertSame('overall', $payload['comparison']['comparison_mode']);
        $this->assertSame('Average Score', $payload['comparison']['metric_label']);
        $this->assertSame(2, $payload['comparison']['same_year_level_total']);
        $this->assertSame(2, $payload['comparison']['organization_total']);
        $this->assertArrayNotHasKey('top_organization_residents', $payload['comparison']);
        $this->assertArrayNotHasKey('top_same_year_level_residents', $payload['comparison']);
    }

    public function test_it_builds_selected_exam_comparison_payload_for_institution_user(): void
    {
        request()->query->set('exam', '501');

        $organization = new Organization(['id' => 10, 'name' => 'Bataan General Hospital']);

        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 55;
        $user->currentOrganization = (object) ['id' => 10, 'type' => 'institution'];

        $resident = new Resident([
            'id' => 1,
            'user_id' => 55,
            'organization_id' => 10,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'year_level' => 'Second Year',
            'status' => 'active',
        ]);
        $resident->setRelation('organization', $organization);
        $user->setRelation('resident', $resident);

        $sameLevelPeer = new Resident([
            'id' => 2,
            'user_id' => 77,
            'organization_id' => 10,
            'first_name' => 'Jane',
            'last_name' => 'Cruz',
            'year_level' => 'Second Year',
            'status' => 'active',
        ]);

        $otherLevelPeer = new Resident([
            'id' => 3,
            'user_id' => 88,
            'organization_id' => 10,
            'first_name' => 'Mark',
            'last_name' => 'Reyes',
            'year_level' => 'Third Year',
            'status' => 'active',
        ]);

        $selfAttempts = collect([
            $this->makeAttempt([
                'assessment_id' => 501,
                'title' => 'Selected Quiz',
                'category' => 'Pretest',
                'score' => 8,
                'total_points' => 10,
                'percentage' => 80,
                'passed' => true,
                'submitted_at' => now()->subDay(),
            ]),
            $this->makeAttempt([
                'assessment_id' => 777,
                'title' => 'Other Quiz',
                'category' => 'Posttest',
                'score' => 9,
                'total_points' => 10,
                'percentage' => 90,
                'passed' => true,
                'submitted_at' => now()->subDays(3),
            ]),
        ]);

        $sameLevelPeerAttempts = collect([
            $this->makeAttempt([
                'assessment_id' => 501,
                'title' => 'Selected Quiz',
                'category' => 'Pretest',
                'score' => 6,
                'total_points' => 10,
                'percentage' => 60,
                'passed' => true,
                'submitted_at' => now()->subDays(2),
            ]),
        ]);

        $otherLevelPeerAttempts = collect([
            $this->makeAttempt([
                'assessment_id' => 501,
                'title' => 'Selected Quiz',
                'category' => 'Pretest',
                'score' => 9,
                'total_points' => 10,
                'percentage' => 90,
                'passed' => true,
                'submitted_at' => now()->subDays(4),
            ]),
        ]);

        $repo = Mockery::mock(GradebookRepositoryInterface::class);
        $repo->shouldReceive('getCompletedInstitutionAttemptsForUser')->with(55)->times(2)->andReturn($selfAttempts);
        $repo->shouldReceive('getCompletedInstitutionAttemptsForUser')->with(55, true)->times(4)->andReturn($selfAttempts);
        $repo->shouldReceive('getCompletedInstitutionAttemptsForUser')->with(77, true)->once()->andReturn($sameLevelPeerAttempts);
        $repo->shouldReceive('getCompletedInstitutionAttemptsForUser')->with(88, true)->once()->andReturn($otherLevelPeerAttempts);
        $repo->shouldReceive('getResidentsForOrganization')->once()->with(10)->andReturn(collect([$resident, $sameLevelPeer, $otherLevelPeer]));
        $repo->shouldReceive('getInstitutionTopicPerformanceRows')->once()->with(55)->andReturn(collect());
        $repo->shouldReceive('getAllResidents')->never();

        $service = new GradebookReadService($repo);
        $payload = $service->myGradesPayload($user);

        $this->assertSame('501', $payload['selectedComparisonExamId']);
        $this->assertSame('selected_exam', $payload['comparison']['comparison_mode']);
        $this->assertSame('Selected Quiz', $payload['comparison']['selected_exam_title']);
        $this->assertSame('Selected Exam Score', $payload['comparison']['metric_label']);
        $this->assertSame(80.0, $payload['comparison']['resident_average_percentage']);
        $this->assertSame(70.0, $payload['comparison']['same_year_level_average_percentage']);
        $this->assertSame(2, $payload['comparison']['same_year_level_total']);
        $this->assertSame(3, $payload['comparison']['organization_total']);
        $this->assertCount(2, $payload['comparisonExamOptions']);
    }

    public function test_it_only_exposes_national_standing_when_assessment_flags_allow_it(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 55;
        $user->currentOrganization = (object) ['id' => 99, 'type' => 'national'];
        $user->setRelation('resident', null);

        $visibleAttempt = $this->makeAttempt([
            'title' => 'In-Service 2026',
            'category' => 'In-Service Exam',
            'score' => 45,
            'total_points' => 50,
            'percentage' => 90,
            'passed' => true,
            'submitted_at' => now()->subDay(),
            'exam_year' => 2026,
            'national_ranking_enabled' => true,
            'institution_comparison_enabled' => false,
            'national_rank' => 3,
            'institution_rank' => 1,
            'percentile' => 92.75,
        ]);

        $repo = Mockery::mock(GradebookRepositoryInterface::class);
        $repo->shouldReceive('getCompletedNationalAttemptsForUser')->with(55)->times(2)->andReturn(collect([$visibleAttempt]));
        $repo->shouldReceive('getCompletedNationalAttemptsForUser')->with(55, true)->times(2)->andReturn(collect([$visibleAttempt]));
        $repo->shouldReceive('getNationalTopicPerformanceRows')->once()->with(55)->andReturn(collect());

        $service = new GradebookReadService($repo);
        $payload = $service->myGradesPayload($user);

        $this->assertNotNull($payload['nationalStanding']);
        $this->assertTrue($payload['nationalStanding']['national_ranking_enabled']);
        $this->assertFalse($payload['nationalStanding']['institution_comparison_enabled']);
        $this->assertSame(3, $payload['nationalStanding']['national_rank']);
        $this->assertNull($payload['nationalStanding']['institution_rank']);
        $this->assertSame(92.75, $payload['nationalStanding']['percentile']);
    }

    public function test_it_builds_year_level_breakdown_for_national_comparison_payload(): void
    {
        $organization = new Organization(['id' => 10, 'name' => 'Bataan General Hospital']);

        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 55;
        $user->currentOrganization = (object) ['id' => 99, 'type' => 'national'];

        $resident = new Resident([
            'id' => 1,
            'user_id' => 55,
            'organization_id' => 10,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'year_level' => 'Third Year',
            'status' => 'active',
        ]);
        $resident->setRelation('organization', $organization);
        $user->setRelation('resident', $resident);

        $sameOrganizationPeer = new Resident([
            'id' => 2,
            'user_id' => 77,
            'organization_id' => 10,
            'first_name' => 'Jane',
            'last_name' => 'Cruz',
            'year_level' => 'First Year',
            'status' => 'active',
        ]);

        $otherOrganizationPeer = new Resident([
            'id' => 3,
            'user_id' => 88,
            'organization_id' => 20,
            'first_name' => 'Mark',
            'last_name' => 'Reyes',
            'year_level' => 'Fourth Year',
            'status' => 'active',
        ]);

        $selfAttempts = collect([
            $this->makeAttempt([
                'title' => 'In-Service 2026',
                'category' => 'In-Service Exam',
                'score' => 40,
                'total_points' => 50,
                'percentage' => 80,
                'passed' => true,
                'submitted_at' => now()->subDay(),
                'exam_year' => 2026,
                'national_ranking_enabled' => true,
                'institution_comparison_enabled' => true,
            ]),
        ]);

        $sameOrgPeerAttempts = collect([
            $this->makeAttempt([
                'title' => 'In-Service 2026',
                'category' => 'In-Service Exam',
                'score' => 35,
                'total_points' => 50,
                'percentage' => 70,
                'passed' => true,
                'submitted_at' => now()->subDays(2),
                'exam_year' => 2026,
                'national_ranking_enabled' => true,
                'institution_comparison_enabled' => true,
            ]),
        ]);

        $otherOrgPeerAttempts = collect([
            $this->makeAttempt([
                'title' => 'In-Service 2026',
                'category' => 'In-Service Exam',
                'score' => 45,
                'total_points' => 50,
                'percentage' => 90,
                'passed' => true,
                'submitted_at' => now()->subDays(3),
                'exam_year' => 2026,
                'national_ranking_enabled' => true,
                'institution_comparison_enabled' => true,
            ]),
        ]);

        $repo = Mockery::mock(GradebookRepositoryInterface::class);
        $repo->shouldReceive('getCompletedNationalAttemptsForUser')->with(55)->times(4)->andReturn($selfAttempts);
        $repo->shouldReceive('getCompletedNationalAttemptsForUser')->with(55, true)->times(2)->andReturn($selfAttempts);
        $repo->shouldReceive('getCompletedNationalAttemptsForUser')->with(77)->times(2)->andReturn($sameOrgPeerAttempts);
        $repo->shouldReceive('getCompletedNationalAttemptsForUser')->with(88)->once()->andReturn($otherOrgPeerAttempts);
        $repo->shouldReceive('getAllResidents')->once()->andReturn(collect([$resident, $sameOrganizationPeer, $otherOrganizationPeer]));
        $repo->shouldReceive('getResidentsForOrganization')->once()->with(10)->andReturn(collect([$resident, $sameOrganizationPeer]));
        $repo->shouldReceive('getNationalTopicPerformanceRows')->once()->with(55)->andReturn(collect());

        $service = new GradebookReadService($repo);
        $payload = $service->myGradesPayload($user);

        $this->assertSame('All Year Levels', $payload['comparison']['comparison_group_label']);
        $this->assertSame('overall', $payload['comparison']['comparison_mode']);
        $this->assertSame(3, $payload['comparison']['same_year_level_total']);
        $this->assertSame(2, $payload['comparison']['organization_total']);
        $this->assertCount(3, $payload['comparison']['year_level_breakdown']);
        $this->assertSame('First Year', $payload['comparison']['year_level_breakdown'][0]['year_level']);
        $this->assertSame('Third Year', $payload['comparison']['year_level_breakdown'][1]['year_level']);
        $this->assertSame('Fourth Year', $payload['comparison']['year_level_breakdown'][2]['year_level']);
        $this->assertSame(70.0, $payload['comparison']['year_level_breakdown'][0]['average_percentage']);
        $this->assertSame(80.0, $payload['comparison']['year_level_breakdown'][1]['average_percentage']);
        $this->assertSame(90.0, $payload['comparison']['year_level_breakdown'][2]['average_percentage']);
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
                    'id' => $data['assessment_id'] ?? 100,
                    'title' => $data['title'],
                    'exam_category' => $data['category'],
                    'passing_score' => 6,
                    'exam_year' => 2026,
                    'national_ranking_enabled' => $data['national_ranking_enabled'] ?? false,
                    'institution_comparison_enabled' => $data['institution_comparison_enabled'] ?? false,
                ];
                $this->assessment_id = $data['assessment_id'] ?? 100;
                $this->score = $data['score'];
                $this->total_points = $data['total_points'];
                $this->percentage = $data['percentage'];
                $this->submitted_at = $data['submitted_at'];
                $this->passed = $data['passed'];
                $this->national_rank = $data['national_rank'] ?? null;
                $this->institution_rank = $data['institution_rank'] ?? null;
                $this->percentile = $data['percentile'] ?? null;
            }

            public function isPassed(): bool
            {
                return $this->passed;
            }
        };
    }
}
