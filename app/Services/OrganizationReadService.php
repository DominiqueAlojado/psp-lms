<?php

namespace App\Services;

use App\Repositories\Contracts\OrganizationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrganizationReadService
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizationRepository,
    ) {}

    public function indexPayload(array $filters): array
    {
        return [
            'institutions' => $this->list($filters),
            'typeStats' => $this->organizationRepository->getTypeStats(),
            'types' => $this->organizationRepository->getDistinctTypes(),
        ];
    }

    private function list(array $filters): LengthAwarePaginator
    {
        return $this->organizationRepository
            ->paginate($filters)
            ->through(fn ($institution) => [
                'id' => $institution->id,
                'name' => $institution->name,
                'slug' => $institution->slug,
                'description' => $institution->description,
                'type' => $institution->type,
                'is_active' => $institution->is_active,
                'residents_count' => $institution->residents_count,
                'users_count' => $institution->users_count,
                'training_officers' => $institution->training_officers ?? [],
                'training_officers_count' => is_array($institution->training_officers) ? count($institution->training_officers) : 0,
                'updated_at' => $institution->updated_at->diffForHumans(),
            ]);
    }
}
