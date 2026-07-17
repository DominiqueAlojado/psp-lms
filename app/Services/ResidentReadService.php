<?php

namespace App\Services;

use App\Models\Resident;
use App\Models\User;
use App\Repositories\Contracts\ResidentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ResidentReadService
{
    private const ALL_ORGANIZATIONS_SLUG = 'all-organizations';

    public function __construct(
        private readonly ResidentRepositoryInterface $residentRepository,
    ) {}

    public function indexPayload(array $filters, User $user): array
    {
        $scope = $this->resolveResidentScope($user);

        return [
            'residents' => $this->list($filters, $scope['organizationId'], $scope['membershipOrganizationId']),
            'organizations' => $this->organizationOptions($user, $scope['isAggregateScope'] || $scope['membershipOrganizationId'] !== null),
            'yearLevelStats' => $this->residentRepository->getYearLevelStats($scope['organizationId'], $scope['membershipOrganizationId']),
            'courses' => $this->residentRepository->getDistinctCourses($scope['organizationId'], $scope['membershipOrganizationId']),
            'isAllOrganizationsContext' => $scope['isAggregateScope'],
        ];
    }

    public function showOrganizationsPayload(Resident $resident): array
    {
        $resident->load(['organization', 'memberships.organization', 'activeMemberships.organization', 'user.organizations']);

        $currentOrganizations = $resident->activeMemberships
            ->map(fn ($membership) => [
                'id' => $membership->organization->id,
                'name' => $membership->organization->name,
                'slug' => $membership->organization->slug,
                'type' => $membership->organization->type,
                'pivot' => [
                    'joined_at' => optional($membership->started_at)?->toISOString(),
                    'is_active' => $membership->ended_at === null,
                ],
                'membership' => [
                    'started_at' => optional($membership->started_at)?->toISOString(),
                    'ended_at' => optional($membership->ended_at)?->toISOString(),
                    'is_primary' => $membership->is_primary,
                ],
            ]);

        if ($currentOrganizations->isEmpty() && $resident->user) {
            $currentOrganizations = $resident->user->organizations
                ->where('pivot.is_active', true)
                ->map(fn ($organization) => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'slug' => $organization->slug,
                    'type' => $organization->type,
                    'pivot' => [
                        'joined_at' => optional($organization->pivot->joined_at)?->toISOString(),
                        'is_active' => $organization->pivot->is_active,
                    ],
                    'membership' => [
                        'started_at' => optional($organization->pivot->joined_at)?->toISOString(),
                        'ended_at' => null,
                        'is_primary' => (int) $organization->id === (int) $resident->organization_id,
                    ],
                ])
                ->values();
        }

        $associatedIds = $currentOrganizations->pluck('id')->toArray();
        $availableOrganizations = $this->residentRepository
            ->getActiveOrganizationsExcluding($associatedIds)
            ->map(fn ($organization) => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'type' => $organization->type,
            ]);

        $organizationHistory = $resident->memberships
            ->sortByDesc(fn ($membership) => $membership->started_at?->timestamp ?? 0)
            ->values()
            ->map(fn ($membership) => [
                'id' => $membership->id,
                'organization' => [
                    'id' => $membership->organization->id,
                    'name' => $membership->organization->name,
                    'slug' => $membership->organization->slug,
                    'type' => $membership->organization->type,
                ],
                'started_at' => optional($membership->started_at)?->toISOString(),
                'ended_at' => optional($membership->ended_at)?->toISOString(),
                'is_primary' => $membership->is_primary,
                'is_active' => $membership->ended_at === null,
                'year_level' => $membership->year_level,
                'status' => $membership->status,
            ]);

        return [
            'currentOrganizations' => $currentOrganizations,
            'availableOrganizations' => $availableOrganizations,
            'organizationHistory' => $organizationHistory,
        ];
    }

    private function list(array $filters, ?int $organizationId = null, ?int $membershipOrganizationId = null): LengthAwarePaginator
    {
        return $this->residentRepository
            ->paginate($filters, $organizationId, $membershipOrganizationId)
            ->through(fn ($resident) => [
                'id' => $resident->id,
                'uuid' => $resident->uuid,
                'full_name' => $resident->full_name,
                'full_name_with_middle_initial' => $resident->full_name_with_middle_initial,
                'first_name' => $resident->first_name,
                'middle_name' => $resident->middle_name,
                'last_name' => $resident->last_name,
                'email' => $resident->email,
                'contact_number' => $resident->contact_number,
                'course' => $resident->course,
                'year_level' => $resident->year_level,
                'status' => $resident->status,
                'updated_at' => $resident->updated_at->toIso8601String(),
                'organizations_count' => $resident->activeMemberships()->count()
                    ?: ($resident->user ? $resident->user->organizations()->wherePivot('is_active', true)->count() : 0),
                'organization' => [
                    'id' => $resident->organization->id,
                    'name' => $resident->organization->name,
                    'slug' => $resident->organization->slug,
                ],
            ]);
    }

    private function includeAllOrganizations(User $user): bool
    {
        return request()->query('org') === self::ALL_ORGANIZATIONS_SLUG
            && $user->hasAnyRole(['System Admin', 'BOP']);
    }

    private function resolveResidentScope(User $user): array
    {
        if ($this->includeAllOrganizations($user)) {
            return [
                'organizationId' => null,
                'membershipOrganizationId' => null,
                'isAggregateScope' => true,
            ];
        }

        $organizationType = strtolower((string) $user->currentOrganization?->type);

        if (in_array($organizationType, ['national', 'inservice'], true)) {
            return [
                'organizationId' => null,
                'membershipOrganizationId' => $user->current_organization_id,
                'isAggregateScope' => false,
            ];
        }

        return [
            'organizationId' => $user->current_organization_id,
            'membershipOrganizationId' => null,
            'isAggregateScope' => false,
        ];
    }

    private function organizationOptions(User $user, bool $isAggregateScope): Collection
    {
        if ($isAggregateScope) {
            return $this->residentRepository->getOrganizations();
        }

        return $user->organizations()
            ->select('organizations.id', 'organizations.name', 'organizations.slug')
            ->orderBy('organizations.name')
            ->get();
    }
}
