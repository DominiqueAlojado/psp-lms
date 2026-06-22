<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use App\Repositories\Contracts\SubmissionRepositoryInterface;
use Illuminate\Support\Collection;

class AssignmentReadService
{
    public function __construct(
        private readonly AssignmentRepositoryInterface $assignmentRepository,
        private readonly SubmissionRepositoryInterface $submissionRepository,
    ) {}

    public function indexPayload(User $user): array
    {
        $organization = $user->currentOrganization;

        if (! $organization) {
            abort(403, 'No organization selected.');
        }

        if ($organization->type === 'national') {
            return [
                'assignments' => [],
                'isNationalOrg' => true,
            ];
        }

        return [
            'assignments' => $this->mapAssignmentsForManagement(
                $this->assignmentRepository->getForOrganization($organization->id)
            ),
            'isNationalOrg' => false,
        ];
    }

    public function editPayload(Assignment $assignment): array
    {
        return [
            'assignment' => $this->mapEditableAssignment($assignment),
        ];
    }

    public function showPayload(Assignment $assignment): array
    {
        return [
            'assignment' => $this->mapDetailedAssignment($assignment),
            'submissions' => $this->mapSubmissions($this->submissionRepository->getForAssignment($assignment)),
        ];
    }

    public function submissionsPayload(Assignment $assignment): Collection
    {
        return $this->mapSubmissions($this->submissionRepository->getForAssignment($assignment));
    }

    public function myAssignmentsPayload(User $user): array
    {
        $organization = $user->currentOrganization;

        if (! $organization) {
            abort(403, 'No organization selected.');
        }

        $resident = $user->resident;
        $yearLevel = $this->normalizeResidentYearLevel($resident?->year_level);

        $assignments = $this->assignmentRepository
            ->getPublishedForResident($organization->id, $yearLevel, $user)
            ->map(function ($assignment) use ($user) {
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
                    'submission' => $userSubmission ? $this->mapResidentSubmission($userSubmission) : null,
                ];
            });

        return [
            'assignments' => $assignments,
        ];
    }

    public function gradePayload(Submission $submission): array
    {
        $submission->load(['assignment', 'user', 'files']);

        return [
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
        ];
    }

    public function canAccessAssignment(User $user, Assignment $assignment): bool
    {
        return $assignment->organization_id === $user->currentOrganization?->id;
    }

    public function canAccessSubmission(User $user, Submission $submission): bool
    {
        return $submission->organization_id === $user->currentOrganization?->id;
    }

    public function canAccessSubmissionFile(User $user, \App\Models\SubmissionFile $file): bool
    {
        return $file->submission->organization_id === $user->currentOrganization?->id;
    }

    private function mapAssignmentsForManagement(Collection $assignments): Collection
    {
        return $assignments->map(fn ($assignment) => [
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
    }

    private function mapEditableAssignment(Assignment $assignment): array
    {
        return [
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
        ];
    }

    private function mapDetailedAssignment(Assignment $assignment): array
    {
        return [
            'id' => $assignment->id,
            'title' => $assignment->title,
            'description' => $assignment->description,
            'instructions' => $assignment->instructions,
            'assignment_type' => $assignment->assignment_type,
            'target_year_levels' => $assignment->target_year_levels,
            'max_score' => $assignment->max_score,
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
        ];
    }

    private function mapSubmissions(Collection $submissions): Collection
    {
        return $submissions->map(fn ($submission) => [
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
    }

    private function mapResidentSubmission(Submission $submission): array
    {
        return [
            'id' => $submission->id,
            'status' => $submission->status,
            'score' => $submission->score,
            'submitted_at' => $submission->submitted_at?->format('Y-m-d H:i:s'),
            'is_late' => $submission->is_late,
            'grader_feedback' => $submission->grader_feedback,
            'submission_text' => $submission->submission_text,
            'files' => $submission->files->map(fn ($file) => [
                'id' => $file->id,
                'original_name' => $file->original_name,
                'file_path' => $file->file_path,
                'file_size' => $file->file_size,
                'mime_type' => $file->mime_type,
            ]),
        ];
    }

    private function normalizeResidentYearLevel(?string $yearLevel): ?string
    {
        return match ($yearLevel) {
            'Pre-Resident' => 'Pre-Resident',
            'First Year' => 'First Year',
            'Second Year' => 'Second Year',
            'Third Year' => 'Third Year',
            'Fourth Year' => 'Fourth Year',
            'Fifth Year' => 'Fourth Year',
            'Graduate' => 'Graduate',
            default => $yearLevel,
        };
    }
}
