<?php

namespace App\Repositories\Eloquent;

use App\Models\Event;
use App\Models\MeetingAttendance;
use App\Repositories\Contracts\MeetingAttendanceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class MeetingAttendanceRepository implements MeetingAttendanceRepositoryInterface
{
    public function paginateForEvent(Event $event, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return MeetingAttendance::query()
            ->where('event_id', $event->id)
            ->with(['user.resident', 'organization:id,name', 'eventRegistration'])
            ->when($filters['attendance_type'] ?? null, function (Builder $query, string $attendanceType) {
                $query->whereJsonContains('metadata->attendance_type', $attendanceType);
            })
            ->when($filters['status'] ?? null, function (Builder $query, string $status) {
                $query->where('status', $status);
            })
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->whereHas('user', function (Builder $userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('joined_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getStatsForEvent(Event $event): array
    {
        return [
            'total_attendees' => MeetingAttendance::where('event_id', $event->id)->count(),
            'active_now' => MeetingAttendance::where('event_id', $event->id)
                ->whereNull('left_at')
                ->where('status', '!=', 'left')
                ->count(),
            'total_duration_minutes' => MeetingAttendance::where('event_id', $event->id)
                ->whereNotNull('duration_seconds')
                ->sum('duration_seconds') / 60,
            'average_duration_minutes' => MeetingAttendance::where('event_id', $event->id)
                ->whereNotNull('duration_seconds')
                ->avg('duration_seconds') / 60,
        ];
    }

    public function findActiveForUserAndEvent(int $userId, int $eventId): ?MeetingAttendance
    {
        return MeetingAttendance::where('event_id', $eventId)
            ->where('user_id', $userId)
            ->whereNull('left_at')
            ->latest('joined_at')
            ->first();
    }

    public function create(array $attributes): MeetingAttendance
    {
        return MeetingAttendance::create($attributes);
    }

    public function update(MeetingAttendance $attendance, array $attributes): bool
    {
        return $attendance->update($attributes);
    }
}
