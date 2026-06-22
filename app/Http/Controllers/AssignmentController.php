<?php

namespace App\Http\Controllers;

use App\Http\Requests\GradeAssignmentSubmissionRequest;
use App\Http\Requests\StoreAssignmentRequest;
use App\Http\Requests\StoreAssignmentSubmissionRequest;
use App\Http\Requests\UpdateAssignmentRequest;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Services\ActivityLog\AssignmentActivityLogService;
use App\Services\AssignmentManagementService;
use App\Services\AssignmentReadService;
use App\Services\AssignmentSubmissionService;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AssignmentController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected AssignmentActivityLogService $activityLogService,
        protected AssignmentReadService $assignmentReadService,
        protected AssignmentManagementService $assignmentManagementService,
        protected AssignmentSubmissionService $assignmentSubmissionService,
    ) {}

    /**
     * Display list of assignments for training officers.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('assignments/index', $this->assignmentReadService->indexPayload($request->user()));
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
        if (! $this->assignmentReadService->canAccessAssignment($request->user(), $assignment)) {
            abort(403, 'You do not have access to this assignment.');
        }

        return Inertia::render('assignments/edit', $this->assignmentReadService->editPayload($assignment));
    }

    /**
     * Store a new assignment.
     */
    public function store(StoreAssignmentRequest $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if (! $this->assignmentManagementService->canCreateFromCurrentOrganization($user)) {
            return back()->withErrors(['error' => 'Assignments cannot be created in national organizations. Please switch to an institution.']);
        }

        $assignment = $this->assignmentManagementService->create($user, $request->validated());

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
        if (! $this->assignmentReadService->canAccessAssignment($request->user(), $assignment)) {
            abort(403, 'You do not have access to this assignment.');
        }

        return response()->json($this->assignmentReadService->submissionsPayload($assignment));
    }

    /**
     * Display assignment details for training officers.
     */
    public function show(Request $request, Assignment $assignment): Response
    {
        if (! $this->assignmentReadService->canAccessAssignment($request->user(), $assignment)) {
            abort(403, 'You do not have access to this assignment.');
        }

        return Inertia::render('assignments/show', $this->assignmentReadService->showPayload($assignment));
    }

    /**
     * Update an assignment.
     */
    public function update(UpdateAssignmentRequest $request, Assignment $assignment): \Illuminate\Http\RedirectResponse
    {
        if (! $this->assignmentReadService->canAccessAssignment($request->user(), $assignment)) {
            abort(403, 'You do not have access to this assignment.');
        }

        $validated = $request->validated();

        // Capture old values before update
        $oldValues = [
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
        ];

        // Update assignment without logging (to avoid duplicate logs)
        $this->withoutActivityLogging(function () use ($assignment, $validated) {
            $this->assignmentManagementService->update($assignment, $validated);
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
        if (! $this->assignmentReadService->canAccessAssignment($request->user(), $assignment)) {
            abort(403, 'You do not have access to this assignment.');
        }

        // Log assignment deletion before deleting
        $this->activityLogService->logAssignmentDeleted($assignment);

        $this->assignmentManagementService->delete($assignment);

        return redirect()->route('assignments.index')
            ->with('success', 'Assignment deleted successfully!');
    }

    /**
     * Get activity logs for an assignment.
     */
    public function logs(Request $request, Assignment $assignment): JsonResponse
    {
        if (! $this->assignmentReadService->canAccessAssignment($request->user(), $assignment)) {
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
        return Inertia::render('assignments/my-assignments', $this->assignmentReadService->myAssignmentsPayload($request->user()));
    }

    /**
     * Store a new submission from resident.
     */
    public function storeSubmission(StoreAssignmentSubmissionRequest $request, Assignment $assignment): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        // Verify assignment belongs to user's organization
        if (! $this->assignmentReadService->canAccessAssignment($user, $assignment)) {
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

        $validated = $request->validated();
        $this->assignmentSubmissionService->createSubmission(
            $user,
            $assignment,
            $validated,
            $request->file('files', [])
        );

        return redirect()->route('assignments.my-assignments')
            ->with('success', 'Assignment submitted successfully!');
    }

    /**
     * Display submission grading interface for training officer.
     */
    public function grade(Request $request, Submission $submission): Response
    {
        if (! $this->assignmentReadService->canAccessSubmission($request->user(), $submission)) {
            abort(403, 'You do not have access to this submission.');
        }

        return Inertia::render('assignments/grade', $this->assignmentReadService->gradePayload($submission));
    }

    /**
     * Save grade and feedback for a submission.
     */
    public function saveGrade(GradeAssignmentSubmissionRequest $request, Submission $submission): \Illuminate\Http\RedirectResponse
    {
        if (! $this->assignmentReadService->canAccessSubmission($request->user(), $submission)) {
            abort(403, 'You do not have access to this submission.');
        }

        $this->assignmentSubmissionService->gradeSubmission($request->user(), $submission, $request->validated());

        return back()->with('success', 'Grade saved successfully!');
    }

    /**
     * Download a submission file.
     */
    public function downloadFile(Request $request, SubmissionFile $file): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (! $this->assignmentReadService->canAccessSubmissionFile($request->user(), $file)) {
            abort(403, 'You do not have access to this file.');
        }

        // Increment download count
        $this->assignmentSubmissionService->incrementDownloadCount($file);

        return Storage::disk('public')->download($file->file_path, $file->original_name);
    }
}
