<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\LearningResourceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class ResourceReadService
{
    private const ALL_ORGANIZATIONS_SLUG = 'all-organizations';

    public function __construct(
        private readonly LearningResourceRepositoryInterface $learningResourceRepository,
    ) {}

    public function indexPayload(User $user, array $filters): array
    {
        $includeAllOrganizations = $this->includeAllOrganizations($user);

        return [
            'resources' => $this->publishedResources($user->current_organization_id, $filters, $includeAllOrganizations),
            'categories' => $this->learningResourceRepository->getPublishedCategoriesByOrganization($user->current_organization_id, $includeAllOrganizations),
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

        $includeAllOrganizations = $this->includeAllOrganizations($user);

        return [
            'resources' => $this->manageableResources($user->current_organization_id, $canCreateSystem, $filters, $includeAllOrganizations),
            'categories' => $this->learningResourceRepository->getCategoriesByOrganization($user->current_organization_id, $includeAllOrganizations),
            'canCreateSystem' => $canCreateSystem,
        ];
    }

    private function publishedResources(?int $organizationId, array $filters, bool $includeAllOrganizations): LengthAwarePaginator
    {
        return $this->learningResourceRepository
            ->paginatePublishedByOrganization($organizationId, $filters, includeAllOrganizations: $includeAllOrganizations)
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

    private function manageableResources(?int $organizationId, bool $canCreateSystem, array $filters, bool $includeAllOrganizations): LengthAwarePaginator
    {
        return $this->learningResourceRepository
            ->paginateForManagementByOrganization($organizationId, $canCreateSystem, $filters, includeAllOrganizations: $includeAllOrganizations)
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

    private function includeAllOrganizations(User $user): bool
    {
        return request()->query('org') === self::ALL_ORGANIZATIONS_SLUG
            && $user->hasAnyRole(['System Admin', 'BOP']);
    }
}
