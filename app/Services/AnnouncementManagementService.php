<?php

namespace App\Services;

use App\Actions\Announcements\CreateAnnouncementAction;
use App\Actions\Announcements\DeleteAnnouncementAction;
use App\Actions\Announcements\MarkAnnouncementViewedAction;
use App\Actions\Announcements\UpdateAnnouncementAction;
use App\Models\Announcement;
use App\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class AnnouncementManagementService
{
    private const ALL_ORGANIZATIONS_SLUG = 'all-organizations';

    public function __construct(
        private readonly CreateAnnouncementAction $createAnnouncementAction,
        private readonly UpdateAnnouncementAction $updateAnnouncementAction,
        private readonly DeleteAnnouncementAction $deleteAnnouncementAction,
        private readonly MarkAnnouncementViewedAction $markAnnouncementViewedAction,
    ) {}

    public function create(User $user, array $validated): Announcement
    {
        return $this->createAnnouncementAction->execute(
            $this->prepareAttributes($user, $validated)
        );
    }

    public function update(User $user, Announcement $announcement, array $validated): array
    {
        $attributes = $this->prepareAttributes($user, $validated);

        $this->updateAnnouncementAction->execute($announcement, $attributes);

        return $attributes;
    }

    public function delete(Announcement $announcement): bool
    {
        return $this->deleteAnnouncementAction->execute($announcement);
    }

    public function markAsViewed(Announcement $announcement, int $userId): void
    {
        $this->markAnnouncementViewedAction->execute($announcement, $userId);
    }

    public function canCreateSystem(User $user): bool
    {
        try {
            return $user->hasPermissionTo('create-system-announcements')
                || $user->hasAnyRole(['System Admin', 'BOP']);
        } catch (PermissionDoesNotExist) {
            return $user->hasAnyRole(['System Admin', 'BOP']);
        }
    }

    public function canManageAnnouncement(User $user, Announcement $announcement): bool
    {
        if ($user->hasAnyRole(['System Admin', 'BOP'])) {
            return true;
        }

        if ($announcement->scope === 'organization') {
            return $announcement->organization_id === $user->current_organization_id;
        }

        return $this->canCreateSystem($user);
    }

    public function canViewAnnouncement(User $user, Announcement $announcement): bool
    {
        if ($this->includeAllOrganizations($user) && $user->hasAnyRole(['System Admin', 'BOP'])) {
            return true;
        }

        if ($announcement->scope === 'system') {
            return true;
        }

        return $announcement->organization_id === $user->current_organization_id;
    }

    private function prepareAttributes(User $user, array $validated): array
    {
        return [
            'organization_id' => $validated['scope'] === 'organization' ? $user->current_organization_id : null,
            'created_by' => $validated['created_by'] ?? $user->id,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'scope' => $validated['scope'],
            'priority' => $validated['priority'],
            'is_published' => $validated['is_published'] ?? true,
            'is_pinned' => $validated['is_pinned'] ?? false,
            'target_year_levels' => $validated['target_year_levels'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
        ];
    }

    private function includeAllOrganizations(User $user): bool
    {
        return request()->query('org') === self::ALL_ORGANIZATIONS_SLUG
            && $user->hasAnyRole(['System Admin', 'BOP']);
    }
}
