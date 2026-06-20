<?php

namespace App\Repositories\Eloquent;

use App\Models\Organization;
use App\Models\Resident;
use App\Repositories\Contracts\ResidentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ResidentRepository implements ResidentRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Resident::query()
            ->with(['organization', 'user.organizations'])
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->search($search);
            })
            ->when($filters['organization_id'] ?? null, function (Builder $query, $organizationId) {
                $query->where('organization_id', $organizationId);
            })
            ->when($filters['year_level'] ?? null, function (Builder $query, string $yearLevel) {
                $query->where('year_level', $yearLevel);
            })
            ->when($filters['status'] ?? null, function (Builder $query, string $status) {
                $query->where('status', $status);
            })
            ->when($filters['course'] ?? null, function (Builder $query, string $course) {
                $query->where('course', $course);
            })
            ->orderBy($filters['sort'] ?? 'last_name', $filters['direction'] ?? 'asc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getOrganizations(): Collection
    {
        return Organization::query()
            ->select('id', 'name', 'slug')
            ->orderBy('name')
            ->get();
    }

    public function getForOrganization(int $organizationId): Collection
    {
        return Resident::query()
            ->with('user')
            ->where('organization_id', $organizationId)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    public function getActiveOrganizationsExcluding(array $excludedIds): Collection
    {
        return Organization::query()
            ->when($excludedIds !== [], function (Builder $query) use ($excludedIds) {
                $query->whereNotIn('id', $excludedIds);
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'type']);
    }

    public function getYearLevelStats(): array
    {
        return Resident::query()
            ->selectRaw('year_level, COUNT(*) as count')
            ->groupBy('year_level')
            ->pluck('count', 'year_level')
            ->toArray();
    }

    public function getDistinctCourses(): Collection
    {
        return Resident::query()
            ->distinct()
            ->pluck('course')
            ->filter()
            ->values();
    }

    public function create(array $attributes): Resident
    {
        return Resident::create($attributes);
    }

    public function update(Resident $resident, array $attributes): bool
    {
        return $resident->update($attributes);
    }

    public function delete(Resident $resident): bool
    {
        return (bool) $resident->delete();
    }

    public function findOrganizationById(int $organizationId): ?Organization
    {
        return Organization::find($organizationId);
    }

    public function findForOrganization(int $organizationId, int $residentId): Resident
    {
        return Resident::query()
            ->where('organization_id', $organizationId)
            ->findOrFail($residentId);
    }
}
