<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\AnnouncementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class AnnouncementReadService
{
    private const ALL_ORGANIZATIONS_SLUG = 'all-organizations';

    public function __construct(
        private readonly AnnouncementRepositoryInterface $announcementRepository,
    ) {}

    public function indexPayload(User $user, array $filters): array
    {
        $includeAllOrganizations = $this->includeAllOrganizations($user);

        return [
            'announcements' => $this->visibleAnnouncements($user->current_organization_id, $filters, $includeAllOrganizations),
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
            'announcements' => $this->manageableAnnouncements($user->current_organization_id, $canCreateSystem, $filters, $includeAllOrganizations),
            'canCreateSystem' => $canCreateSystem,
        ];
    }

    private function visibleAnnouncements(?int $organizationId, array $filters, bool $includeAllOrganizations): LengthAwarePaginator
    {
        return $this->announcementRepository
            ->paginateVisibleToOrganization($organizationId, $filters, includeAllOrganizations: $includeAllOrganizations)
            ->through(fn ($announcement) => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'content' => $announcement->content,
                'scope' => $announcement->scope,
                'priority' => $announcement->priority,
                'is_pinned' => $announcement->is_pinned,
                'target_year_levels' => $announcement->target_year_levels,
                'expires_at' => $announcement->expires_at?->format('M d, Y'),
                'organization_name' => $announcement->organization?->name,
                'created_by' => $announcement->creator->name,
                'created_at' => $announcement->created_at->format('M d, Y'),
                'views_count' => $announcement->views_count,
            ]);
    }

    private function manageableAnnouncements(?int $organizationId, bool $canCreateSystem, array $filters, bool $includeAllOrganizations): LengthAwarePaginator
    {
        return $this->announcementRepository
            ->paginateForManagement($organizationId, $canCreateSystem, $filters, includeAllOrganizations: $includeAllOrganizations)
            ->through(fn ($announcement) => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'content' => $announcement->content,
                'scope' => $announcement->scope,
                'priority' => $announcement->priority,
                'is_published' => $announcement->is_published,
                'is_pinned' => $announcement->is_pinned,
                'target_year_levels' => $announcement->target_year_levels,
                'expires_at' => $announcement->expires_at?->format('Y-m-d'),
                'organization_name' => $announcement->organization?->name,
                'created_by' => $announcement->creator->name,
                'created_at' => $announcement->created_at->format('M d, Y'),
                'updated_at' => $announcement->updated_at->diffForHumans(),
                'views_count' => $announcement->views_count,
            ]);
    }

    private function includeAllOrganizations(User $user): bool
    {
        return request()->query('org') === self::ALL_ORGANIZATIONS_SLUG
            && $user->hasAnyRole(['System Admin', 'BOP']);
    }
}
