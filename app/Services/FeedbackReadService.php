<?php

namespace App\Services;

use App\Models\FeedbackEntry;
use App\Models\User;

class FeedbackReadService
{
    private const ALL_ORGANIZATIONS_SLUG = 'all-organizations';

    public function indexPayload(User $user): array
    {
        $isAllOrganizationsContext = $this->isAllOrganizationsContext($user);
        $organizationIds = $isAllOrganizationsContext
            ? $user->organizations()->wherePivot('is_active', true)->pluck('organizations.id')->all()
            : array_filter([$user->current_organization_id]);

        $query = FeedbackEntry::query()
            ->with(['organization:id,name', 'user:id,name,email'])
            ->when(
                $organizationIds !== [],
                fn ($builder) => $builder->whereIn('organization_id', $organizationIds)
            );

        $entries = $query
            ->orderByDesc('created_at')
            ->limit(12)
            ->get()
            ->map(fn (FeedbackEntry $entry) => [
                'id' => $entry->id,
                'organization_name' => $entry->organization?->name,
                'user_name' => $entry->user?->name,
                'overall_rating' => $entry->overall_rating,
                'content_rating' => $entry->content_rating,
                'support_rating' => $entry->support_rating,
                'usability_rating' => $entry->usability_rating,
                'context' => $entry->context,
                'module_name' => $entry->module_name,
                'page_url' => $entry->page_url,
                'comment' => $entry->comment,
                'would_recommend' => $entry->would_recommend,
                'created_at' => $entry->created_at->format('M d, Y h:i A'),
                'created_at_human' => $entry->created_at->diffForHumans(),
            ])
            ->values();

        $summarySource = $entries;

        return [
            'entries' => $entries,
            'summary' => [
                'total_feedback' => $entries->count(),
                'average_overall' => $summarySource->count() > 0
                    ? round($summarySource->avg('overall_rating'), 1)
                    : 0,
                'average_content' => $summarySource->count() > 0
                    ? round($summarySource->avg('content_rating'), 1)
                    : 0,
                'recommendation_rate' => $summarySource->where('would_recommend', true)->count() > 0
                    ? round(($summarySource->where('would_recommend', true)->count() / max($summarySource->whereNotNull('would_recommend')->count(), 1)) * 100, 1)
                    : 0,
            ],
            'canCreateFeedback' => ! $isAllOrganizationsContext && $user->current_organization_id !== null,
            'isAllOrganizationsContext' => $isAllOrganizationsContext,
        ];
    }

    private function isAllOrganizationsContext(User $user): bool
    {
        return $user->hasAnyRole(['System Admin', 'BOP'])
            && request()->query('org') === self::ALL_ORGANIZATIONS_SLUG;
    }
}
