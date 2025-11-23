<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\CmeCredit;
use App\Models\Event;
use App\Models\Institution\InstitutionAttempt;
use App\Models\MeetingAttendance;
use App\Models\National\NationalAttempt;
use App\Models\Submission;

class CmeCreditService
{
    /**
     * Check if CME/CPD tracking is enabled system-wide.
     */
    public function isEnabled(): bool
    {
        return config('cme_cpd.enabled', true);
    }

    /**
     * Award CME/CPD credits for event attendance.
     * Credits are awarded when user has attended the event for a minimum duration.
     */
    public function awardCreditsForEventAttendance(MeetingAttendance $attendance, ?int $approvedBy = null): ?CmeCredit
    {
        // Check if CME/CPD tracking is enabled
        if (! $this->isEnabled()) {
            return null;
        }

        $event = $attendance->event;

        // Check if event has CME credits
        if (! $event || ! $event->cme_credits || $event->cme_credits <= 0) {
            return null;
        }

        // Check if user already earned credits for this event
        $existingCredit = CmeCredit::where('user_id', $attendance->user_id)
            ->where('source_type', 'event')
            ->where('source_id', $event->id)
            ->where('status', 'approved')
            ->first();

        if ($existingCredit) {
            return null; // Already awarded
        }

        // Get credit type from event (default to 'cme' if not set)
        $creditType = $event->credit_type ?? 'cme';

        // Award credits
        return CmeCredit::award(
            userId: $attendance->user_id,
            sourceType: 'event',
            sourceId: $event->id,
            credits: (float) $event->cme_credits,
            description: "Attended: {$event->title}",
            organizationId: $attendance->organization_id,
            approvedBy: $approvedBy,
            creditType: $creditType
        );
    }

    /**
     * Award CME credits for completing an institution exam.
     */
    public function awardCreditsForInstitutionExam(InstitutionAttempt $attempt, ?int $approvedBy = null): ?CmeCredit
    {
        // Check if CME/CPD tracking is enabled
        if (! $this->isEnabled()) {
            return null;
        }

        $assessment = $attempt->assessment;

        // Check if assessment has CME credits
        if (! $assessment || ! $assessment->cme_credits || $assessment->cme_credits <= 0) {
            return null;
        }

        // Only award if exam is completed
        if (! in_array($attempt->status, ['completed', 'graded'])) {
            return null;
        }

        // Check if user already earned credits for this attempt
        $existingCredit = CmeCredit::where('user_id', $attempt->user_id)
            ->where('source_type', 'exam_institution')
            ->where('source_id', $attempt->id)
            ->where('status', 'approved')
            ->first();

        if ($existingCredit) {
            return null; // Already awarded
        }

        // Get credit type from assessment (default to 'cme' if not set)
        $creditType = $assessment->credit_type ?? 'cme';

        // Award credits
        return CmeCredit::award(
            userId: $attempt->user_id,
            sourceType: 'exam_institution',
            sourceId: $attempt->id,
            credits: (float) $assessment->cme_credits,
            description: "Completed exam: {$assessment->title}",
            organizationId: $attempt->organization_id,
            approvedBy: $approvedBy,
            creditType: $creditType
        );
    }

    /**
     * Award CME credits for completing a national exam.
     */
    public function awardCreditsForNationalExam(NationalAttempt $attempt, ?int $approvedBy = null): ?CmeCredit
    {
        // Check if CME/CPD tracking is enabled
        if (! $this->isEnabled()) {
            return null;
        }

        $assessment = $attempt->assessment;

        // Check if assessment has CME credits
        if (! $assessment || ! $assessment->cme_credits || $assessment->cme_credits <= 0) {
            return null;
        }

        // Only award if exam is completed
        if (! in_array($attempt->status, ['completed', 'graded'])) {
            return null;
        }

        // Check if user already earned credits for this attempt
        $existingCredit = CmeCredit::where('user_id', $attempt->user_id)
            ->where('source_type', 'exam_national')
            ->where('source_id', $attempt->id)
            ->where('status', 'approved')
            ->first();

        if ($existingCredit) {
            return null; // Already awarded
        }

        // Get credit type from assessment (default to 'cme' if not set)
        $creditType = $assessment->credit_type ?? 'cme';

        // Award credits
        return CmeCredit::award(
            userId: $attempt->user_id,
            sourceType: 'exam_national',
            sourceId: $attempt->id,
            credits: (float) $assessment->cme_credits,
            description: "Completed exam: {$assessment->title}",
            organizationId: $attempt->organization_id,
            approvedBy: $approvedBy,
            creditType: $creditType
        );
    }

    /**
     * Get total CME/CPD credits for a user within a date range.
     */
    public function getTotalCreditsForUser(int $userId, ?string $startDate = null, ?string $endDate = null, ?string $creditType = null): float
    {
        return CmeCredit::getTotalCreditsForUser($userId, $startDate, $endDate, $creditType);
    }

    /**
     * Get total CME credits for a user.
     */
    public function getTotalCmeCredits(int $userId, ?string $startDate = null, ?string $endDate = null): float
    {
        return $this->getTotalCreditsForUser($userId, $startDate, $endDate, 'cme');
    }

    /**
     * Get total CPD credits for a user.
     */
    public function getTotalCpdCredits(int $userId, ?string $startDate = null, ?string $endDate = null): float
    {
        return $this->getTotalCreditsForUser($userId, $startDate, $endDate, 'cpd');
    }

    /**
     * Award CME credits for assignment submission when graded.
     * Credits are awarded when assignment is graded and passes minimum threshold.
     */
    public function awardCreditsForAssignment(Submission $submission, ?int $approvedBy = null): ?CmeCredit
    {
        // Check if CME/CPD tracking is enabled
        if (! $this->isEnabled()) {
            return null;
        }

        $assignment = $submission->assignment;

        // Check if assignment has CME credits
        if (! $assignment || ! $assignment->cme_credits || $assignment->cme_credits <= 0) {
            return null;
        }

        // Only award if submission is graded
        if ($submission->status !== 'graded') {
            return null;
        }

        // Only award if submission passes (60% default passing score)
        if (! $submission->isPassed()) {
            return null;
        }

        // Check if user already earned credits for this assignment
        // Only one credit per assignment (first passing grade)
        $existingCredit = CmeCredit::where('user_id', $submission->user_id)
            ->where('source_type', 'assignment_submission')
            ->where('source_id', $assignment->id)
            ->where('status', 'approved')
            ->first();

        if ($existingCredit) {
            return null; // Already awarded for this assignment
        }

        // Get credit type from assignment (default to 'cme' if not set)
        $creditType = $assignment->credit_type ?? 'cme';

        // Award credits
        return CmeCredit::award(
            userId: $submission->user_id,
            sourceType: 'assignment_submission',
            sourceId: $assignment->id,
            credits: (float) $assignment->cme_credits,
            description: "Completed assignment: {$assignment->title}",
            organizationId: $submission->organization_id,
            approvedBy: $approvedBy ?? $submission->graded_by,
            creditType: $creditType
        );
    }

    /**
     * Get credit history for a user.
     */
    public function getCreditHistory(int $userId, int $perPage = 20)
    {
        return CmeCredit::where('user_id', $userId)
            ->orderBy('earned_at', 'desc')
            ->paginate($perPage);
    }
}
