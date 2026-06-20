<?php

namespace App\Repositories\Eloquent;

use App\Models\Organization;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OrganizationRepository implements OrganizationRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Organization::query()
            ->withCount(['residents', 'users'])
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $nestedQuery) use ($search) {
                    $nestedQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%");
                });
            })
            ->when($filters['type'] ?? null, function (Builder $query, string $type) {
                $query->where('type', $type);
            })
            ->when($filters['status'] ?? null, function (Builder $query, string $status) {
                $query->where('is_active', $status === 'active');
            })
            ->orderBy($filters['sort'] ?? 'name', $filters['direction'] ?? 'asc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getTypeStats(): array
    {
        return Organization::query()
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
    }

    public function getDistinctTypes(): Collection
    {
        return Organization::query()
            ->distinct()
            ->pluck('type')
            ->filter()
            ->values();
    }

    public function create(array $attributes): Organization
    {
        return Organization::create($attributes);
    }

    public function update(Organization $organization, array $attributes): bool
    {
        return $organization->update($attributes);
    }

    public function delete(Organization $organization): bool
    {
        return (bool) $organization->delete();
    }

    public function hasResidents(Organization $organization): bool
    {
        return $organization->residents()->exists();
    }
}
