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
}
