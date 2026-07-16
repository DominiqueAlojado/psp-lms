<?php

namespace App\Services;

use App\Models\FeedbackEntry;
use App\Models\User;

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
}
