<?php

namespace App\Repositories\Eloquent;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Repositories\Contracts\EventRegistrationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EventRegistrationRepository implements EventRegistrationRepositoryInterface
{
    public function findUserRegistrationForEvent(Event $event, int $userId): ?EventRegistration
    {
        return $event->registrations()
            ->where('user_id', $userId)
            ->first();
    }

    public function create(array $attributes): EventRegistration
    {
        return EventRegistration::create($attributes);
    }

    public function paginateAttendeesForEvent(Event $event, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $event->registrations()
            ->with(['user.resident', 'organization:id,name'])
            ->when($filters['status'] ?? null, function (Builder $query, string $status) {
                $query->where('registration_status', $status);
            })
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->whereHas('user', function (Builder $userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function update(EventRegistration $registration, array $attributes): bool
    {
        return $registration->update($attributes);
    }

    public function paginateForUser(int $userId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return EventRegistration::query()
            ->where('user_id', $userId)
            ->with(['event.organization:id,name'])
            ->when($filters['status'] ?? null, function (Builder $query, string $status) {
                $query->where('registration_status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }
}
