<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Services\ActivityLog\AssignmentActivityLogService;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AssignmentController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected AssignmentActivityLogService $activityLogService
    ) {}

    /**
     * Display list of assignments for training officers.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $organizationId = $user->currentOrganization?->id;

        if (! $organizationId) {
            abort(403, 'No organization selected.');
        }

        // Assignments are only for institution organizations
        if ($user->currentOrganization?->type === 'national') {
            return Inertia::render('assignments/index', [
                'assignments' => [],
                'isNationalOrg' => true,
            ]);
        }

        $assignments = Assignment::with(['creator', 'submissions'])
            ->where('organization_id', $organizationId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($assignment) => [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'description' => $assignment->description,
                'instructions' => $assignment->instructions,
                'assignment_type' => $assignment->assignment_type,
                'target_year_levels' => $assignment->target_year_levels,
                'max_score' => $assignment->max_score,
                'due_date' => $assignment->due_date?->format('Y-m-d\TH:i'),
                'allow_late_submission' => $assignment->allow_late_submission,
                'late_submission_until' => $assignment->late_submission_until?->format('Y-m-d\TH:i'),
                'late_penalty_percent' => $assignment->late_penalty_percent,
                'allow_resubmission' => $assignment->allow_resubmission,
                'max_submissions' => $assignment->max_submissions,
                'allowed_file_types' => $assignment->allowed_file_types,
                'max_file_size_mb' => $assignment->max_file_size_mb,
                'max_files' => $assignment->max_files,
                'is_published' => $assignment->is_published,
                'is_overdue' => $assignment->isOverdue(),
                'submissions_count' => $assignment->submissions()->whereIn('status', ['submitted', 'graded'])->count(),
                'graded_count' => $assignment->submissions()->where('status', 'graded')->count(),
                'created_by' => $assignment->creator->name,
                'created_at' => $assignment->created_at->format('Y-m-d'),
            ]);

        return Inertia::render('assignments/index', [
            'assignments' => $assignments,
        ]);
    }

    /**
     * Show assignment creation form.
     */
    public function create(): Response
    {
        return Inertia::render('assignments/create');
    }

    /**
     * Show assignment edit form.
     */
    public function edit(Request $request, Assignment $assignment): Response
    {
        $user = $request->user();

        // Verify assignment belongs to user's organization
        if ($assignment->organization_id !== $user->currentOrganization?->id) {
            abort(403, 'You do not have access to this assignment.');
        }

        return Inertia::render('assignments/edit', [
            'assignment' => [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'description' => $assignment->description,
                'instructions' => $assignment->instructions,
                'assignment_type' => $assignment->assignment_type,
                'target_year_levels' => $assignment->target_year_levels,
                'max_score' => $assignment->max_score,
                'cme_credits' => $assignment->cme_credits,
                'credit_type' => $assignment->credit_type,
                'due_date' => $assignment->due_date?->format('Y-m-d\TH:i'),
                'allow_late_submission' => $assignment->allow_late_submission,
                'late_submission_until' => $assignment->late_submission_until?->format('Y-m-d\TH:i'),
                'late_penalty_percent' => $assignment->late_penalty_percent,
                'allow_resubmission' => $assignment->allow_resubmission,
                'max_submissions' => $assignment->max_submissions,
                'allowed_file_types' => $assignment->allowed_file_types,
                'max_file_size_mb' => $assignment->max_file_size_mb,
                'max_files' => $assignment->max_files,
                'is_published' => $assignment->is_published,
            ],
        ]);
    }

    /**
     * Store a new assignment.
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        $organizationId = $user->currentOrganization?->id;

        if (! $organizationId) {
            abort(403, 'No organization selected.');
        }

        // Assignments are only for institution organizations, not national
        if ($user->currentOrganization?->type === 'national') {
            return back()->withErrors(['error' => 'Assignments cannot be created in national organizations. Please switch to an institution.']);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'instructions' => ['required', 'string'],
            'assignment_type' => ['required', 'string', 'in:case_report,procedure_log,journal_review,presentation,research_paper,reflection,other'],
            'target_year_levels' => ['required', 'array', 'min:1'],
            'target_year_levels.*' => ['string'],
            'max_score' => ['required', 'integer', 'min:1'],
            'cme_credits' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'credit_type' => ['nullable', 'string', 'in:cme,cpd'],
            'due_date' => ['required', 'date'],
            'allow_late_submission' => ['required', 'boolean'],
            'late_submission_until' => ['nullable', 'date', 'after:due_date', 'required_if:allow_late_submission,true'],
            'late_penalty_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'allow_resubmission' => ['required', 'boolean'],
            'max_submissions' => ['required', 'integer', 'min:1', 'max:10'],
            'allowed_file_types' => ['required', 'array', 'min:1'],
            'allowed_file_types.*' => ['string'],
            'max_file_size_mb' => ['required', 'integer', 'min:1', 'max:100'],
            'max_files' => ['required', 'integer', 'min:1', 'max:20'],
            'is_published' => ['required', 'boolean'],
        ]);

        $validated['organization_id'] = $organizationId;
        $validated['created_by'] = $user->id;

        $assignment = Assignment::create($validated);

        // Log assignment creation
        $this->activityLogService->logAssignmentCreated($assignment);

        return redirect()->route('assignments.index')
            ->with('success', 'Assignment created successfully!');
    }

    /**
     * Get submissions for an assignment (API endpoint).
     */
    public function getSubmissions(Request $request, Assignment $assignment): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        // Verify assignment belongs to user's organization
        if ($assignment->organization_id !== $user->currentOrganization?->id) {
            abort(403, 'You do not have access to this assignment.');
        }

        $submissions = Submission::with(['user', 'files'])
            ->where('assignment_id', $assignment->id)
            ->whereIn('status', ['submitted', 'graded', 'returned'])
            ->orderBy('submitted_at', 'desc')
            ->get()
            ->map(fn ($submission) => [
                'id' => $submission->id,
                'resident_name' => $submission->user->name,
                'year_level' => $submission->year_level,
                'submitted_at' => $submission->submitted_at?->format('Y-m-d H:i:s'),
                'status' => $submission->status,
                'score' => $submission->score,
                'max_score' => $submission->max_score,
                'percentage' => $submission->score ? round($submission->percentage, 2) : null,
                'is_late' => $submission->is_late,
                'late_days' => $submission->late_days,
                'files_count' => $submission->files->count(),
                'has_feedback' => ! empty($submission->grader_feedback),
                'submission_text' => $submission->submission_text,
                'files' => $submission->files->map(fn ($file) => [
                    'id' => $file->id,
                    'original_name' => $file->original_name,
                    'file_path' => $file->file_path,
                    'file_size' => $file->file_size,
                    'mime_type' => $file->mime_type,
                ]),
            ]);

        return response()->json($submissions);
    }

    /**
     * Display assignment details for training officers.
     */
    public function show(Request $request, Assignment $assignment): Response
    {
        $user = $request->user();

        // Verify assignment belongs to user's organization
        if ($assignment->organization_id !== $user->currentOrganization?->id) {
            abort(403, 'You do not have access to this assignment.');
        }

        $submissions = Submission::with(['user', 'files'])
            ->where('assignment_id', $assignment->id)
            ->whereIn('status', ['submitted', 'graded', 'returned'])
            ->orderBy('submitted_at', 'desc')
            ->get()
            ->map(fn ($submission) => [
                'id' => $submission->id,
                'resident_name' => $submission->user->name,
                'year_level' => $submission->year_level,
                'submitted_at' => $submission->submitted_at?->format('Y-m-d H:i:s'),
                'status' => $submission->status,
                'score' => $submission->score,
                'max_score' => $submission->max_score,
                'percentage' => $submission->score ? round($submission->percentage, 2) : null,
                'is_late' => $submission->is_late,
                'late_days' => $submission->late_days,
                'files_count' => $submission->files->count(),
                'has_feedback' => ! empty($submission->grader_feedback),
                'submission_text' => $submission->submission_text,
                'files' => $submission->files->map(fn ($file) => [
                    'id' => $file->id,
                    'original_name' => $file->original_name,
                    'file_path' => $file->file_path,
                    'file_size' => $file->file_size,
                    'mime_type' => $file->mime_type,
                ]),
            ]);

        return Inertia::render('assignments/show', [
            'assignment' => [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'description' => $assignment->description,
                'instructions' => $assignment->instructions,
                'assignment_type' => $assignment->assignment_type,
                'target_year_levels' => $assignment->target_year_levels,
                'max_score' => $assignment->max_score,
                'cme_credits' => $assignment->cme_credits,
                'due_date' => $assignment->due_date?->format('Y-m-d H:i:s'),
                'allow_late_submission' => $assignment->allow_late_submission,
                'late_submission_until' => $assignment->late_submission_until?->format('Y-m-d H:i:s'),
                'late_penalty_percent' => $assignment->late_penalty_percent,
                'allow_resubmission' => $assignment->allow_resubmission,
                'max_submissions' => $assignment->max_submissions,
                'allowed_file_types' => $assignment->allowed_file_types,
                'max_file_size_mb' => $assignment->max_file_size_mb,
                'max_files' => $assignment->max_files,
                'is_published' => $assignment->is_published,
                'is_overdue' => $assignment->isOverdue(),
                'can_still_submit' => $assignment->canStillSubmit(),
                'created_by' => $assignment->creator->name,
                'created_at' => $assignment->created_at->format('Y-m-d'),
            ],
            'submissions' => $submissions,
        ]);
    }

    /**
     * Update an assignment.
     */
    public function update(Request $request, Assignment $assignment): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        // Verify assignment belongs to user's organization
        if ($assignment->organization_id !== $user->currentOrganization?->id) {
            abort(403, 'You do not have access to this assignment.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'instructions' => ['required', 'string'],
            'assignment_type' => ['required', 'string', 'in:case_report,procedure_log,journal_review,presentation,research_paper,reflection,other'],
            'target_year_levels' => ['required', 'array', 'min:1'],
            'target_year_levels.*' => ['string'],
            'max_score' => ['required', 'integer', 'min:1'],
            'cme_credits' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'credit_type' => ['nullable', 'string', 'in:cme,cpd'],
            'due_date' => ['required', 'date'],
            'allow_late_submission' => ['required', 'boolean'],
            'late_submission_until' => ['nullable', 'date', 'after:due_date', 'required_if:allow_late_submission,true'],
            'late_penalty_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'allow_resubmission' => ['required', 'boolean'],
            'max_submissions' => ['required', 'integer', 'min:1', 'max:10'],
            'allowed_file_types' => ['required', 'array', 'min:1'],
            'allowed_file_types.*' => ['string'],
            'max_file_size_mb' => ['required', 'integer', 'min:1', 'max:100'],
            'max_files' => ['required', 'integer', 'min:1', 'max:20'],
            'is_published' => ['required', 'boolean'],
        ]);

        // Capture old values before update
        $oldValues = [
            'title' => $assignment->title,
            'description' => $assignment->description,
            'instructions' => $assignment->instructions,
            'assignment_type' => $assignment->assignment_type,
            'target_year_levels' => $assignment->target_year_levels,
            'max_score' => $assignment->max_score,
            'cme_credits' => $assignment->cme_credits,
            'credit_type' => $assignment->credit_type,
            'due_date' => $assignment->due_date?->format('Y-m-d\TH:i'),
            'allow_late_submission' => $assignment->allow_late_submission,
            'late_submission_until' => $assignment->late_submission_until?->format('Y-m-d\TH:i'),
            'late_penalty_percent' => $assignment->late_penalty_percent,
            'allow_resubmission' => $assignment->allow_resubmission,
            'max_submissions' => $assignment->max_submissions,
            'allowed_file_types' => $assignment->allowed_file_types,
            'max_file_size_mb' => $assignment->max_file_size_mb,
            'max_files' => $assignment->max_files,
            'is_published' => $assignment->is_published,
        ];

        // Update assignment without logging (to avoid duplicate logs)
        $this->withoutActivityLogging(function () use ($assignment, $validated) {
            $assignment->update($validated);
        });

        // Build log data and log changes
        $logData = $this->activityLogService->buildUpdateLogData($assignment, $validated, $oldValues);
        if (! empty($logData['attributes']) || ! empty($logData['old'])) {
            $this->activityLogService->logAssignmentUpdated($assignment, $logData['attributes'], $logData['old']);
        }

        return back()->with('success', 'Assignment updated successfully!');
    }

    /**
     * Delete an assignment.
     */
    public function destroy(Request $request, Assignment $assignment): RedirectResponse
    {
        $user = $request->user();

        // Verify assignment belongs to user's organization
        if ($assignment->organization_id !== $user->currentOrganization?->id) {
            abort(403, 'You do not have access to this assignment.');
        }

        // Log assignment deletion before deleting
        $this->activityLogService->logAssignmentDeleted($assignment);

        $assignment->delete();

        return redirect()->route('assignments.index')
            ->with('success', 'Assignment deleted successfully!');
    }

    /**
     * Get activity logs for an assignment.
     */
    public function logs(Request $request, Assignment $assignment): JsonResponse
    {
        $user = $request->user();

        // Verify assignment belongs to user's organization
        if ($assignment->organization_id !== $user->currentOrganization?->id) {
            abort(403, 'You do not have access to this assignment.');
        }

        $logs = $this->activityLogService->getLogs($assignment);

        return response()->json([
            'logs' => $logs,
        ]);
    }

    /**
     * Display resident's assignment list and submission portal.
     */
    public function myAssignments(Request $request): Response
    {
        $user = $request->user();
        $organizationId = $user->currentOrganization?->id;

        if (! $organizationId) {
            abort(403, 'No organization selected.');
        }

        // Get user's year level
        $resident = $user->resident;
        $yearLevel = $resident?->year_level;

        // Use year level as-is (already in the correct format: Pre-Resident, First Year, etc.)
        $formattedYearLevel = match ($yearLevel) {
            'Pre-Resident' => 'Pre-Resident',
            'First Year' => 'First Year',
            'Second Year' => 'Second Year',
            'Third Year' => 'Third Year',
            'Fourth Year' => 'Fourth Year',
            'Fifth Year' => 'Fourth Year', // Map Fifth Year to Fourth Year
            'Graduate' => 'Graduate',
            default => $yearLevel,
        };

        // Debug logging
        \Log::info('MyAssignments Debug', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'organization_id' => $organizationId,
            'organization_name' => $user->currentOrganization?->name,
            'resident' => $resident ? 'Found' : 'Not Found',
            'year_level_raw' => $yearLevel,
            'year_level_formatted' => $formattedYearLevel,
        ]);

        // Get published assignments for this organization
        $assignments = Assignment::where('organization_id', $organizationId)
            ->where('is_published', true)
            ->where(function ($query) use ($formattedYearLevel) {
                $query->whereNull('target_year_levels')
                    ->orWhereJsonContains('target_year_levels', $formattedYearLevel);
            })
            ->with(['submissions' => function ($query) use ($user) {
                $query->where('user_id', $user->id)->with('files');
            }])
            ->orderBy('due_date', 'asc')
            ->get();

        // Debug: Log found assignments
        \Log::info('Assignments found', [
            'count' => $assignments->count(),
            'assignment_ids' => $assignments->pluck('id')->toArray(),
            'assignment_titles' => $assignments->pluck('title')->toArray(),
        ]);

        $assignments = $assignments->map(function ($assignment) use ($user) {
            $userSubmission = $assignment->submissions->first();

            return [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'description' => $assignment->description,
                'instructions' => $assignment->instructions,
                'assignment_type' => $assignment->assignment_type,
                'due_date' => $assignment->due_date?->format('Y-m-d H:i:s'),
                'max_score' => $assignment->max_score,
                'allowed_file_types' => $assignment->allowed_file_types,
                'max_file_size_mb' => $assignment->max_file_size_mb,
                'max_files' => $assignment->max_files,
                'is_overdue' => $assignment->isOverdue(),
                'can_still_submit' => $assignment->canStillSubmit(),
                'has_submitted' => $assignment->hasUserSubmitted($user),
                'submission_count' => $assignment->getUserSubmissionCount($user),
                'max_submissions' => $assignment->max_submissions,
                'allow_resubmission' => $assignment->allow_resubmission,
                'submission' => $userSubmission ? [
                    'id' => $userSubmission->id,
                    'status' => $userSubmission->status,
                    'score' => $userSubmission->score,
                    'submitted_at' => $userSubmission->submitted_at?->format('Y-m-d H:i:s'),
                    'is_late' => $userSubmission->is_late,
                    'grader_feedback' => $userSubmission->grader_feedback,
                    'submission_text' => $userSubmission->submission_text,
                    'files' => $userSubmission->files->map(fn ($file) => [
                        'id' => $file->id,
                        'original_name' => $file->original_name,
                        'file_path' => $file->file_path,
                        'file_size' => $file->file_size,
                        'mime_type' => $file->mime_type,
                    ]),
                ] : null,
            ];
        });

        return Inertia::render('assignments/my-assignments', [
            'assignments' => $assignments,
        ]);
    }

    /**
     * Store a new submission from resident.
     */
    public function storeSubmission(Request $request, Assignment $assignment): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        $organizationId = $user->currentOrganization?->id;

        // Verify assignment belongs to user's organization
        if ($assignment->organization_id !== $organizationId) {
            abort(403, 'You do not have access to this assignment.');
        }

        // Check if can still submit
        if (! $assignment->canStillSubmit()) {
            return back()->with('error', 'This assignment is no longer accepting submissions.');
        }

        // Check submission count
        $submissionCount = $assignment->getUserSubmissionCount($user);
        if ($submissionCount >= $assignment->max_submissions) {
            return back()->with('error', 'You have reached the maximum number of submissions.');
        }

        $validated = $request->validate([
            'submission_text' => ['nullable', 'string'],
            'files' => ['required', 'array', 'max:'.$assignment->max_files],
            'files.*' => ['file', 'max:'.($assignment->max_file_size_mb * 1024)],
        ]);

        // Get resident's year level
        $resident = $user->resident;
        $yearLevel = $resident?->year_level;

        // Create submission
        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $user->id,
            'organization_id' => $organizationId,
            'year_level' => $yearLevel,
            'submission_text' => $validated['submission_text'] ?? null,
            'submitted_at' => now(),
            'max_score' => $assignment->max_score,
            'status' => 'submitted',
            'submission_number' => $submissionCount + 1,
        ]);

        // Calculate if late
        $submission->calculateLateDays();

        // Handle file uploads
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $fileName = Str::uuid().'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs('submissions', $fileName, 'public');

                SubmissionFile::create([
                    'submission_id' => $submission->id,
                    'file_name' => $fileName,
                    'original_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getClientOriginalExtension(),
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]);
            }
        }

        return redirect()->route('assignments.my-assignments')
            ->with('success', 'Assignment submitted successfully!');
    }

    /**
     * Display submission grading interface for training officer.
     */
    public function grade(Request $request, Submission $submission): Response
    {
        $user = $request->user();

        // Verify submission belongs to user's organization
        if ($submission->organization_id !== $user->currentOrganization?->id) {
            abort(403, 'You do not have access to this submission.');
        }

        $submission->load(['assignment', 'user', 'files']);

        return Inertia::render('assignments/grade', [
            'submission' => [
                'id' => $submission->id,
                'assignment_title' => $submission->assignment->title,
                'assignment_type' => $submission->assignment->assignment_type,
                'resident_name' => $submission->user->name,
                'year_level' => $submission->year_level,
                'submission_text' => $submission->submission_text,
                'submitted_at' => $submission->submitted_at?->format('Y-m-d H:i:s'),
                'is_late' => $submission->is_late,
                'late_days' => $submission->late_days,
                'max_score' => $submission->max_score,
                'score' => $submission->score,
                'status' => $submission->status,
                'grader_feedback' => $submission->grader_feedback,
                'files' => $submission->files->map(fn ($file) => [
                    'id' => $file->id,
                    'original_name' => $file->original_name,
                    'file_type' => $file->file_type,
                    'file_size_formatted' => $file->file_size_formatted,
                    'download_count' => $file->download_count,
                ]),
            ],
        ]);
    }

    /**
     * Save grade and feedback for a submission.
     */
    public function saveGrade(Request $request, Submission $submission): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        // Verify submission belongs to user's organization
        if ($submission->organization_id !== $user->currentOrganization?->id) {
            abort(403, 'You do not have access to this submission.');
        }

        $validated = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:'.$submission->max_score],
            'grader_feedback' => ['nullable', 'string'],
        ]);

        $submission->update([
            'score' => $validated['score'],
            'grader_feedback' => $validated['grader_feedback'],
            'status' => 'graded',
            'graded_by' => $user->id,
            'graded_at' => now(),
        ]);

        // Award CME credits if assignment has credits and submission passes
        $cmeCreditService = app(\App\Services\CmeCreditService::class);
        $cmeCreditService->awardCreditsForAssignment($submission, $user->id);

        return back()->with('success', 'Grade saved successfully!');
    }

    /**
     * Download a submission file.
     */
    public function downloadFile(Request $request, SubmissionFile $file): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = $request->user();

        // Verify file belongs to user's organization
        if ($file->submission->organization_id !== $user->currentOrganization?->id) {
            abort(403, 'You do not have access to this file.');
        }

        // Increment download count
        $file->incrementDownloadCount();

        return Storage::disk('public')->download($file->file_path, $file->original_name);
    }
}
