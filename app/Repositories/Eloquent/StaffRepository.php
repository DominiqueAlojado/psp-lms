<?php

namespace App\Repositories\Eloquent;

use App\Models\Organization;
use App\Models\User;
use App\Repositories\Contracts\StaffRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class StaffRepository implements StaffRepositoryInterface
{
    public function getStaffRoleNames(): array
    {
        return Role::query()
            ->where('name', '!=', 'Resident')
            ->pluck('name')
            ->toArray();
    }

    public function paginate(array $filters, array $staffRoleNames, array $userOrganizationIds, bool $isSystemAdmin, int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
            ->whereHas('roles', function (Builder $query) use ($staffRoleNames) {
                $query->whereIn('name', $staffRoleNames);
            })
            ->when(! $isSystemAdmin, function (Builder $query) use ($userOrganizationIds) {
                $query->whereHas('organizations', function (Builder $organizationQuery) use ($userOrganizationIds) {
                    $organizationQuery->whereIn('organizations.id', $userOrganizationIds);
                });
            })
            ->with(['roles', 'currentOrganization'])
            ->withCount('organizations')
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $nestedQuery) use ($search) {
                    $nestedQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['role'] ?? null, function (Builder $query, string $role) {
                $query->whereHas('roles', function (Builder $roleQuery) use ($role) {
                    $roleQuery->where('name', $role);
                });
            })
            ->when($filters['organization'] ?? null, function (Builder $query, $organizationId) {
                $query->where('current_organization_id', $organizationId);
            })
            ->orderBy($filters['sort'] ?? 'name', $filters['direction'] ?? 'asc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getRoleStats(array $userOrganizationIds, bool $isSystemAdmin): Collection
    {
        $baseRoles = Role::query()
            ->where('name', '!=', 'Resident')
            ->orderBy('name')
            ->pluck('name');

        $counts = DB::table('roles')
            ->join('model_has_roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->join('users', function ($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', '=', User::class);
            })
            ->when(! $isSystemAdmin, function ($query) use ($userOrganizationIds) {
                $query->join('organization_user', function ($join) use ($userOrganizationIds) {
                    $join->on('organization_user.user_id', '=', 'users.id')
                        ->whereIn('organization_user.organization_id', $userOrganizationIds)
                        ->where('organization_user.is_active', true);
                });
            })
            ->where('roles.name', '!=', 'Resident')
            ->groupBy('roles.name')
            ->select('roles.name', DB::raw('COUNT(DISTINCT users.id) as aggregate_count'))
            ->pluck('aggregate_count', 'roles.name');

        return $baseRoles->map(fn (string $roleName) => [
            'role' => $roleName,
            'count' => (int) ($counts[$roleName] ?? 0),
        ]);
    }

    public function getSelectableRoles(User $user, bool $isSystemAdmin): Collection
    {
        return Role::query()
            ->where('name', '!=', 'Resident')
            ->when(! $isSystemAdmin, function (Builder $query) {
                $query->whereNotIn('name', ['System Admin', 'Admin']);
            })
            ->get(['id', 'name']);
    }

    public function getSelectableOrganizations(User $user, bool $isSystemAdmin): Collection
    {
        if ($isSystemAdmin) {
            return Organization::query()
                ->where('is_active', true)
                ->get(['id', 'name']);
        }

        return $user->organizations()
            ->wherePivot('organization_user.is_active', true)
            ->get(['organizations.id', 'organizations.name']);
    }

    public function create(array $attributes): User
    {
        return User::create($attributes);
    }

    public function update(User $staff, array $attributes): bool
    {
        return $staff->update($attributes);
    }

    public function delete(User $staff): bool
    {
        return (bool) $staff->delete();
    }

    public function syncRoles(User $staff, array $roleIds): void
    {
        $staff->syncRoles($roleIds);
    }

    public function attachOrganizations(User $staff, array $organizationIds): void
    {
        $staff->organizations()->attach($this->buildOrganizationPivotData($organizationIds));
    }

    public function syncOrganizations(User $staff, array $organizationIds): void
    {
        $staff->organizations()->sync($this->buildOrganizationPivotData($organizationIds));
    }

    public function getOrganizationNamesByIds(array $organizationIds): array
    {
        return Organization::query()
            ->whereIn('id', $organizationIds)
            ->pluck('name')
            ->sort()
            ->values()
            ->toArray();
    }

    public function findOrganizationsByIds(array $organizationIds): Collection
    {
        return Organization::query()
            ->whereIn('id', $organizationIds)
            ->get();
    }

    private function buildOrganizationPivotData(array $organizationIds): array
    {
        return collect($organizationIds)->mapWithKeys(function ($organizationId) {
            return [$organizationId => ['joined_at' => now(), 'is_active' => true]];
        })->toArray();
    }
}
