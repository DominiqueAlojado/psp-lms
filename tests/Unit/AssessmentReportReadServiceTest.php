<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\User;
use App\Repositories\Contracts\AssessmentReportRepositoryInterface;
use App\Services\AssessmentReportReadService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class AssessmentReportReadServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_builds_by_resident_payload_for_institution_context(): void
    {
        $organization = new Organization(['id' => 10, 'type' => 'institution']);
        $user = Mockery::mock(User::class)->makePartial();
        $user->current_organization_id = 10;
        $user->currentOrganization = $organization;
        $user->shouldReceive('hasPermissionTo')->with('view-all-assessment-reports')->andReturn(false);

        $repo = Mockery::mock(AssessmentReportRepositoryInterface::class);
        $repo->shouldReceive('getCompletedInstitutionAttemptsForReport')
            ->once()
            ->andReturn(collect([
                $this->makeAttempt([
                    'id' => 5,
                    'type' => 'institution',
                    'exam_category' => 'Quiz',
                    'submitted_at' => Carbon::parse('2026-06-22 10:00:00'),
                ]),
            ]));
        $repo->shouldReceive('getPublishedInstitutionExamOptions')
            ->once()
            ->andReturn(collect([(object) ['id' => 3, 'title' => 'Foundations']]));
        $repo->shouldReceive('getPublishedNationalExamOptions')
            ->once()
            ->andReturn(collect());

        $service = new AssessmentReportReadService($repo);
        $request = Request::create('/assessment-reports/by-resident', 'GET');
        $request->setUserResolver(fn () => $user);

        $payload = $service->byResidentPayload($request);

        $this->assertFalse($payload['isSystemAdmin']);
        $this->assertCount(1, $payload['attempts']['data']);
        $this->assertSame('Foundations', $payload['exams'][0]['title']);
        $this->assertSame('Quiz', $payload['attempts']['data'][0]['exam_category']);
    }

    public function test_it_builds_live_monitor_payload_with_suspicious_session_details(): void
    {
        Carbon::setTestNow('2026-06-22 12:00:00');

        $organization = new Organization(['id' => 10, 'type' => 'institution']);
        $user = Mockery::mock(User::class)->makePartial();
        $user->current_organization_id = 10;
        $user->currentOrganization = $organization;
        $user->shouldReceive('hasPermissionTo')->with('view-all-assessment-reports')->andReturn(false);

        $attempt = (object) [
            'id' => 9,
            'user_id' => 77,
            'user' => (object) ['name' => 'Resident User', 'email' => 'resident@example.com'],
            'assessment' => (object) ['title' => 'General Nursing', 'exam_category' => 'In-Service'],
            'organization' => (object) ['name' => 'Alpha Hospital'],
            'started_at' => Carbon::parse('2026-06-22 11:30:00'),
            'last_activity_at' => Carbon::parse('2026-06-22 11:56:00'),
            'ip_address' => '10.0.0.1',
            'browser_metadata' => ['browser' => 'Chrome', 'device' => 'Desktop'],
            'connection_type' => 'wifi',
            'connection_speed' => 20.45,
            'total_idle_time' => 120,
            'idle_periods_count' => 1,
            'active_session_id' => 'session-12345678',
        ];

        $repo = Mockery::mock(AssessmentReportRepositoryInterface::class);
        $repo->shouldReceive('getLiveInstitutionAttempts')->once()->andReturn(collect([$attempt]));
        $repo->shouldReceive('getLiveNationalAttempts')->never();
        $repo->shouldReceive('getActiveWebSessionsForUsers')->once()->andReturn(collect([
            77 => collect([
                (object) [
                    'id' => 'session-12345678',
                    'ip_address' => '10.0.0.1',
                    'user_agent' => 'Mozilla/5.0 Chrome/120.0',
                    'last_activity' => Carbon::now()->subMinute()->timestamp,
                ],
                (object) [
                    'id' => 'session-87654321',
                    'ip_address' => '10.0.0.2',
                    'user_agent' => 'Mozilla/5.0 Firefox/120.0',
                    'last_activity' => Carbon::now()->subMinutes(3)->timestamp,
                ],
            ]),
        ]));
        $repo->shouldReceive('getSessionChangesForInstitutionAttempt')->once()->andReturn(collect([
            (object) [
                'change_type' => 'both',
                'previous_user_agent' => 'Mozilla/5.0 Chrome/119.0',
                'new_user_agent' => 'Mozilla/5.0 Firefox/120.0',
                'previous_ip_address' => '10.0.0.1',
                'new_ip_address' => '10.0.0.2',
                'browser_info' => ['browser' => 'Firefox'],
                'detected_at' => Carbon::parse('2026-06-22 11:50:00'),
            ],
        ]));
        $repo->shouldReceive('getSessionChangesForNationalAttempt')->never();
        $repo->shouldReceive('getIdlePeriodsForInstitutionAttempt')->once()->andReturn(collect([
            (object) [
                'started_at' => Carbon::parse('2026-06-22 11:40:00'),
                'ended_at' => Carbon::parse('2026-06-22 11:42:00'),
                'duration_seconds' => 120,
            ],
        ]));
        $repo->shouldReceive('getIdlePeriodsForNationalAttempt')->never();
        $repo->shouldReceive('getPublishedInstitutionExamOptions')->once()->andReturn(collect());
        $repo->shouldReceive('getPublishedNationalExamOptions')->once()->andReturn(collect());

        $service = new AssessmentReportReadService($repo);
        $request = Request::create('/assessment-reports/live-monitor', 'GET');
        $request->setUserResolver(fn () => $user);

        $payload = $service->liveMonitorPayload($request);

        $this->assertCount(1, $payload['activeSessions']);
        $this->assertTrue($payload['activeSessions'][0]['is_suspicious']);
        $this->assertSame(2, $payload['activeSessions'][0]['active_account_sessions_count']);
        $this->assertSame('Firefox', $payload['activeSessions'][0]['browser_change_details'][0]['to']);
        $this->assertSame('10.0.0.2', $payload['activeSessions'][0]['ip_change_details'][0]['to']);

        Carbon::setTestNow();
    }

    public function test_it_builds_live_monitor_payload_for_all_organizations_with_institution_and_national_attempts(): void
    {
        Carbon::setTestNow('2026-06-22 12:00:00');

        $user = Mockery::mock(User::class)->makePartial();
        $user->current_organization_id = 10;
        $user->currentOrganization = (object) [
            'id' => 10,
            'type' => 'institution',
            'slug' => 'alpha-hospital',
        ];
        $user->shouldReceive('hasPermissionTo')->with('view-all-assessment-reports')->andReturn(true);

        $institutionAttempt = (object) [
            'id' => 9,
            'user_id' => 77,
            'user' => (object) ['name' => 'Resident User', 'email' => 'resident@example.com'],
            'assessment' => (object) ['title' => 'General Nursing', 'exam_category' => 'Midterm'],
            'organization' => (object) ['name' => 'Alpha Hospital'],
            'started_at' => Carbon::parse('2026-06-22 11:30:00'),
            'last_activity_at' => Carbon::parse('2026-06-22 11:56:00'),
            'ip_address' => '10.0.0.1',
            'browser_metadata' => ['browser' => 'Chrome', 'device' => 'Desktop'],
            'connection_type' => 'wifi',
            'connection_speed' => 20.45,
            'total_idle_time' => 0,
            'idle_periods_count' => 0,
            'active_session_id' => 'session-12345678',
        ];

        $nationalAttempt = (object) [
            'id' => 12,
            'user_id' => 88,
            'user' => (object) ['name' => 'National Resident', 'email' => 'national@example.com'],
            'assessment' => (object) ['title' => 'In-Service Boards', 'category' => 'In-Service'],
            'organization' => (object) ['name' => 'Beta Medical Center'],
            'started_at' => Carbon::parse('2026-06-22 11:45:00'),
            'last_activity_at' => Carbon::parse('2026-06-22 11:59:00'),
            'ip_address' => '10.0.0.5',
            'browser_metadata' => ['browser' => 'Edge', 'device' => 'Laptop'],
            'connection_type' => 'ethernet',
            'connection_speed' => 35.2,
            'total_idle_time' => 0,
            'idle_periods_count' => 0,
            'active_session_id' => 'session-22223333',
        ];

        $repo = Mockery::mock(AssessmentReportRepositoryInterface::class);
        $repo->shouldReceive('getLiveInstitutionAttempts')->once()->andReturn(collect([$institutionAttempt]));
        $repo->shouldReceive('getLiveNationalAttempts')->once()->andReturn(collect([$nationalAttempt]));
        $repo->shouldReceive('getActiveWebSessionsForUsers')->once()->andReturn(collect([
            77 => collect(),
            88 => collect(),
        ]));
        $repo->shouldReceive('getSessionChangesForInstitutionAttempt')->once()->andReturn(collect());
        $repo->shouldReceive('getSessionChangesForNationalAttempt')->once()->andReturn(collect());
        $repo->shouldReceive('getIdlePeriodsForInstitutionAttempt')->once()->andReturn(collect());
        $repo->shouldReceive('getIdlePeriodsForNationalAttempt')->once()->andReturn(collect());
        $repo->shouldReceive('getOrganizations')->once()->andReturn(collect([
            (object) ['id' => 10, 'name' => 'Alpha Hospital'],
            (object) ['id' => 11, 'name' => 'Beta Medical Center'],
        ]));
        $repo->shouldReceive('getPublishedInstitutionExamOptions')->once()->andReturn(collect([
            (object) ['id' => 3, 'title' => 'Foundations'],
        ]));
        $repo->shouldReceive('getPublishedNationalExamOptions')->once()->andReturn(collect([
            (object) ['id' => 4, 'title' => 'National Boards'],
        ]));

        $service = new AssessmentReportReadService($repo);
        $request = Request::create('/assessment-reports/live-monitor?org=all-organizations', 'GET');
        $request->setUserResolver(fn () => $user);

        $payload = $service->liveMonitorPayload($request);

        $this->assertCount(2, $payload['activeSessions']);
        $this->assertSame('National Resident', $payload['activeSessions'][0]['resident_name']);
        $this->assertSame('national', $payload['activeSessions'][0]['exam_scope']);
        $this->assertSame('Beta Medical Center', $payload['activeSessions'][0]['organization_name']);
        $this->assertSame('Resident User', $payload['activeSessions'][1]['resident_name']);
        $this->assertCount(2, $payload['exams']);
        $this->assertSame('institution_3', $payload['exams'][0]['id']);
        $this->assertSame('national_4', $payload['exams'][1]['id']);

        Carbon::setTestNow();
    }

    public function test_it_builds_by_resident_payload_for_all_organizations_context(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->current_organization_id = 10;
        $user->currentOrganization = (object) [
            'id' => 10,
            'type' => 'institution',
            'slug' => 'alpha-hospital',
        ];
        $user->shouldReceive('hasPermissionTo')->with('view-all-assessment-reports')->andReturn(true);

        $repo = Mockery::mock(AssessmentReportRepositoryInterface::class);
        $repo->shouldReceive('getCompletedInstitutionAttemptsForReport')
            ->once()
            ->andReturn(collect([
                $this->makeAttempt([
                    'id' => 5,
                    'type' => 'institution',
                    'exam_category' => 'Quiz',
                    'submitted_at' => Carbon::parse('2026-06-22 10:00:00'),
                ]),
            ]));
        $repo->shouldReceive('getCompletedNationalAttemptsForReport')
            ->once()
            ->andReturn(collect([
                $this->makeAttempt([
                    'id' => 6,
                    'type' => 'national',
                    'exam_category' => 'In-Service',
                    'submitted_at' => Carbon::parse('2026-06-23 08:00:00'),
                ]),
            ]));
        $repo->shouldReceive('getOrganizations')->once()->andReturn(collect([
            (object) ['id' => 10, 'name' => 'Alpha Hospital'],
        ]));
        $repo->shouldReceive('getPublishedInstitutionExamOptions')
            ->once()
            ->andReturn(collect([(object) ['id' => 3, 'title' => 'Foundations']]));
        $repo->shouldReceive('getPublishedNationalExamOptions')
            ->once()
            ->andReturn(collect([(object) ['id' => 4, 'title' => 'National Boards']]));

        $service = new AssessmentReportReadService($repo);
        $request = Request::create('/assessment-reports/by-resident?org=all-organizations', 'GET');
        $request->setUserResolver(fn () => $user);

        $payload = $service->byResidentPayload($request);

        $this->assertTrue($payload['isSystemAdmin']);
        $this->assertCount(2, $payload['attempts']['data']);
        $this->assertCount(2, $payload['exams']);
        $this->assertSame('Foundations', $payload['exams'][0]['title']);
        $this->assertSame('National Boards', $payload['exams'][1]['title']);
    }

    private function makeAttempt(array $attributes): object
    {
        $submittedAt = $attributes['submitted_at'];
        $startedAt = (clone $submittedAt)->subMinutes(15);

        return new class($attributes, $startedAt, $submittedAt)
        {
            public int $id;
            public string $year_level = 'R1';
            public int $score = 8;
            public int $total_points = 10;
            public float $percentage = 80.0;
            public object $user;
            public object $assessment;
            public object $organization;
            public Carbon $started_at;
            public Carbon $submitted_at;

            public function __construct(array $attributes, Carbon $startedAt, Carbon $submittedAt)
            {
                $this->id = $attributes['id'];
                $this->started_at = $startedAt;
                $this->submitted_at = $submittedAt;
                $this->user = (object) ['name' => 'Resident User', 'email' => 'resident@example.com'];
                $this->assessment = (object) [
                    'title' => 'Foundations',
                    'exam_category' => $attributes['exam_category'] ?? 'Quiz',
                    'category' => $attributes['exam_category'] ?? 'Quiz',
                    'passing_score' => 6,
                ];
                $this->organization = (object) ['name' => 'Alpha Hospital'];
            }

            public function isPassed(): bool
            {
                return true;
            }
        };
    }
}
