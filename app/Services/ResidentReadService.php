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
        $resident->load(['organization', 'user.organizations']);

        $currentOrganizations = $resident->user
            ? $resident->user->organizations->map(fn ($organization) => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'type' => $organization->type,
                'pivot' => [
                    'joined_at' => $organization->pivot->joined_at,
                    'is_active' => $organization->pivot->is_active,
                ],
            ])
            : collect();

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
                'organizations_count' => $resident->user ? $resident->user->organizations()->count() : 0,
                'organization' => [
                    'id' => $resident->organization->id,
                    'name' => $resident->organization->name,
                    'slug' => $resident->organization->slug,
                ],
            ]);
    }
}
