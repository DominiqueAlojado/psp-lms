<?php

namespace App\Repositories\Contracts;

use App\Models\Event;
use App\Models\MeetingAttendance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MeetingAttendanceRepositoryInterface
{
    public function paginateForEvent(Event $event, array $filters, int $perPage = 20): LengthAwarePaginator;

    public function getStatsForEvent(Event $event): array;

    public function findActiveForUserAndEvent(int $userId, int $eventId): ?MeetingAttendance;

    public function create(array $attributes): MeetingAttendance;

    public function update(MeetingAttendance $attendance, array $attributes): bool;
}
