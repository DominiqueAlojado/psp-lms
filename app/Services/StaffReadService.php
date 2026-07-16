<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\StaffRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StaffReadService
{
    private const ALL_ORGANIZATIONS_SLUG = 'all-organizations';

    public function __construct(
        private readonly StaffRepositoryInterface $staffRepository,
    ) {}

    public function indexPayload(User $user, array $filters): array
    {
        $staffRoleNames = $this->staffRepository->getStaffRoleNames();
        $userOrganizationIds = $user->organizations()->pluck('organizations.id')->toArray();
        $canManageAllOrganizations = $user->hasAnyRole(['System Admin', 'BOP']);
        $isAllOrganizationsContext = $this->isAllOrganizationsContext($user);
        $currentOrganizationId = $isAllOrganizationsContext ? null : $user->current_organization_id;

        return [
            'staff' => $this->list($filters, $staffRoleNames, $userOrganizationIds, $isAllOrganizationsContext, $currentOrganizationId),
            'roleStats' => $this->staffRepository->getRoleStats($userOrganizationIds, $isAllOrganizationsContext, $currentOrganizationId),
            'roles' => $this->staffRepository->getSelectableRoles($user, $canManageAllOrganizations),
            'organizations' => $this->staffRepository->getSelectableOrganizations($user, $canManageAllOrganizations),
            'isAllOrganizationsContext' => $isAllOrganizationsContext,
        ];
    }

    public function showPayload(User $staff): array
    {
        $staff->load(['roles', 'currentOrganization', 'organizations']);

        return [
            'staff' => [
                'id' => $staff->id,
                'uuid' => $staff->uuid,
                'name' => $staff->name,
                'email' => $staff->email,
                'roles' => $staff->roles->pluck('id')->toArray(),
                'role_names' => $staff->roles->pluck('name')->toArray(),
                'current_organization_id' => $staff->current_organization_id,
                'organizations' => $staff->organizations->map(fn ($organization) => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                ])->toArray(),
            ],
        ];
    }

    private function list(array $filters, array $staffRoleNames, array $userOrganizationIds, bool $isAllOrganizationsContext, ?int $currentOrganizationId = null): LengthAwarePaginator
    {
        return $this->staffRepository
            ->paginate($filters, $staffRoleNames, $userOrganizationIds, $isAllOrganizationsContext, $currentOrganizationId)
            ->through(fn ($user) => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
                'primary_role' => $user->roles->first()?->name ?? 'N/A',
                'current_organization' => $user->currentOrganization?->name ?? 'N/A',
                'organizations_count' => (int) ($user->organizations_count ?? 0),
                'created_at' => $user->created_at->format('Y-m-d'),
                'updated_at' => $user->updated_at->toIso8601String(),
            ]);
    }

    private function isAllOrganizationsContext(User $user): bool
    {
        return $user->hasAnyRole(['System Admin', 'BOP'])
            && request()->query('org') === self::ALL_ORGANIZATIONS_SLUG;
    }
}
