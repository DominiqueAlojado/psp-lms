<?php

namespace App\Http\Controllers;

use App\Models\ExamSessionChange;
use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionAttempt;
use App\Models\National\NationalAssessment;
use App\Models\National\NationalAttempt;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssessmentReportController extends Controller
{
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
        $institutionQuery = InstitutionAttempt::query()
            ->with([
                'user:id,name,email',
                'assessment:id,title,total_points,passing_score,exam_category',
                'organization:id,name',
            ])
            ->where('status', 'completed');

        // Users with system-wide permissions see all organizations, others see only their org
        if (! $canViewAllOrganizations) {
            $institutionQuery->where('organization_id', $organizationId);
        }

        // Filter by search (resident name or email)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $institutionQuery->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by exam (only if it's an institution exam)
        $isInstitutionExam = false;
        if ($request->filled('exam')) {
            $examFilter = $request->input('exam');
            // Check if it's prefixed with 'institution_' or 'national_'
            if (str_starts_with($examFilter, 'institution_')) {
                $examId = (int) str_replace('institution_', '', $examFilter);
                $isInstitutionExam = InstitutionAssessment::where('id', $examId)->exists();
                if ($isInstitutionExam) {
                    $institutionQuery->where('assessment_id', $examId);
                }
            } elseif (str_starts_with($examFilter, 'national_')) {
                // This will be handled in the national exam section
            } else {
                // Legacy support: try to determine by checking both tables
                $examId = (int) $examFilter;
                $isInstitutionExam = InstitutionAssessment::where('id', $examId)->exists();
                if ($isInstitutionExam) {
                    $institutionQuery->where('assessment_id', $examId);
                }
            }
        }

        // Filter by institution (for users with system-wide permissions)
        if ($request->filled('organization') && $canViewAllOrganizations) {
            $institutionQuery->where('organization_id', $request->input('organization'));
        }

        // Filter by year level
        if ($request->filled('year_level')) {
            $institutionQuery->where('year_level', $request->input('year_level'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'passed') {
                $institutionQuery->whereRaw('score >= (SELECT passing_score FROM institution_assessments WHERE id = assessment_id)');
            } elseif ($status === 'failed') {
                $institutionQuery->whereRaw('score < (SELECT passing_score FROM institution_assessments WHERE id = assessment_id)');
            }
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $institutionQuery->whereDate('submitted_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $institutionQuery->whereDate('submitted_at', '<=', $request->input('date_to'));
        }

        // Only get institution attempts if:
        // 1. No exam filter OR it's an institution exam
        // 2. AND (organization is institution type OR system admin viewing all orgs)
        $institutionAttempts = collect();
        $shouldShowInstitutionAttempts = (! $request->filled('exam') || $isInstitutionExam) 
            && ($orgType === 'institution' || ($canViewAllOrganizations && ! $orgType));
        
        if ($shouldShowInstitutionAttempts) {
            $institutionAttempts = $institutionQuery
                ->get()
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

        // Get national attempts (in-service exams)
        $nationalQuery = NationalAttempt::query()
            ->with([
                'user:id,name,email',
                'assessment:id,title,total_points,passing_score,category',
                'organization:id,name',
            ])
            ->whereIn('status', ['completed', 'graded']);

        // Users with system-wide permissions see all organizations, others see only their org
        if (! $canViewAllOrganizations) {
            $nationalQuery->where('organization_id', $organizationId);
        }

        // Filter by search (resident name or email)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $nationalQuery->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by exam (only if it's a national exam)
        $isNationalExam = false;
        if ($request->filled('exam')) {
            $examFilter = $request->input('exam');
            // Check if it's prefixed with 'national_'
            if (str_starts_with($examFilter, 'national_')) {
                $examId = (int) str_replace('national_', '', $examFilter);
                $isNationalExam = NationalAssessment::where('id', $examId)->exists();
                if ($isNationalExam) {
                    $nationalQuery->where('assessment_id', $examId);
                }
            } elseif (! str_starts_with($examFilter, 'institution_')) {
                // Legacy support: check if it's a national exam (and not an institution exam)
                if (! $isInstitutionExam) {
                    $examId = (int) $examFilter;
                    $isNationalExam = NationalAssessment::where('id', $examId)->exists();
                    if ($isNationalExam) {
                        $nationalQuery->where('assessment_id', $examId);
                    }
                }
            }
        }

        // Filter by institution (for users with system-wide permissions)
        if ($request->filled('organization') && $canViewAllOrganizations) {
            $nationalQuery->where('organization_id', $request->input('organization'));
        }

        // Filter by year level
        if ($request->filled('year_level')) {
            $nationalQuery->where('year_level', $request->input('year_level'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'passed') {
                $nationalQuery->whereRaw('score >= (SELECT passing_score FROM national_assessments WHERE id = assessment_id)');
            } elseif ($status === 'failed') {
                $nationalQuery->whereRaw('score < (SELECT passing_score FROM national_assessments WHERE id = assessment_id)');
            }
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $nationalQuery->whereDate('submitted_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $nationalQuery->whereDate('submitted_at', '<=', $request->input('date_to'));
        }

        // Only get national attempts if:
        // 1. No exam filter OR it's a national exam
        // 2. AND (organization is national/inservice type OR system admin viewing all orgs)
        $nationalAttempts = collect();
        $shouldShowNationalAttempts = (! $request->filled('exam') || $isNationalExam) 
            && (($orgType && in_array($orgType, ['national', 'inservice'])) || ($canViewAllOrganizations && ! $orgType));
        
        if ($shouldShowNationalAttempts) {
            $nationalAttempts = $nationalQuery
                ->get()
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
            ? Organization::select('id', 'name')->orderBy('name')->get()
            : collect();

        // Get current organization to determine exam type filtering
        $currentOrg = $user->currentOrganization;
        $orgType = $currentOrg?->type;

        // Get both institution and national exams
        $institutionExams = InstitutionAssessment::query()
            ->when(! $canViewAllOrganizations, function ($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })
            ->where('is_published', true)
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn($exam) => ['id' => 'institution_' . $exam->id, 'title' => $exam->title, 'type' => 'institution', 'original_id' => $exam->id]);

        $nationalExams = NationalAssessment::query()
            ->where('is_published', true)
            ->orderBy('title')
            ->get(['id', 'title'])
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

        $query = InstitutionAttempt::query()
            ->with([
                'user:id,name,email',
                'assessment:id,title,exam_category',
                'organization:id,name',
            ])
            ->where('status', 'in_progress');

        // Users with system-wide permissions see all, others see only their org
        if (! $canViewAllOrganizations) {
            $query->where('organization_id', $organizationId);
        }

        // Filter by exam
        if ($request->filled('exam')) {
            $query->where('assessment_id', $request->input('exam'));
        }

        // Filter by institution
        if ($request->filled('organization') && $canViewAllOrganizations) {
            $query->where('organization_id', $request->input('organization'));
        }

        // Filter by activity status
        if ($request->filled('activity_status')) {
            $status = $request->input('activity_status');
            if ($status === 'idle') {
                // No activity in last 2 minutes
                $query->where('last_activity_at', '<', now()->subMinutes(2));
            } elseif ($status === 'suspicious') {
                // Has IP or browser changes
                $query->where(function ($q) {
                    $q->where('ip_changes_count', '>', 0)
                        ->orWhere('browser_changes_count', '>', 0);
                });
            } elseif ($status === 'active') {
                // Active in last 2 minutes
                $query->where('last_activity_at', '>=', now()->subMinutes(2));
            }
        }

        $activeSessions = $query
            ->orderBy('started_at', 'desc')
            ->get()
            ->map(function ($attempt) {
                // Get browser change details (deduplicated)
                $browserChanges = ExamSessionChange::where('attempt_type', 'institution')
                    ->where('attempt_id', $attempt->id)
                    ->whereIn('change_type', ['browser', 'both'])
                    ->orderBy('detected_at', 'asc')
                    ->get()
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
                $ipChanges = ExamSessionChange::where('attempt_type', 'institution')
                    ->where('attempt_id', $attempt->id)
                    ->whereIn('change_type', ['ip_address', 'both'])
                    ->orderBy('detected_at', 'asc')
                    ->get()
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
                $idlePeriods = \App\Models\ExamIdlePeriod::where('attempt_type', 'institution')
                    ->where('attempt_id', $attempt->id)
                    ->orderBy('started_at', 'asc')
                    ->get()
                    ->map(fn($period) => [
                        'started_at' => $period->started_at->format('M d, h:i A'),
                        'ended_at' => $period->ended_at->format('M d, h:i A'),
                        'duration' => gmdate('H:i:s', $period->duration_seconds),
                        'duration_minutes' => round($period->duration_seconds / 60, 1),
                    ]);

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
                    'is_suspicious' => $ipChanges->count() > 0 || $browserChanges->count() > 0,
                ];
            });

        // Filter options
        $organizations = $canViewAllOrganizations
            ? Organization::select('id', 'name')->orderBy('name')->get()
            : collect();

        $exams = InstitutionAssessment::query()
            ->when(! $canViewAllOrganizations, function ($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })
            ->where('is_published', true)
            ->orderBy('title')
            ->get(['id', 'title']);

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
