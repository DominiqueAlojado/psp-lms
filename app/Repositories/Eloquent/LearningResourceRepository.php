<?php

namespace App\Repositories\Eloquent;

use App\Models\LearningResource;
use App\Repositories\Contracts\LearningResourceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LearningResourceRepository implements LearningResourceRepositoryInterface
{
    public function paginatePublishedByOrganization(int $organizationId, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->baseOrganizationQuery($organizationId)
            ->where('is_published', true)
            ->with('uploader:id,name')
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $this->applySearchFilter($query, $search);
            })
            ->when($filters['category'] ?? null, function (Builder $query, string $category) {
                $query->where('category', $category);
            })
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateForManagementByOrganization(int $organizationId, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->baseOrganizationQuery($organizationId)
            ->with('uploader:id,name')
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $this->applySearchFilter($query, $search);
            })
            ->when($filters['category'] ?? null, function (Builder $query, string $category) {
                $query->where('category', $category);
            })
            ->when(array_key_exists('is_published', $filters), function (Builder $query) use ($filters) {
                $query->where('is_published', $filters['is_published']);
            })
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getPublishedCategoriesByOrganization(int $organizationId): Collection
    {
        return $this->baseOrganizationQuery($organizationId)
            ->where('is_published', true)
            ->distinct()
            ->pluck('category')
            ->filter()
            ->sort()
            ->values();
    }

    public function getCategoriesByOrganization(int $organizationId): Collection
    {
        return $this->baseOrganizationQuery($organizationId)
            ->distinct()
            ->pluck('category')
            ->filter()
            ->sort()
            ->values();
    }

    public function create(array $attributes): LearningResource
    {
        return LearningResource::create($attributes);
    }

    public function update(LearningResource $resource, array $attributes): bool
    {
        return $resource->update($attributes);
    }

    public function delete(LearningResource $resource): bool
    {
        return (bool) $resource->delete();
    }

    public function incrementDownloadCount(LearningResource $resource): void
    {
        $resource->incrementDownloadCount();
    }

    private function baseOrganizationQuery(int $organizationId): Builder
    {
        return LearningResource::query()->where('organization_id', $organizationId);
    }

    private function applySearchFilter(Builder $query, string $search): void
    {
        $query->where(function (Builder $nestedQuery) use ($search) {
            $nestedQuery->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }
}
