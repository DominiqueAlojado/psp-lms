<?php

namespace App\Repositories\Eloquent;

use App\Models\Event;
use App\Models\User;
use App\Repositories\Contracts\EventRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EventRepository implements EventRepositoryInterface
{
    public function paginatePublished(int $organizationId, array $filters, int $perPage = 12): LengthAwarePaginator
    {
        return Event::query()
            ->with(['creator:id,name', 'organization:id,name'])
            ->published()
            ->forOrganization($organizationId)
            ->when($filters['category'] ?? null, function (Builder $query, string $category) {
                $query->where('event_category', $category);
            })
            ->when($filters['type'] ?? null, function (Builder $query, string $type) {
                $query->where('event_type', $type);
            })
            ->when(($filters['filter'] ?? null) === 'upcoming', function (Builder $query) {
                $query->upcoming();
            })
            ->when(($filters['filter'] ?? null) === 'past', function (Builder $query) {
                $query->where('end_date', '<', now());
            })
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $nestedQuery) use ($search) {
                    $nestedQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->orderBy('start_date', 'asc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function attachUserRegistrations(LengthAwarePaginator $events, User $user): LengthAwarePaginator
    {
        $events->getCollection()->transform(function (Event $event) use ($user) {
            $event->user_registration = $event->registrations()
                ->where('user_id', $user->id)
                ->first();

            return $event;
        });

        return $events;
    }

    public function loadEventDetails(Event $event): Event
    {
        $event->load(['creator:id,name', 'organization:id,name']);

        return $event;
    }

    public function getRegistrationStats(Event $event): array
    {
        return [
            'total' => $event->registrations()->whereIn('registration_status', ['confirmed', 'approved'])->count(),
            'capacity' => $event->capacity,
            'remaining' => $event->getRemainingCapacity(),
        ];
    }

    public function paginateForManagement(?int $organizationId, bool $canManageAll, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Event::query()
            ->with(['creator:id,name', 'organization:id,name'])
            ->withCount('registrations')
            ->when(! $canManageAll, function (Builder $query) use ($organizationId) {
                $query->where(function (Builder $nestedQuery) use ($organizationId) {
                    $nestedQuery->where(function (Builder $organizationQuery) use ($organizationId) {
                        $organizationQuery->where('scope', 'organization')
                            ->where('organization_id', $organizationId);
                    })->orWhere('scope', 'system');
                });
            })
            ->when($filters['scope'] ?? null, function (Builder $query, string $scope) {
                $query->where('scope', $scope);
            })
            ->when(($filters['status'] ?? null) === 'published', function (Builder $query) {
                $query->where('is_published', true);
            })
            ->when(($filters['status'] ?? null) === 'draft', function (Builder $query) {
                $query->where('is_published', false);
            })
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->orderBy('start_date', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $attributes): Event
    {
        return Event::create($attributes);
    }

    public function update(Event $event, array $attributes): bool
    {
        return $event->update($attributes);
    }

    public function delete(Event $event): bool
    {
        return (bool) $event->delete();
    }

    public function hasConfirmedRegistrations(Event $event): bool
    {
        return $event->registrations()->confirmed()->exists();
    }
}
