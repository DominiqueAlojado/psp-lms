<?php

namespace App\Actions\Events;

use App\Models\MeetingAttendance;
use App\Repositories\Contracts\MeetingAttendanceRepositoryInterface;

class UpdateMeetingAttendanceAction
{
    public function __construct(
        private readonly MeetingAttendanceRepositoryInterface $meetingAttendanceRepository,
    ) {}

    public function execute(MeetingAttendance $attendance, array $attributes): bool
    {
        return $this->meetingAttendanceRepository->update($attendance, $attributes);
    }
}
