<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\StaffRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StaffReadService
{
    public function __construct(
        private readonly StaffRepositoryInterface $staffRepository,
    ) {}

    public function indexPayload(User $user, array $filters): array
    {
        $staffRoleNames = $this->staffRepository->getStaffRoleNames();
        $userOrganizationIds = $user->organizations()->pluck('organizations.id')->toArray();
        $isSystemAdmin = $user->hasRole('System Admin');

        return [
            'staff' => $this->list($filters, $staffRoleNames, $userOrganizationIds, $isSystemAdmin),
            'roleStats' => $this->staffRepository->getRoleStats($userOrganizationIds, $isSystemAdmin),
            'roles' => $this->staffRepository->getSelectableRoles(),
            'organizations' => $this->staffRepository->getSelectableOrganizations($user, $isSystemAdmin),
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

    private function list(array $filters, array $staffRoleNames, array $userOrganizationIds, bool $isSystemAdmin): LengthAwarePaginator
    {
        return $this->staffRepository
            ->paginate($filters, $staffRoleNames, $userOrganizationIds, $isSystemAdmin)
            ->through(fn ($user) => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
                'primary_role' => $user->roles->first()?->name ?? 'N/A',
                'current_organization' => $user->currentOrganization?->name ?? 'N/A',
                'organizations_count' => $user->organizations()->count(),
                'created_at' => $user->created_at->format('Y-m-d'),
                'updated_at' => $user->updated_at->diffForHumans(),
            ]);
    }
}
