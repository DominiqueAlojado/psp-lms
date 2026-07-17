<?php

namespace App\Services;

use App\Models\FeedbackEntry;
use App\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class FeedbackReadService
{
    private const ALL_ORGANIZATIONS_SLUG = 'all-organizations';

    public function indexPayload(User $user): array
    {
        $isAllOrganizationsContext = $this->isAllOrganizationsContext($user);
        $canCreateFeedback = $this->canCreateFeedback($user, $isAllOrganizationsContext);
        $canManageFeedback = $this->canManageFeedback($user);
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
            'canCreateFeedback' => $canCreateFeedback,
            'canManageFeedback' => $canManageFeedback,
            'isAllOrganizationsContext' => $isAllOrganizationsContext,
        ];
    }

    public function managePayload(User $user, array $filters): array
    {
        abort_unless($this->canManageFeedback($user), 403);

        $isAllOrganizationsContext = $this->isAllOrganizationsContext($user);
        $organizationIds = $isAllOrganizationsContext
            ? $user->organizations()->wherePivot('is_active', true)->pluck('organizations.id')->all()
            : array_filter([$user->current_organization_id]);

        abort_if($organizationIds === [], 403, 'No active organization selected.');

        $query = FeedbackEntry::query()
            ->with(['organization:id,name', 'user:id,name,email'])
            ->whereIn('organization_id', $organizationIds)
            ->when($filters['search'] ?? null, function ($builder, string $search) {
                $builder->where(function ($nested) use ($search) {
                    $nested->where('comment', 'like', "%{$search}%")
                        ->orWhere('context', 'like', "%{$search}%")
                        ->orWhere('module_name', 'like', "%{$search}%");
                });
            })
            ->when($filters['module_name'] ?? null, fn ($builder, string $module) => $builder->where('module_name', $module))
            ->when(($filters['would_recommend'] ?? null) !== null && $filters['would_recommend'] !== '', function ($builder) use ($filters) {
                $builder->where('would_recommend', filter_var($filters['would_recommend'], FILTER_VALIDATE_BOOL));
            });

        $entries = $query
            ->latest()
            ->paginate(12)
            ->through(fn (FeedbackEntry $entry) => [
                'id' => $entry->id,
                'organization_name' => $entry->organization?->name,
                'user_name' => $entry->user?->name,
                'user_email' => $entry->user?->email,
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
            ]);

        $summaryQuery = FeedbackEntry::query()->whereIn('organization_id', $organizationIds);

        return [
            'entries' => $entries,
            'summary' => [
                'total_feedback' => $summaryQuery->count(),
                'average_overall' => round((float) ($summaryQuery->avg('overall_rating') ?? 0), 1),
                'average_content' => round((float) ($summaryQuery->avg('content_rating') ?? 0), 1),
                'recommendation_rate' => $this->recommendationRate($organizationIds),
            ],
            'filters' => [
                'search' => $filters['search'] ?? '',
                'module_name' => $filters['module_name'] ?? '',
                'would_recommend' => $filters['would_recommend'] ?? '',
            ],
            'modules' => FeedbackEntry::query()
                ->whereIn('organization_id', $organizationIds)
                ->whereNotNull('module_name')
                ->where('module_name', '!=', '')
                ->distinct()
                ->orderBy('module_name')
                ->pluck('module_name')
                ->values(),
            'canDeleteFeedback' => $this->canDeleteFeedback($user),
            'isAllOrganizationsContext' => $isAllOrganizationsContext,
        ];
    }

    private function canCreateFeedback(User $user, bool $isAllOrganizationsContext): bool
    {
        if ($isAllOrganizationsContext || $user->current_organization_id === null) {
            return false;
        }

        try {
            return $user->hasPermissionTo('create-feedback');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    private function canManageFeedback(User $user): bool
    {
        try {
            return $user->hasPermissionTo('view-all-feedback');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    private function canDeleteFeedback(User $user): bool
    {
        try {
            return $user->hasPermissionTo('delete-feedback');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    private function recommendationRate(array $organizationIds): float
    {
        $query = FeedbackEntry::query()
            ->whereIn('organization_id', $organizationIds)
            ->whereNotNull('would_recommend');

        $total = $query->count();

        if ($total === 0) {
            return 0;
        }

        $recommended = (clone $query)->where('would_recommend', true)->count();

        return round(($recommended / $total) * 100, 1);
    }

    private function isAllOrganizationsContext(User $user): bool
    {
        return $user->hasAnyRole(['System Admin', 'BOP'])
            && request()->query('org') === self::ALL_ORGANIZATIONS_SLUG;
    }
}
