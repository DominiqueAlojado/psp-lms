<?php

namespace App\Http\Controllers;

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
            ->map(fn ($attempt) => [
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
                'is_idle' => $attempt->last_activity_at && $attempt->last_activity_at < now()->subMinutes(2),
                'ip_address' => $attempt->ip_address,
                'browser' => $attempt->browser_metadata['browser'] ?? 'Unknown',
                'device' => $attempt->browser_metadata['device'] ?? 'Unknown',
                'connection' => $attempt->connection_type,
                'speed' => $attempt->connection_speed ? round($attempt->connection_speed, 1).' Mbps' : 'N/A',
                'ip_changes' => $attempt->ip_changes_count,
                'browser_changes' => $attempt->browser_changes_count,
                'idle_time' => gmdate('H:i:s', $attempt->total_idle_time),
                'idle_periods' => $attempt->idle_periods_count,
                'is_suspicious' => $attempt->ip_changes_count > 0 || $attempt->browser_changes_count > 0,
            ]);

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
}
