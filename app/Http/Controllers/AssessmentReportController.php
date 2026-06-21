<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\AssessmentReportRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AssessmentReportController extends Controller
{
    public function __construct(
        private readonly AssessmentReportRepositoryInterface $assessmentReportRepository,
    ) {}

    /**
     * Display resident exam attempts with filtering.
     */
    public function byResident(Request $request): Response
    {
        $user = $request->user();
        $organizationId = $user->current_organization_id;
        // Check if user has permission to view all organizations' assessment reports
        $canViewAllOrganizations = $user->hasPermissionTo('view-all-assessment-reports');

        // Get current organization to determine which attempts to show
        $currentOrg = $user->currentOrganization;
        $orgType = $currentOrg?->type ? strtolower($currentOrg->type) : null;

        // Get institution attempts
        $isInstitutionExam = false;
        $isNationalExam = false;
        $examId = null;
        $examType = null;

        if ($request->filled('exam')) {
            $examFilter = $request->input('exam');
            if (str_starts_with($examFilter, 'institution_')) {
                $examId = (int) str_replace('institution_', '', $examFilter);
                $examType = 'institution';
                $isInstitutionExam = $this->assessmentReportRepository->institutionExamExists($examId);
            } elseif (str_starts_with($examFilter, 'national_')) {
                $examId = (int) str_replace('national_', '', $examFilter);
                $examType = 'national';
                $isNationalExam = $this->assessmentReportRepository->nationalExamExists($examId);
            } else {
                $examId = (int) $examFilter;
                $isInstitutionExam = $this->assessmentReportRepository->institutionExamExists($examId);
                if ($isInstitutionExam) {
                    $examType = 'institution';
                } else {
                    $isNationalExam = $this->assessmentReportRepository->nationalExamExists($examId);
                    if ($isNationalExam) {
                        $examType = 'national';
                    }
                }
            }
        }

        $filters = array_merge(
            $request->only(['search', 'organization', 'year_level', 'status', 'date_from', 'date_to']),
            [
                'exam_id' => $examId,
                'exam_type' => $examType,
                'can_view_all_organizations' => $canViewAllOrganizations,
            ]
        );

        // Only get institution attempts if:
        // 1. No exam filter OR it's an institution exam
        // 2. AND (organization is institution type OR system admin viewing all orgs)
        $institutionAttempts = collect();
        $shouldShowInstitutionAttempts = (! $request->filled('exam') || $isInstitutionExam)
            && ($orgType === 'institution' || ($canViewAllOrganizations && ! $orgType));

        if ($shouldShowInstitutionAttempts) {
            $institutionAttempts = $this->assessmentReportRepository
                ->getCompletedInstitutionAttemptsForReport($filters, $organizationId, $canViewAllOrganizations)
                ->map(fn($attempt) => [
                    'id' => $attempt->id,
                    'type' => 'institution',
                    'resident_name' => $attempt->user->name,
                    'resident_email' => $attempt->user->email,
                    'year_level' => $attempt->year_level,
                    'exam_title' => $attempt->assessment->title,
                    'exam_category' => $attempt->assessment->exam_category,
                    'score' => $attempt->score,
                    'total_points' => $attempt->total_points,
                    'percentage' => $attempt->percentage,
                    'passing_score' => $attempt->assessment->passing_score,
                    'status' => $attempt->isPassed() ? 'Passed' : 'Failed',
                    'organization_name' => $attempt->organization->name,
                    'submitted_at' => $attempt->submitted_at,
                    'time_spent' => $attempt->started_at && $attempt->submitted_at
                        ? $attempt->started_at->diffInMinutes($attempt->submitted_at)
                        : null,
                ]);
        }

        // Only get national attempts if:
        // 1. No exam filter OR it's a national exam
        // 2. AND (organization is national/inservice type OR system admin viewing all orgs)
        $nationalAttempts = collect();
        $shouldShowNationalAttempts = (! $request->filled('exam') || $isNationalExam)
            && (($orgType && in_array($orgType, ['national', 'inservice'])) || ($canViewAllOrganizations && ! $orgType));

        if ($shouldShowNationalAttempts) {
            $nationalAttempts = $this->assessmentReportRepository
                ->getCompletedNationalAttemptsForReport($filters, $organizationId, $canViewAllOrganizations)
                ->map(fn($attempt) => [
                    'id' => $attempt->id,
                    'type' => 'inservice',
                    'resident_name' => $attempt->user->name,
                    'resident_email' => $attempt->user->email,
                    'year_level' => $attempt->year_level,
                    'exam_title' => $attempt->assessment->title,
                    'exam_category' => $attempt->assessment->category,
                    'score' => $attempt->score,
                    'total_points' => $attempt->total_points,
                    'percentage' => $attempt->percentage,
                    'passing_score' => $attempt->assessment->passing_score,
                    'status' => $attempt->isPassed() ? 'Passed' : 'Failed',
                    'organization_name' => $attempt->organization->name,
                    'submitted_at' => $attempt->submitted_at,
                    'time_spent' => $attempt->started_at && $attempt->submitted_at
                        ? $attempt->started_at->diffInMinutes($attempt->submitted_at)
                        : null,
                ]);
        }

        // Combine and sort all attempts
        $allAttempts = $institutionAttempts->concat($nationalAttempts)
            ->sortByDesc('submitted_at')
            ->values();

        // Manual pagination
        $page = $request->input('page', 1);
        $perPage = 20;
        $total = $allAttempts->count();
        $offset = ($page - 1) * $perPage;
        $paginatedAttempts = $allAttempts->slice($offset, $perPage);

        $attempts = [
            'data' => $paginatedAttempts->map(fn($attempt) => [
                'id' => $attempt['id'],
                'resident_name' => $attempt['resident_name'],
                'resident_email' => $attempt['resident_email'],
                'year_level' => $attempt['year_level'],
                'exam_title' => $attempt['exam_title'],
                'exam_category' => $attempt['exam_category'],
                'score' => $attempt['score'],
                'total_points' => $attempt['total_points'],
                'percentage' => $attempt['percentage'],
                'passing_score' => $attempt['passing_score'],
                'status' => $attempt['status'],
                'organization_name' => $attempt['organization_name'],
                'submitted_at' => $attempt['submitted_at']?->format('M d, Y h:i A'),
                'time_spent' => $attempt['time_spent'] ? $attempt['time_spent'] . ' mins' : 'N/A',
            ])->values(),
            'total' => $total,
            'current_page' => (int) $page,
            'last_page' => (int) ceil($total / $perPage),
            'per_page' => $perPage,
        ];

        // Get filter options
        $organizations = $canViewAllOrganizations
            ? $this->assessmentReportRepository->getOrganizations()
            : collect();

        // Get current organization to determine exam type filtering
        $currentOrg = $user->currentOrganization;
        $orgType = $currentOrg?->type;

        // Get both institution and national exams
        $institutionExams = $this->assessmentReportRepository
            ->getPublishedInstitutionExamOptions($organizationId, $canViewAllOrganizations)
            ->map(fn($exam) => ['id' => 'institution_' . $exam->id, 'title' => $exam->title, 'type' => 'institution', 'original_id' => $exam->id]);

        $nationalExams = $this->assessmentReportRepository
            ->getPublishedNationalExamOptions()
            ->map(fn($exam) => ['id' => 'national_' . $exam->id, 'title' => $exam->title, 'type' => 'inservice', 'original_id' => $exam->id]);

        // Filter exams based on organization type
        $exams = collect();
        if ($orgType && strtolower($orgType) === 'institution') {
            // Institution organizations: show only institution exams
            $exams = $institutionExams;
        } elseif ($orgType && in_array(strtolower($orgType), ['national', 'inservice'])) {
            // National/Inservice organizations: show only inservice exams
            $exams = $nationalExams;
        } else {
            // Default: show all exams (for system admins or unknown types)
            $exams = $institutionExams->concat($nationalExams);
        }

        $exams = $exams->sortBy('title')->values();

        return Inertia::render('assessment-reports/by-resident', [
            'attempts' => $attempts,
            'filters' => $request->only([
                'search',
                'exam',
                'organization',
                'year_level',
                'status',
                'date_from',
                'date_to',
            ]),
            'organizations' => $organizations,
            'exams' => $exams,
            'isSystemAdmin' => $canViewAllOrganizations,
        ]);
    }

    /**
     * Live monitoring of active exam sessions.
     */
    public function liveMonitor(Request $request): Response
    {
        $user = $request->user();
        $organizationId = $user->current_organization_id;
        // Check if user has permission to view all organizations' assessment reports
        $canViewAllOrganizations = $user->hasPermissionTo('view-all-assessment-reports');

        $activeSessions = $this->assessmentReportRepository
            ->getLiveInstitutionAttempts($request->only(['exam', 'organization', 'activity_status']), $organizationId, $canViewAllOrganizations);

        $webSessionsByUser = $this->assessmentReportRepository
            ->getActiveWebSessionsForUsers($activeSessions->pluck('user_id')->all());

        $activeSessions = $activeSessions
            ->map(function ($attempt) use ($webSessionsByUser) {
                $webSessions = collect($webSessionsByUser->get($attempt->user_id, collect()));
                $sessionChanges = $this->assessmentReportRepository->getSessionChangesForInstitutionAttempt($attempt->id);

                // Get browser change details (deduplicated)
                $browserChanges = $sessionChanges
                    ->whereIn('change_type', ['browser', 'both'])
                    ->sortBy('detected_at')
                    ->unique(function ($change) {
                        // Deduplicate by combining user agents and timestamp (rounded to minute)
                        return $change->previous_user_agent .
                            '|' . $change->new_user_agent .
                            '|' . $change->detected_at->format('Y-m-d H:i');
                    })
                    ->map(fn($change) => [
                        'from' => $this->extractBrowserName($change->previous_user_agent),
                        'to' => $change->browser_info['browser'] ?? $this->extractBrowserName($change->new_user_agent),
                        'time' => $change->detected_at->format('h:i A'),
                    ])
                    ->values(); // Reset array keys after deduplication

                // Get IP change details (deduplicated)
                $ipChanges = $sessionChanges
                    ->whereIn('change_type', ['ip_address', 'both'])
                    ->sortBy('detected_at')
                    ->unique(function ($change) {
                        // Deduplicate by IP addresses and timestamp (rounded to minute)
                        return $change->previous_ip_address .
                            '|' . $change->new_ip_address .
                            '|' . $change->detected_at->format('Y-m-d H:i');
                    })
                    ->map(fn($change) => [
                        'from' => $change->previous_ip_address ?? 'Unknown',
                        'to' => $change->new_ip_address ?? 'Unknown',
                        'time' => $change->detected_at->format('h:i A'),
                    ])
                    ->values();

                // Get idle period details
                $idlePeriods = $this->assessmentReportRepository
                    ->getIdlePeriodsForInstitutionAttempt($attempt->id)
                    ->map(fn($period) => [
                        'started_at' => $period->started_at->format('M d, h:i A'),
                        'ended_at' => $period->ended_at->format('M d, h:i A'),
                        'duration' => gmdate('H:i:s', $period->duration_seconds),
                        'duration_minutes' => round($period->duration_seconds / 60, 1),
                    ]);

                $activeAccountSessions = $webSessions->map(function ($session) {
                    $browser = 'Unknown';

                    if (is_string($session->user_agent)) {
                        $browser = $this->extractBrowserName($session->user_agent);
                    }

                    return [
                        'id' => $session->id,
                        'short_id' => substr($session->id, 0, 8),
                        'ip_address' => $session->ip_address,
                        'browser' => $browser,
                        'last_activity' => Carbon::createFromTimestamp((int) $session->last_activity)->diffForHumans(),
                    ];
                })->values();

                $lockSessionIsActive = $attempt->active_session_id
                    ? $activeAccountSessions->contains(fn ($session) => $session['id'] === $attempt->active_session_id)
                    : false;

                return [
                    'id' => $attempt->id,
                    'resident_name' => $attempt->user->name,
                    'resident_email' => $attempt->user->email,
                    'exam_title' => $attempt->assessment->title,
                    'exam_category' => $attempt->assessment->exam_category,
                    'organization_name' => $attempt->organization->name,
                    'started_at' => $attempt->started_at?->format('M d, Y h:i A'),
                    'time_elapsed' => $attempt->started_at?->diffInMinutes(now()) . ' mins',
                    'last_activity' => $attempt->last_activity_at
                        ? $attempt->last_activity_at->diffForHumans()
                        : 'No activity yet',
                    'is_idle' => $attempt->last_activity_at && $attempt->last_activity_at < now()->subMinutes(2),
                    'ip_address' => $attempt->ip_address,
                    'browser' => $attempt->browser_metadata['browser'] ?? 'Unknown',
                    'device' => $attempt->browser_metadata['device'] ?? 'Unknown',
                    'connection' => $attempt->connection_type,
                    'speed' => $attempt->connection_speed ? round($attempt->connection_speed, 1) . ' Mbps' : 'N/A',
                    'ip_changes' => $ipChanges->count(), // Use actual deduplicated count
                    'ip_change_details' => $ipChanges,
                    'browser_changes' => $browserChanges->count(), // Use actual deduplicated count
                    'browser_change_details' => $browserChanges,
                    'idle_time' => gmdate('H:i:s', $attempt->total_idle_time),
                    'idle_periods' => $attempt->idle_periods_count,
                    'idle_period_details' => $idlePeriods,
                    'locked_session_id' => $attempt->active_session_id,
                    'locked_session_short_id' => $attempt->active_session_id ? substr($attempt->active_session_id, 0, 8) : null,
                    'lock_session_is_active' => $lockSessionIsActive,
                    'active_account_sessions_count' => $activeAccountSessions->count(),
                    'active_account_sessions' => $activeAccountSessions,
                    'has_multiple_account_sessions' => $activeAccountSessions->count() > 1,
                    'is_suspicious' => $ipChanges->count() > 0 || $browserChanges->count() > 0 || $activeAccountSessions->count() > 1,
                ];
            });

        // Filter options
        $organizations = $canViewAllOrganizations
            ? $this->assessmentReportRepository->getOrganizations()
            : collect();

        $exams = $this->assessmentReportRepository
            ->getPublishedInstitutionExamOptions($organizationId, $canViewAllOrganizations);

        return Inertia::render('assessment-reports/live-monitor', [
            'activeSessions' => $activeSessions,
            'filters' => $request->only(['exam', 'organization', 'activity_status']),
            'organizations' => $organizations,
            'exams' => $exams,
            'isSystemAdmin' => $canViewAllOrganizations,
            'lastUpdate' => now()->format('h:i:s A'),
        ]);
    }

    /**
     * Extract browser name from user agent string.
     */
    private function extractBrowserName(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Unknown';
        }

        // Check most specific browsers first (Edge has multiple identifiers)
        if (
            str_contains($userAgent, 'Edg/') ||
            str_contains($userAgent, 'Edge/') ||
            str_contains($userAgent, 'EdgA/') ||
            str_contains($userAgent, 'EdgiOS/')
        ) {
            return 'Edge';
        } elseif (str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera/')) {
            return 'Opera';
        } elseif (str_contains($userAgent, 'Vivaldi/')) {
            return 'Vivaldi';
        } elseif (str_contains($userAgent, 'Arc/')) {
            return 'Arc';
        } elseif (str_contains($userAgent, 'SamsungBrowser/')) {
            return 'Samsung Internet';
        } elseif (str_contains($userAgent, 'UCBrowser/')) {
            return 'UC Browser';
        } elseif (str_contains($userAgent, 'DuckDuckGo/')) {
            return 'DuckDuckGo';
        } elseif (str_contains($userAgent, 'YaBrowser/')) {
            return 'Yandex';
        } elseif (str_contains($userAgent, 'Firefox')) {
            return 'Firefox';
        } elseif (str_contains($userAgent, 'Chrome')) {
            return 'Chrome';
        } elseif (str_contains($userAgent, 'Safari')) {
            return 'Safari';
        } elseif (str_contains($userAgent, 'MSIE') || str_contains($userAgent, 'Trident/')) {
            return 'Internet Explorer';
        } elseif (str_contains($userAgent, 'Chromium/')) {
            return 'Chromium';
        }

        return 'Unknown';
    }
}
