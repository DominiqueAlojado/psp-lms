<?php

namespace App\Services;

use App\Repositories\Contracts\AssessmentReportRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AssessmentReportReadService
{
    private const ALL_ORGANIZATIONS_SLUG = 'all-organizations';

    public function __construct(
        private readonly AssessmentReportRepositoryInterface $assessmentReportRepository,
    ) {}

    public function byResidentPayload(Request $request): array
    {
        $user = $request->user();
        $organizationId = $user->current_organization_id;
        $canViewAllOrganizations = $user->hasPermissionTo('view-all-assessment-reports');
        $currentOrg = $this->resolveReportOrganizationContext($request);
        $orgType = $currentOrg?->type ? strtolower($currentOrg->type) : null;
        $isAllOrganizationsContext = data_get($currentOrg, 'slug') === self::ALL_ORGANIZATIONS_SLUG;

        [$examId, $examType, $isInstitutionExam, $isNationalExam] = $this->parseExamFilter($request->input('exam'));

        $filters = array_merge(
            $request->only(['search', 'organization', 'year_level', 'status', 'date_from', 'date_to']),
            [
                'exam_id' => $examId,
                'exam_type' => $examType,
                'can_view_all_organizations' => $canViewAllOrganizations,
            ]
        );

        $institutionAttempts = collect();
        $shouldShowInstitutionAttempts = (! $request->filled('exam') || $isInstitutionExam)
            && ($orgType === 'institution' || $isAllOrganizationsContext || ($canViewAllOrganizations && ! $orgType));

        if ($shouldShowInstitutionAttempts) {
            $institutionAttempts = $this->assessmentReportRepository
                ->getCompletedInstitutionAttemptsForReport($filters, $organizationId, $canViewAllOrganizations)
                ->map(fn ($attempt) => [
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

        $nationalAttempts = collect();
        $shouldShowNationalAttempts = (! $request->filled('exam') || $isNationalExam)
            && (($orgType && in_array($orgType, ['national', 'inservice'])) || $isAllOrganizationsContext || ($canViewAllOrganizations && ! $orgType));

        if ($shouldShowNationalAttempts) {
            $nationalAttempts = $this->assessmentReportRepository
                ->getCompletedNationalAttemptsForReport($filters, $organizationId, $canViewAllOrganizations)
                ->map(fn ($attempt) => [
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

        $allAttempts = $institutionAttempts->concat($nationalAttempts)
            ->sortByDesc('submitted_at')
            ->values();

        $page = (int) $request->input('page', 1);
        $perPage = 20;
        $total = $allAttempts->count();
        $offset = ($page - 1) * $perPage;
        $paginatedAttempts = $allAttempts->slice($offset, $perPage);

        $attempts = [
            'data' => $paginatedAttempts->map(fn ($attempt) => [
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
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
            'per_page' => $perPage,
        ];

        $organizations = $canViewAllOrganizations
            ? $this->assessmentReportRepository->getOrganizations()
            : collect();

        $orgType = $currentOrg?->type;
        $institutionExams = $this->assessmentReportRepository
            ->getPublishedInstitutionExamOptions($organizationId, $canViewAllOrganizations)
            ->map(fn ($exam) => ['id' => 'institution_' . $exam->id, 'title' => $exam->title, 'type' => 'institution', 'original_id' => $exam->id]);

        $nationalExams = $this->assessmentReportRepository
            ->getPublishedNationalExamOptions()
            ->map(fn ($exam) => ['id' => 'national_' . $exam->id, 'title' => $exam->title, 'type' => 'inservice', 'original_id' => $exam->id]);

        $exams = collect();
        if ($orgType && strtolower($orgType) === 'institution') {
            $exams = $institutionExams;
        } elseif ($orgType && in_array(strtolower($orgType), ['national', 'inservice'])) {
            $exams = $nationalExams;
        } else {
            $exams = $institutionExams->concat($nationalExams);
        }

        return [
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
            'exams' => $exams->sortBy('title')->values(),
            'isSystemAdmin' => $canViewAllOrganizations,
        ];
    }

    public function liveMonitorPayload(Request $request): array
    {
        $user = $request->user();
        $organizationId = $user->current_organization_id;
        $canViewAllOrganizations = $user->hasPermissionTo('view-all-assessment-reports');
        $currentOrg = $this->resolveReportOrganizationContext($request);
        $orgType = $currentOrg?->type ? strtolower($currentOrg->type) : null;
        $isAllOrganizationsContext = data_get($currentOrg, 'slug') === self::ALL_ORGANIZATIONS_SLUG;

        [$examId, $examType, $isInstitutionExam, $isNationalExam] = $this->parseExamFilter($request->input('exam'));

        $filters = array_merge(
            $request->only(['organization', 'activity_status']),
            [
                'exam_id' => $examId,
                'exam_type' => $examType,
            ]
        );

        $institutionSessions = collect();
        $shouldShowInstitutionSessions = (! $request->filled('exam') || $isInstitutionExam)
            && ($orgType === 'institution' || $isAllOrganizationsContext || ($canViewAllOrganizations && ! $orgType));

        if ($shouldShowInstitutionSessions) {
            $institutionSessions = $this->assessmentReportRepository
                ->getLiveInstitutionAttempts($filters, $organizationId, $canViewAllOrganizations)
                ->map(fn ($attempt) => $this->mapLiveAttempt($attempt, 'institution'));
        }

        $nationalSessions = collect();
        $shouldShowNationalSessions = (! $request->filled('exam') || $isNationalExam)
            && (($orgType && in_array($orgType, ['national', 'inservice'])) || $isAllOrganizationsContext || ($canViewAllOrganizations && ! $orgType));

        if ($shouldShowNationalSessions) {
            $nationalSessions = $this->assessmentReportRepository
                ->getLiveNationalAttempts($filters, $organizationId, $canViewAllOrganizations)
                ->map(fn ($attempt) => $this->mapLiveAttempt($attempt, 'national'));
        }

        $activeSessions = $institutionSessions
            ->concat($nationalSessions)
            ->sortByDesc('started_at_sort')
            ->values();

        $webSessionsByUser = $this->assessmentReportRepository
            ->getActiveWebSessionsForUsers($activeSessions->pluck('user_id')->all());

        $activeSessions = $activeSessions
            ->map(function (array $attempt) use ($webSessionsByUser) {
                $webSessions = collect($webSessionsByUser->get($attempt['user_id'], collect()));
                $sessionChanges = $attempt['type'] === 'national'
                    ? $this->assessmentReportRepository->getSessionChangesForNationalAttempt($attempt['id'])
                    : $this->assessmentReportRepository->getSessionChangesForInstitutionAttempt($attempt['id']);

                $browserChanges = $sessionChanges
                    ->whereIn('change_type', ['browser', 'both'])
                    ->sortBy('detected_at')
                    ->unique(fn ($change) => $change->previous_user_agent . '|' . $change->new_user_agent . '|' . $change->detected_at->format('Y-m-d H:i'))
                    ->map(fn ($change) => [
                        'from' => $this->extractBrowserName($change->previous_user_agent),
                        'to' => $change->browser_info['browser'] ?? $this->extractBrowserName($change->new_user_agent),
                        'time' => $change->detected_at->format('h:i A'),
                    ])
                    ->values();

                $ipChanges = $sessionChanges
                    ->whereIn('change_type', ['ip_address', 'both'])
                    ->sortBy('detected_at')
                    ->unique(fn ($change) => $change->previous_ip_address . '|' . $change->new_ip_address . '|' . $change->detected_at->format('Y-m-d H:i'))
                    ->map(fn ($change) => [
                        'from' => $change->previous_ip_address ?? 'Unknown',
                        'to' => $change->new_ip_address ?? 'Unknown',
                        'time' => $change->detected_at->format('h:i A'),
                    ])
                    ->values();

                $idlePeriods = ($attempt['type'] === 'national'
                    ? $this->assessmentReportRepository->getIdlePeriodsForNationalAttempt($attempt['id'])
                    : $this->assessmentReportRepository->getIdlePeriodsForInstitutionAttempt($attempt['id']))
                    ->map(fn ($period) => [
                        'started_at' => $period->started_at->format('M d, h:i A'),
                        'ended_at' => $period->ended_at->format('M d, h:i A'),
                        'duration' => gmdate('H:i:s', $period->duration_seconds),
                        'duration_minutes' => round($period->duration_seconds / 60, 1),
                    ]);

                $activeAccountSessions = $webSessions->map(function ($session) {
                    $browser = is_string($session->user_agent)
                        ? $this->extractBrowserName($session->user_agent)
                        : 'Unknown';

                    return [
                        'id' => $session->id,
                        'short_id' => substr($session->id, 0, 8),
                        'ip_address' => $session->ip_address,
                        'browser' => $browser,
                        'last_activity' => Carbon::createFromTimestamp((int) $session->last_activity)->diffForHumans(),
                    ];
                })->values();

                $lockSessionIsActive = $attempt['active_session_id']
                    ? $activeAccountSessions->contains(fn ($session) => $session['id'] === $attempt['active_session_id'])
                    : false;

                return [
                    'id' => $attempt['id'],
                    'resident_name' => $attempt['resident_name'],
                    'resident_email' => $attempt['resident_email'],
                    'exam_title' => $attempt['exam_title'],
                    'exam_category' => $attempt['exam_category'],
                    'exam_scope' => $attempt['type'],
                    'organization_name' => $attempt['organization_name'],
                    'started_at' => $attempt['started_at_label'],
                    'time_elapsed' => $attempt['time_elapsed'],
                    'last_activity' => $attempt['last_activity_at']
                        ? $attempt['last_activity_at']->diffForHumans()
                        : 'No activity yet',
                    'is_idle' => $attempt['last_activity_at'] && $attempt['last_activity_at'] < now()->subMinutes(2),
                    'ip_address' => $attempt['ip_address'],
                    'browser' => $attempt['browser'],
                    'device' => $attempt['device'],
                    'connection' => $attempt['connection_type'],
                    'speed' => $attempt['speed'],
                    'ip_changes' => $ipChanges->count(),
                    'ip_change_details' => $ipChanges,
                    'browser_changes' => $browserChanges->count(),
                    'browser_change_details' => $browserChanges,
                    'idle_time' => gmdate('H:i:s', $attempt['total_idle_time']),
                    'idle_periods' => $attempt['idle_periods_count'],
                    'idle_period_details' => $idlePeriods,
                    'locked_session_id' => $attempt['active_session_id'],
                    'locked_session_short_id' => $attempt['active_session_id'] ? substr($attempt['active_session_id'], 0, 8) : null,
                    'lock_session_is_active' => $lockSessionIsActive,
                    'active_account_sessions_count' => $activeAccountSessions->count(),
                    'active_account_sessions' => $activeAccountSessions,
                    'has_multiple_account_sessions' => $activeAccountSessions->count() > 1,
                    'is_suspicious' => $ipChanges->count() > 0 || $browserChanges->count() > 0 || $activeAccountSessions->count() > 1,
                ];
            });

        $organizations = $canViewAllOrganizations
            ? $this->assessmentReportRepository->getOrganizations()
            : collect();

        $institutionExams = $this->assessmentReportRepository
            ->getPublishedInstitutionExamOptions($organizationId, $canViewAllOrganizations)
            ->map(fn ($exam) => ['id' => 'institution_' . $exam->id, 'title' => $exam->title, 'scope' => 'institution']);

        $nationalExams = $this->assessmentReportRepository
            ->getPublishedNationalExamOptions()
            ->map(fn ($exam) => ['id' => 'national_' . $exam->id, 'title' => $exam->title, 'scope' => 'national']);

        $exams = collect();
        if ($orgType === 'institution') {
            $exams = $institutionExams;
        } elseif (in_array($orgType, ['national', 'inservice'])) {
            $exams = $nationalExams;
        } else {
            $exams = $institutionExams->concat($nationalExams);
        }

        return [
            'activeSessions' => $activeSessions,
            'filters' => $request->only(['exam', 'organization', 'activity_status']),
            'organizations' => $organizations,
            'exams' => $exams->sortBy('title')->values(),
            'isSystemAdmin' => $canViewAllOrganizations,
            'lastUpdate' => now()->format('h:i:s A'),
        ];
    }

    private function mapLiveAttempt(object $attempt, string $type): array
    {
        $examCategory = $type === 'national'
            ? ($attempt->assessment->category ?? null)
            : ($attempt->assessment->exam_category ?? null);

        return [
            'id' => $attempt->id,
            'type' => $type,
            'user_id' => $attempt->user_id,
            'resident_name' => $attempt->user->name,
            'resident_email' => $attempt->user->email,
            'exam_title' => $attempt->assessment->title,
            'exam_category' => $examCategory,
            'organization_name' => $attempt->organization->name,
            'started_at_sort' => $attempt->started_at,
            'started_at_label' => $attempt->started_at?->format('M d, Y h:i A'),
            'time_elapsed' => $attempt->started_at?->diffInMinutes(now()) . ' mins',
            'last_activity_at' => $attempt->last_activity_at,
            'ip_address' => $attempt->ip_address,
            'browser' => $attempt->browser_metadata['browser'] ?? 'Unknown',
            'device' => $attempt->browser_metadata['device'] ?? 'Unknown',
            'connection_type' => $attempt->connection_type,
            'speed' => $attempt->connection_speed ? round($attempt->connection_speed, 1) . ' Mbps' : 'N/A',
            'total_idle_time' => $attempt->total_idle_time,
            'idle_periods_count' => $attempt->idle_periods_count,
            'active_session_id' => $attempt->active_session_id,
        ];
    }

    private function resolveReportOrganizationContext(Request $request): object|null
    {
        $user = $request->user();

        if (
            $user
            && $user->hasPermissionTo('view-all-assessment-reports')
            && $request->query('org') === self::ALL_ORGANIZATIONS_SLUG
        ) {
            return (object) [
                'id' => 0,
                'name' => 'All Organizations',
                'slug' => self::ALL_ORGANIZATIONS_SLUG,
                'type' => 'all',
            ];
        }

        return $user?->currentOrganization;
    }

    private function parseExamFilter(?string $examFilter): array
    {
        $isInstitutionExam = false;
        $isNationalExam = false;
        $examId = null;
        $examType = null;

        if (! $examFilter) {
            return [$examId, $examType, $isInstitutionExam, $isNationalExam];
        }

        if (str_starts_with($examFilter, 'institution_')) {
            $examId = (int) str_replace('institution_', '', $examFilter);
            $examType = 'institution';
            $isInstitutionExam = $this->assessmentReportRepository->institutionExamExists($examId);

            return [$examId, $examType, $isInstitutionExam, $isNationalExam];
        }

        if (str_starts_with($examFilter, 'national_')) {
            $examId = (int) str_replace('national_', '', $examFilter);
            $examType = 'national';
            $isNationalExam = $this->assessmentReportRepository->nationalExamExists($examId);

            return [$examId, $examType, $isInstitutionExam, $isNationalExam];
        }

        $examId = (int) $examFilter;
        $isInstitutionExam = $this->assessmentReportRepository->institutionExamExists($examId);

        if ($isInstitutionExam) {
            $examType = 'institution';

            return [$examId, $examType, $isInstitutionExam, $isNationalExam];
        }

        $isNationalExam = $this->assessmentReportRepository->nationalExamExists($examId);
        if ($isNationalExam) {
            $examType = 'national';
        }

        return [$examId, $examType, $isInstitutionExam, $isNationalExam];
    }

    private function extractBrowserName(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Unknown';
        }

        if (
            str_contains($userAgent, 'Edg/') ||
            str_contains($userAgent, 'Edge/') ||
            str_contains($userAgent, 'EdgA/') ||
            str_contains($userAgent, 'EdgiOS/')
        ) {
            return 'Edge';
        }

        if (str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera/')) {
            return 'Opera';
        }

        if (str_contains($userAgent, 'Vivaldi/')) {
            return 'Vivaldi';
        }

        if (str_contains($userAgent, 'Arc/')) {
            return 'Arc';
        }

        if (str_contains($userAgent, 'SamsungBrowser/')) {
            return 'Samsung Internet';
        }

        if (str_contains($userAgent, 'UCBrowser/')) {
            return 'UC Browser';
        }

        if (str_contains($userAgent, 'DuckDuckGo/')) {
            return 'DuckDuckGo';
        }

        if (str_contains($userAgent, 'YaBrowser/')) {
            return 'Yandex';
        }

        if (str_contains($userAgent, 'Firefox')) {
            return 'Firefox';
        }

        if (str_contains($userAgent, 'Chrome')) {
            return 'Chrome';
        }

        if (str_contains($userAgent, 'Safari')) {
            return 'Safari';
        }

        if (str_contains($userAgent, 'MSIE') || str_contains($userAgent, 'Trident/')) {
            return 'Internet Explorer';
        }

        if (str_contains($userAgent, 'Chromium/')) {
            return 'Chromium';
        }

        return 'Unknown';
    }
}
