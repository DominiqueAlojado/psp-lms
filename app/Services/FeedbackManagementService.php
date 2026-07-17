<?php

namespace App\Services;

use App\Models\FeedbackEntry;
use App\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class FeedbackManagementService
{
    private const ALL_ORGANIZATIONS_SLUG = 'all-organizations';

    public function create(User $user, array $validated): FeedbackEntry
    {
        abort_if(
            request()->query('org') === self::ALL_ORGANIZATIONS_SLUG,
            422,
            'Select a specific organization before submitting feedback.'
        );

        abort_if($user->current_organization_id === null, 403, 'No active organization selected.');

        return FeedbackEntry::query()->create([
            'organization_id' => $user->current_organization_id,
            'user_id' => $user->id,
            'overall_rating' => $validated['overall_rating'],
            'content_rating' => $validated['content_rating'],
            'support_rating' => $validated['support_rating'],
            'usability_rating' => $validated['usability_rating'],
            'context' => $validated['context'] ?? null,
            'module_name' => $validated['module_name'] ?? null,
            'page_url' => $validated['page_url'] ?? null,
            'comment' => $validated['comment'],
            'would_recommend' => $validated['would_recommend'] ?? null,
        ]);
    }

    public function delete(User $user, FeedbackEntry $feedbackEntry): void
    {
        if (! $this->canDelete($user, $feedbackEntry)) {
            abort(403, 'You do not have permission to delete this feedback entry.');
        }

        $feedbackEntry->delete();
    }

    public function canManage(User $user): bool
    {
        try {
            return $user->hasPermissionTo('view-all-feedback');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    private function canDelete(User $user, FeedbackEntry $feedbackEntry): bool
    {
        if ($this->isAllOrganizationsContext() && $this->canManage($user)) {
            return $user->organizations()
                ->wherePivot('is_active', true)
                ->where('organizations.id', $feedbackEntry->organization_id)
                ->exists()
                && $this->hasDeletePermission($user);
        }

        return $user->current_organization_id === $feedbackEntry->organization_id
            && $this->hasDeletePermission($user);
    }

    private function hasDeletePermission(User $user): bool
    {
        try {
            return $user->hasPermissionTo('delete-feedback');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    private function isAllOrganizationsContext(): bool
    {
        return request()->query('org') === self::ALL_ORGANIZATIONS_SLUG;
    }
}
