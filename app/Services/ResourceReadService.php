<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\LearningResourceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class ResourceReadService
{
    public function __construct(
        private readonly LearningResourceRepositoryInterface $learningResourceRepository,
    ) {}

    public function indexPayload(User $user, array $filters): array
    {
        return [
            'resources' => $this->publishedResources($user->current_organization_id, $filters),
            'categories' => $this->learningResourceRepository->getPublishedCategoriesByOrganization($user->current_organization_id),
        ];
    }

    public function managePayload(User $user, array $filters): array
    {
        try {
            $canCreateSystem = $user->hasPermissionTo('create-system-announcements')
                || $user->hasAnyRole(['System Admin', 'BOP']);
        } catch (PermissionDoesNotExist) {
            $canCreateSystem = $user->hasAnyRole(['System Admin', 'BOP']);
        }

        return [
            'resources' => $this->manageableResources($user->current_organization_id, $canCreateSystem, $filters),
            'categories' => $this->learningResourceRepository->getCategoriesByOrganization($user->current_organization_id),
            'canCreateSystem' => $canCreateSystem,
        ];
    }

    private function publishedResources(int $organizationId, array $filters): LengthAwarePaginator
    {
        return $this->learningResourceRepository
            ->paginatePublishedByOrganization($organizationId, $filters)
            ->through(fn ($resource) => [
                'id' => $resource->id,
                'title' => $resource->title,
                'description' => $resource->description,
                'category' => $resource->category,
                'scope' => $resource->scope,
                'file_name' => $resource->file_name,
                'file_type' => $resource->file_type,
                'file_size_formatted' => $resource->file_size_formatted,
                'target_year_levels' => $resource->target_year_levels,
                'download_count' => $resource->download_count,
                'uploaded_by' => $resource->uploader->name,
                'created_at' => $resource->created_at->format('M d, Y'),
            ]);
    }

    private function manageableResources(int $organizationId, bool $canCreateSystem, array $filters): LengthAwarePaginator
    {
        return $this->learningResourceRepository
            ->paginateForManagementByOrganization($organizationId, $canCreateSystem, $filters)
            ->through(fn ($resource) => [
                'id' => $resource->id,
                'title' => $resource->title,
                'description' => $resource->description,
                'category' => $resource->category,
                'scope' => $resource->scope,
                'file_name' => $resource->file_name,
                'file_type' => $resource->file_type,
                'file_size_formatted' => $resource->file_size_formatted,
                'file_url' => $resource->file_url,
                'target_year_levels' => $resource->target_year_levels,
                'is_published' => $resource->is_published,
                'download_count' => $resource->download_count,
                'organization_name' => $resource->organization?->name,
                'uploaded_by' => $resource->uploader->name,
                'created_at' => $resource->created_at->format('M d, Y'),
                'updated_at' => $resource->updated_at->diffForHumans(),
            ]);
    }
}
