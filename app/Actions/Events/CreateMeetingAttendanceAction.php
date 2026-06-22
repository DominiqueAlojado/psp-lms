<?php

namespace App\Actions\Events;

use App\Models\MeetingAttendance;
use App\Repositories\Contracts\MeetingAttendanceRepositoryInterface;

class CreateMeetingAttendanceAction
{
    public function __construct(
        private readonly MeetingAttendanceRepositoryInterface $meetingAttendanceRepository,
    ) {}

    public function execute(array $attributes): MeetingAttendance
    {
        return $this->meetingAttendanceRepository->create($attributes);
    }
}
