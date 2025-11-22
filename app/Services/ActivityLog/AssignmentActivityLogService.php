<?php

namespace App\Services\ActivityLog;

use App\Models\Assignment;
use Spatie\Activitylog\Models\Activity as ActivityLog;

class AssignmentActivityLogService
{
    /**
     * Log assignment creation.
     */
    public function logAssignmentCreated(Assignment $assignment): void
    {
        activity()
            ->performedOn($assignment)
            ->causedBy(auth()->user() ?? null)
            ->useLog('assignments')
            ->withProperties([
                'attributes' => [
                    'title' => $assignment->title,
                    'description' => substr(strip_tags($assignment->description ?? ''), 0, 100),
                    'assignment_type' => $assignment->assignment_type,
                    'target_year_levels' => ! empty($assignment->target_year_levels) ? implode(', ', $assignment->target_year_levels) : 'None',
                    'max_score' => $assignment->max_score,
                    'due_date' => $assignment->due_date?->format('Y-m-d H:i'),
                    'allow_late_submission' => $assignment->allow_late_submission,
                    'late_submission_until' => $assignment->late_submission_until?->format('Y-m-d H:i'),
                    'late_penalty_percent' => $assignment->late_penalty_percent,
                    'allow_resubmission' => $assignment->allow_resubmission,
                    'max_submissions' => $assignment->max_submissions,
                    'allowed_file_types' => ! empty($assignment->allowed_file_types) ? implode(', ', $assignment->allowed_file_types) : 'None',
                    'max_file_size_mb' => $assignment->max_file_size_mb,
                    'max_files' => $assignment->max_files,
                    'is_published' => $assignment->is_published,
                ],
            ])
            ->log('Assignment created');
    }

    /**
     * Log assignment update.
     */
    public function logAssignmentUpdated(
        Assignment $assignment,
        array $attributes,
        array $oldValues
    ): void {
        activity()
            ->performedOn($assignment)
            ->causedBy(auth()->user() ?? null)
            ->useLog('assignments')
            ->withProperties([
                'attributes' => $attributes,
                'old' => $oldValues,
            ])
            ->log('Assignment updated');
    }

    /**
     * Log assignment deletion.
     */
    public function logAssignmentDeleted(Assignment $assignment): void
    {
        activity()
            ->performedOn($assignment)
            ->causedBy(auth()->user() ?? null)
            ->useLog('assignments')
            ->withProperties([
                'attributes' => [
                    'title' => $assignment->title,
                ],
            ])
            ->log('Assignment deleted');
    }

    /**
     * Build update log data by comparing old and new values.
     */
    public function buildUpdateLogData(
        Assignment $assignment,
        array $validated,
        array $oldValues = []
    ): array {
        $attributes = [];
        $old = [];

        // Title
        $oldTitle = $oldValues['title'] ?? $assignment->title;
        if (isset($validated['title']) && $validated['title'] !== $oldTitle) {
            $attributes['title'] = $validated['title'];
            $old['title'] = $oldTitle;
        }

        // Description
        $oldDescription = $oldValues['description'] ?? $assignment->description ?? '';
        $newDescription = $validated['description'] ?? $oldDescription;
        if ($newDescription !== $oldDescription) {
            $attributes['description'] = substr(strip_tags($newDescription), 0, 100);
            $old['description'] = substr(strip_tags($oldDescription), 0, 100);
        }

        // Instructions
        $oldInstructions = $oldValues['instructions'] ?? $assignment->instructions ?? '';
        $newInstructions = $validated['instructions'] ?? $oldInstructions;
        if ($newInstructions !== $oldInstructions) {
            $attributes['instructions'] = substr(strip_tags($newInstructions), 0, 100);
            $old['instructions'] = substr(strip_tags($oldInstructions), 0, 100);
        }

        // Assignment Type
        $oldAssignmentType = $oldValues['assignment_type'] ?? $assignment->assignment_type;
        if (isset($validated['assignment_type']) && $validated['assignment_type'] !== $oldAssignmentType) {
            $attributes['assignment_type'] = $validated['assignment_type'];
            $old['assignment_type'] = $oldAssignmentType;
        }

        // Target Year Levels
        $oldYearLevels = $oldValues['target_year_levels'] ?? $assignment->target_year_levels ?? [];
        $newYearLevels = $validated['target_year_levels'] ?? $oldYearLevels;
        $oldYearLevelsArray = is_array($oldYearLevels) ? $oldYearLevels : [];
        $newYearLevelsArray = is_array($newYearLevels) ? $newYearLevels : [];

        $oldSorted = collect($oldYearLevelsArray)->sort()->values()->toArray();
        $newSorted = collect($newYearLevelsArray)->sort()->values()->toArray();

        if (json_encode($oldSorted) !== json_encode($newSorted)) {
            $attributes['target_year_levels'] = ! empty($newYearLevelsArray) ? implode(', ', $newYearLevelsArray) : 'None';
            $old['target_year_levels'] = ! empty($oldYearLevelsArray) ? implode(', ', $oldYearLevelsArray) : 'None';
        }

        // Max Score
        $oldMaxScore = $oldValues['max_score'] ?? $assignment->max_score;
        if (isset($validated['max_score']) && (int) $validated['max_score'] !== (int) $oldMaxScore) {
            $attributes['max_score'] = (int) $validated['max_score'];
            $old['max_score'] = (int) $oldMaxScore;
        }

        // Due Date - normalize format for comparison
        $oldDueDate = $oldValues['due_date'] ?? $assignment->due_date?->format('Y-m-d\TH:i');
        $newDueDate = isset($validated['due_date']) ? $validated['due_date'] : $oldDueDate;
        if ($newDueDate && str_contains($newDueDate, ' ')) {
            $newDueDate = str_replace(' ', 'T', $newDueDate);
        }
        if ($newDueDate !== $oldDueDate) {
            $attributes['due_date'] = $newDueDate;
            $old['due_date'] = $oldDueDate;
        }

        // Allow Late Submission - normalize boolean comparison
        $oldAllowLateSubmission = (bool) ($oldValues['allow_late_submission'] ?? $assignment->allow_late_submission);
        $newAllowLateSubmission = isset($validated['allow_late_submission']) ? (bool) $validated['allow_late_submission'] : $oldAllowLateSubmission;
        if ($newAllowLateSubmission !== $oldAllowLateSubmission) {
            $attributes['allow_late_submission'] = $newAllowLateSubmission;
            $old['allow_late_submission'] = $oldAllowLateSubmission;
        }

        // Late Submission Until - normalize format for comparison
        $oldLateSubmissionUntil = $oldValues['late_submission_until'] ?? $assignment->late_submission_until?->format('Y-m-d\TH:i');
        $newLateSubmissionUntil = isset($validated['late_submission_until']) ? $validated['late_submission_until'] : $oldLateSubmissionUntil;
        if ($newLateSubmissionUntil && str_contains($newLateSubmissionUntil, ' ')) {
            $newLateSubmissionUntil = str_replace(' ', 'T', $newLateSubmissionUntil);
        }
        $oldLateSubmissionUntilNormalized = $oldLateSubmissionUntil ?: null;
        $newLateSubmissionUntilNormalized = $newLateSubmissionUntil ?: null;
        if ($newLateSubmissionUntilNormalized !== $oldLateSubmissionUntilNormalized) {
            $attributes['late_submission_until'] = $newLateSubmissionUntilNormalized ?? 'None';
            $old['late_submission_until'] = $oldLateSubmissionUntilNormalized ?? 'None';
        }

        // Late Penalty Percent
        $oldLatePenaltyPercent = $oldValues['late_penalty_percent'] ?? $assignment->late_penalty_percent;
        if (isset($validated['late_penalty_percent']) && (int) $validated['late_penalty_percent'] !== (int) $oldLatePenaltyPercent) {
            $attributes['late_penalty_percent'] = (int) $validated['late_penalty_percent'];
            $old['late_penalty_percent'] = (int) $oldLatePenaltyPercent;
        }

        // Allow Resubmission - normalize boolean comparison
        $oldAllowResubmission = (bool) ($oldValues['allow_resubmission'] ?? $assignment->allow_resubmission);
        $newAllowResubmission = isset($validated['allow_resubmission']) ? (bool) $validated['allow_resubmission'] : $oldAllowResubmission;
        if ($newAllowResubmission !== $oldAllowResubmission) {
            $attributes['allow_resubmission'] = $newAllowResubmission;
            $old['allow_resubmission'] = $oldAllowResubmission;
        }

        // Max Submissions
        $oldMaxSubmissions = $oldValues['max_submissions'] ?? $assignment->max_submissions;
        if (isset($validated['max_submissions']) && (int) $validated['max_submissions'] !== (int) $oldMaxSubmissions) {
            $attributes['max_submissions'] = (int) $validated['max_submissions'];
            $old['max_submissions'] = (int) $oldMaxSubmissions;
        }

        // Allowed File Types
        $oldAllowedFileTypes = $oldValues['allowed_file_types'] ?? $assignment->allowed_file_types ?? [];
        $newAllowedFileTypes = $validated['allowed_file_types'] ?? $oldAllowedFileTypes;
        $oldAllowedFileTypesArray = is_array($oldAllowedFileTypes) ? $oldAllowedFileTypes : [];
        $newAllowedFileTypesArray = is_array($newAllowedFileTypes) ? $newAllowedFileTypes : [];

        $oldSorted = collect($oldAllowedFileTypesArray)->sort()->values()->toArray();
        $newSorted = collect($newAllowedFileTypesArray)->sort()->values()->toArray();

        if (json_encode($oldSorted) !== json_encode($newSorted)) {
            $attributes['allowed_file_types'] = ! empty($newAllowedFileTypesArray) ? implode(', ', $newAllowedFileTypesArray) : 'None';
            $old['allowed_file_types'] = ! empty($oldAllowedFileTypesArray) ? implode(', ', $oldAllowedFileTypesArray) : 'None';
        }

        // Max File Size MB
        $oldMaxFileSizeMb = $oldValues['max_file_size_mb'] ?? $assignment->max_file_size_mb;
        if (isset($validated['max_file_size_mb']) && (int) $validated['max_file_size_mb'] !== (int) $oldMaxFileSizeMb) {
            $attributes['max_file_size_mb'] = (int) $validated['max_file_size_mb'];
            $old['max_file_size_mb'] = (int) $oldMaxFileSizeMb;
        }

        // Max Files
        $oldMaxFiles = $oldValues['max_files'] ?? $assignment->max_files;
        if (isset($validated['max_files']) && (int) $validated['max_files'] !== (int) $oldMaxFiles) {
            $attributes['max_files'] = (int) $validated['max_files'];
            $old['max_files'] = (int) $oldMaxFiles;
        }

        // Is Published - normalize boolean comparison
        $oldIsPublished = (bool) ($oldValues['is_published'] ?? $assignment->is_published);
        $newIsPublished = isset($validated['is_published']) ? (bool) $validated['is_published'] : $oldIsPublished;
        if ($newIsPublished !== $oldIsPublished) {
            $attributes['is_published'] = $newIsPublished;
            $old['is_published'] = $oldIsPublished;
        }

        return [
            'attributes' => $attributes,
            'old' => $old,
        ];
    }

    /**
     * Get activity logs for an assignment.
     */
    public function getLogs(Assignment $assignment, int $limit = 100): array
    {
        $logs = ActivityLog::query()
            ->where('subject_type', Assignment::class)
            ->where('subject_id', $assignment->id)
            ->where('log_name', 'assignments')
            ->with(['subject', 'causer'])
            ->latest()
            ->limit($limit)
            ->get();

        return $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'description' => $log->description,
                'log_name' => $log->log_name,
                'causer' => $log->causer ? [
                    'id' => $log->causer->id,
                    'name' => $log->causer->name,
                    'email' => $log->causer->email,
                ] : null,
                'properties' => $log->properties->toArray(),
                'created_at' => $log->created_at->toIso8601String(),
            ];
        })->toArray();
    }
}
