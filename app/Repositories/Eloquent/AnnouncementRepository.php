<?php

namespace App\Repositories\Eloquent;

use App\Models\Announcement;
use App\Repositories\Contracts\AnnouncementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AnnouncementRepository implements AnnouncementRepositoryInterface
{
    public function paginateVisibleToOrganization(int $organizationId, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return Announcement::query()
            ->with('creator:id,name', 'organization:id,name')
            ->visibleTo($organizationId)
            ->active()
            ->when($filters['priority'] ?? null, function (Builder $query, string $priority) {
                $query->where('priority', $priority);
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc('priority')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateForManagement(int $organizationId, bool $canCreateSystem, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return Announcement::query()
            ->with('creator:id,name', 'organization:id,name')
            ->when(! $canCreateSystem, function (Builder $query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $nestedQuery) use ($search) {
                    $nestedQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->when($filters['scope'] ?? null, function (Builder $query, string $scope) {
                $query->where('scope', $scope);
            })
            ->when(array_key_exists('is_published', $filters), function (Builder $query) use ($filters) {
                $query->where('is_published', $filters['is_published']);
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $attributes): Announcement
    {
        return Announcement::create($attributes);
    }

    public function update(Announcement $announcement, array $attributes): bool
    {
        return $announcement->update($attributes);
    }

    public function delete(Announcement $announcement): bool
    {
        return (bool) $announcement->delete();
    }

    public function markAsViewedBy(Announcement $announcement, int $userId): void
    {
        $announcement->markAsViewedBy($userId);
    }
}
