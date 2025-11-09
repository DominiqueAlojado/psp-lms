<?php

namespace App\Http\Controllers;

use App\Models\ExamSessionChange;
use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionAttempt;
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
        $isSystemAdmin = $user->hasRole(['System Admin', 'BOP']);

        $query = InstitutionAttempt::query()
            ->with([
                'user:id,name,email',
                'assessment:id,title,total_points,passing_score,exam_category',
                'organization:id,name',
            ])
            ->where('status', 'completed');

        // System admins see all organizations, others see only their org
        if (! $isSystemAdmin) {
            $query->where('organization_id', $organizationId);
        }

        // Filter by search (resident name or email)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by exam
        if ($request->filled('exam')) {
            $query->where('assessment_id', $request->input('exam'));
        }

        // Filter by institution (for system admins)
        if ($request->filled('organization') && $isSystemAdmin) {
            $query->where('organization_id', $request->input('organization'));
        }

        // Filter by year level
        if ($request->filled('year_level')) {
            $query->where('year_level', $request->input('year_level'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'passed') {
                $query->whereRaw('score >= (SELECT passing_score FROM institution_assessments WHERE id = assessment_id)');
            } elseif ($status === 'failed') {
                $query->whereRaw('score < (SELECT passing_score FROM institution_assessments WHERE id = assessment_id)');
            }
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('submitted_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('submitted_at', '<=', $request->input('date_to'));
        }

        $attempts = $query
            ->orderBy('submitted_at', 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($attempt) => [
                'id' => $attempt->id,
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
                'submitted_at' => $attempt->submitted_at?->format('M d, Y h:i A'),
                'time_spent' => $attempt->started_at && $attempt->submitted_at
                    ? $attempt->started_at->diffInMinutes($attempt->submitted_at).' mins'
                    : 'N/A',
            ]);

        // Get filter options
        $organizations = $isSystemAdmin
            ? Organization::select('id', 'name')->orderBy('name')->get()
            : collect();

        $exams = InstitutionAssessment::query()
            ->when(! $isSystemAdmin, function ($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })
            ->where('is_published', true)
            ->orderBy('title')
            ->get(['id', 'title']);

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
            'isSystemAdmin' => $isSystemAdmin,
        ]);
    }

    /**
     * Live monitoring of active exam sessions.
     */
    public function liveMonitor(Request $request): Response
    {
        $user = $request->user();
        $organizationId = $user->current_organization_id;
        $isSystemAdmin = $user->hasRole(['System Admin', 'BOP']);

        $query = InstitutionAttempt::query()
            ->with([
                'user:id,name,email',
                'assessment:id,title,exam_category',
                'organization:id,name',
            ])
            ->where('status', 'in_progress');

        // System admins see all, others see only their org
        if (! $isSystemAdmin) {
            $query->where('organization_id', $organizationId);
        }

        // Filter by exam
        if ($request->filled('exam')) {
            $query->where('assessment_id', $request->input('exam'));
        }

        // Filter by institution
        if ($request->filled('organization') && $isSystemAdmin) {
            $query->where('organization_id', $request->input('organization'));
        }

        // Filter by activity status
        if ($request->filled('activity_status')) {
            $status = $request->input('activity_status');
            if ($status === 'idle') {
                // No activity in last 3 minutes
                $query->where('last_activity_at', '<', now()->subMinutes(3));
            } elseif ($status === 'suspicious') {
                // Has IP or browser changes
                $query->where(function ($q) {
                    $q->where('ip_changes_count', '>', 0)
                        ->orWhere('browser_changes_count', '>', 0);
                });
            } elseif ($status === 'active') {
                // Active in last 3 minutes
                $query->where('last_activity_at', '>=', now()->subMinutes(3));
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
                        return $change->previous_user_agent.
                            '|'.$change->new_user_agent.
                            '|'.$change->detected_at->format('Y-m-d H:i');
                    })
                    ->map(fn ($change) => [
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
                        return $change->previous_ip_address.
                            '|'.$change->new_ip_address.
                            '|'.$change->detected_at->format('Y-m-d H:i');
                    })
                    ->map(fn ($change) => [
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
                    ->map(fn ($period) => [
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
                    'time_elapsed' => $attempt->started_at?->diffInMinutes(now()).' mins',
                    'last_activity' => $attempt->last_activity_at
                        ? $attempt->last_activity_at->diffForHumans()
                        : 'No activity yet',
                    'is_idle' => $attempt->last_activity_at && $attempt->last_activity_at < now()->subMinutes(3),
                    'ip_address' => $attempt->ip_address,
                    'browser' => $attempt->browser_metadata['browser'] ?? 'Unknown',
                    'device' => $attempt->browser_metadata['device'] ?? 'Unknown',
                    'connection' => $attempt->connection_type,
                    'speed' => $attempt->connection_speed ? round($attempt->connection_speed, 1).' Mbps' : 'N/A',
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
        $organizations = $isSystemAdmin
            ? Organization::select('id', 'name')->orderBy('name')->get()
            : collect();

        $exams = InstitutionAssessment::query()
            ->when(! $isSystemAdmin, function ($q) use ($organizationId) {
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
            'isSystemAdmin' => $isSystemAdmin,
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
        if (str_contains($userAgent, 'Edg/') ||
            str_contains($userAgent, 'Edge/') ||
            str_contains($userAgent, 'EdgA/') ||
            str_contains($userAgent, 'EdgiOS/')) {
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
