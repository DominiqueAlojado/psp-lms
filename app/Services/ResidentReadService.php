<?php

namespace App\Services;

use App\Models\Resident;
use App\Repositories\Contracts\ResidentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ResidentReadService
{
    public function __construct(
        private readonly ResidentRepositoryInterface $residentRepository,
    ) {}

    public function indexPayload(array $filters): array
    {
        return [
            'residents' => $this->list($filters),
            'organizations' => $this->residentRepository->getOrganizations(),
            'yearLevelStats' => $this->residentRepository->getYearLevelStats(),
            'courses' => $this->residentRepository->getDistinctCourses(),
        ];
    }

    public function showOrganizationsPayload(Resident $resident): array
    {
        $resident->load(['organization', 'activeMemberships.organization', 'user.organizations']);

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

        return [
            'currentOrganizations' => $currentOrganizations,
            'availableOrganizations' => $availableOrganizations,
        ];
    }

    private function list(array $filters): LengthAwarePaginator
    {
        return $this->residentRepository
            ->paginate($filters)
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
                'updated_at' => $resident->updated_at->diffForHumans(),
                'organizations_count' => $resident->activeMemberships()->count()
                    ?: ($resident->user ? $resident->user->organizations()->wherePivot('is_active', true)->count() : 0),
                'organization' => [
                    'id' => $resident->organization->id,
                    'name' => $resident->organization->name,
                    'slug' => $resident->organization->slug,
                ],
            ]);
    }
}
