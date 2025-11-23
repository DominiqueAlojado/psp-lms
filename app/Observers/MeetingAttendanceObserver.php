<?php

namespace App\Observers;

use App\Models\MeetingAttendance;
use App\Services\CmeCreditService;

class MeetingAttendanceObserver
{
    /**
     * Handle the MeetingAttendance "updated" event.
     * Award CME credits when attendance is finalized (left_at is set or duration calculated).
     */
    public function updated(MeetingAttendance $meetingAttendance): void
    {
        // Check if attendance was just finalized (left_at was set or duration was calculated)
        if ($meetingAttendance->wasChanged('left_at') && $meetingAttendance->left_at) {
            $this->awardCreditsIfEligible($meetingAttendance);
        } elseif ($meetingAttendance->wasChanged('duration_seconds') && $meetingAttendance->duration_seconds) {
            // Also check when duration is calculated
            $this->awardCreditsIfEligible($meetingAttendance);
        }
    }

    /**
     * Award credits if user attended for minimum required duration.
     */
    private function awardCreditsIfEligible(MeetingAttendance $attendance): void
    {
        // Check if CME/CPD tracking is enabled system-wide
        if (! config('cme_cpd.enabled', true)) {
            return;
        }

        // Load event if not already loaded
        if (! $attendance->relationLoaded('event')) {
            $attendance->load('event');
        }

        $event = $attendance->event;

        if (! $event || ! $event->cme_credits || $event->cme_credits <= 0) {
            return; // No credits to award
        }

        // Calculate event duration in seconds
        $eventDurationSeconds = $event->start_date->diffInSeconds($event->end_date);

        // Get minimum attendance percentage from config
        $minAttendancePercentage = config('cme_cpd.minimum_attendance_percentage', 0.5);
        $minimumDurationSeconds = $eventDurationSeconds * $minAttendancePercentage;

        // Check if user attended for minimum duration
        if ($attendance->duration_seconds && $attendance->duration_seconds >= $minimumDurationSeconds) {
            $cmeCreditService = app(CmeCreditService::class);
            $cmeCreditService->awardCreditsForEventAttendance($attendance, $attendance->user_id);
        }
    }
}
