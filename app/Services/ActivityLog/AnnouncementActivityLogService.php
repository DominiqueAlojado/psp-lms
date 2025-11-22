<?php

namespace App\Services\ActivityLog;

use App\Models\Announcement;
use Spatie\Activitylog\Models\Activity as ActivityLog;

class AnnouncementActivityLogService
{
    /**
     * Log announcement creation.
     */
    public function logAnnouncementCreated(Announcement $announcement, array $attributes = []): void
    {
        activity()
            ->performedOn($announcement)
            ->causedBy(auth()->user() ?? null)
            ->useLog('announcements')
            ->withProperties([
                'attributes' => $attributes ?: [
                    'title' => $announcement->title,
                    'content' => $announcement->content ? substr(strip_tags($announcement->content), 0, 100) : null,
                    'scope' => $announcement->scope,
                    'priority' => $announcement->priority,
                    'is_published' => $announcement->is_published,
                    'is_pinned' => $announcement->is_pinned,
                    'target_year_levels' => $announcement->target_year_levels,
                    'expires_at' => $announcement->expires_at?->format('Y-m-d'),
                ],
            ])
            ->log('Announcement created');
    }

    /**
     * Log announcement update with consolidated changes.
     */
    public function logAnnouncementUpdated(
        Announcement $announcement,
        array $attributes = [],
        array $oldValues = []
    ): void {
        if (empty($attributes) && empty($oldValues)) {
            return;
        }

        activity()
            ->performedOn($announcement)
            ->causedBy(auth()->user() ?? null)
            ->useLog('announcements')
            ->withProperties([
                'attributes' => $attributes,
                'old' => $oldValues,
            ])
            ->log('Announcement updated');
    }

    /**
     * Log announcement deletion.
     */
    public function logAnnouncementDeleted(Announcement $announcement): void
    {
        activity()
            ->performedOn($announcement)
            ->causedBy(auth()->user() ?? null)
            ->useLog('announcements')
            ->withProperties([
                'attributes' => [
                    'title' => $announcement->title,
                    'scope' => $announcement->scope,
                    'priority' => $announcement->priority,
                ],
            ])
            ->log('Announcement deleted');
    }

    /**
     * Build consolidated log data for announcement updates.
     */
    public function buildUpdateLogData(
        Announcement $announcement,
        array $validated,
        array $oldValues = []
    ): array {
        $attributes = [];
        $old = [];

        // Title
        $oldTitle = $oldValues['title'] ?? $announcement->title;
        if (isset($validated['title']) && $validated['title'] !== $oldTitle) {
            $attributes['title'] = $validated['title'];
            $old['title'] = $oldTitle;
        }

        // Content
        $oldContent = $oldValues['content'] ?? $announcement->content ?? '';
        $newContent = $validated['content'] ?? $oldContent;
        if ($newContent !== $oldContent) {
            $attributes['content'] = substr(strip_tags($newContent), 0, 100);
            $old['content'] = substr(strip_tags($oldContent), 0, 100);
        }

        // Scope
        $oldScope = $oldValues['scope'] ?? $announcement->scope;
        if (isset($validated['scope']) && $validated['scope'] !== $oldScope) {
            $attributes['scope'] = $validated['scope'];
            $old['scope'] = $oldScope;
        }

        // Priority
        $oldPriority = $oldValues['priority'] ?? $announcement->priority;
        if (isset($validated['priority']) && $validated['priority'] !== $oldPriority) {
            $attributes['priority'] = $validated['priority'];
            $old['priority'] = $oldPriority;
        }

        // Is Published - normalize boolean comparison
        $oldIsPublished = (bool) ($oldValues['is_published'] ?? $announcement->is_published);
        $newIsPublished = isset($validated['is_published']) ? (bool) $validated['is_published'] : $oldIsPublished;
        if ($newIsPublished !== $oldIsPublished) {
            $attributes['is_published'] = $newIsPublished;
            $old['is_published'] = $oldIsPublished;
        }

        // Is Pinned - normalize boolean comparison
        $oldIsPinned = (bool) ($oldValues['is_pinned'] ?? $announcement->is_pinned);
        $newIsPinned = isset($validated['is_pinned']) ? (bool) $validated['is_pinned'] : $oldIsPinned;
        if ($newIsPinned !== $oldIsPinned) {
            $attributes['is_pinned'] = $newIsPinned;
            $old['is_pinned'] = $oldIsPinned;
        }

        // Target Year Levels
        $oldYearLevels = $oldValues['target_year_levels'] ?? $announcement->target_year_levels ?? [];
        $newYearLevels = $validated['target_year_levels'] ?? $oldYearLevels;
        $oldYearLevelsArray = is_array($oldYearLevels) ? $oldYearLevels : [];
        $newYearLevelsArray = is_array($newYearLevels) ? $newYearLevels : [];

        $oldSorted = collect($oldYearLevelsArray)->sort()->values()->toArray();
        $newSorted = collect($newYearLevelsArray)->sort()->values()->toArray();

        if (json_encode($oldSorted) !== json_encode($newSorted)) {
            $attributes['target_year_levels'] = ! empty($newYearLevelsArray) ? implode(', ', $newYearLevelsArray) : 'None';
            $old['target_year_levels'] = ! empty($oldYearLevelsArray) ? implode(', ', $oldYearLevelsArray) : 'None';
        }

        // Expires At - normalize format for comparison
        $oldExpiresAt = $oldValues['expires_at'] ?? $announcement->expires_at?->format('Y-m-d');
        $newExpiresAt = isset($validated['expires_at']) ? $validated['expires_at'] : $oldExpiresAt;
        if ($newExpiresAt && str_contains($newExpiresAt, ' ')) {
            $newExpiresAt = explode(' ', $newExpiresAt)[0]; // Extract date part only
        }
        // Normalize null/empty to null for comparison
        $oldExpiresAtNormalized = $oldExpiresAt ?: null;
        $newExpiresAtNormalized = $newExpiresAt ?: null;
        if ($newExpiresAtNormalized !== $oldExpiresAtNormalized) {
            $attributes['expires_at'] = $newExpiresAtNormalized ?? 'None';
            $old['expires_at'] = $oldExpiresAtNormalized ?? 'None';
        }

        return [
            'attributes' => $attributes,
            'old' => $old,
        ];
    }

    /**
     * Get activity logs for an announcement.
     */
    public function getLogs(Announcement $announcement, int $limit = 100): array
    {
        $logs = ActivityLog::query()
            ->where('subject_type', Announcement::class)
            ->where('subject_id', $announcement->id)
            ->where('log_name', 'announcements')
            ->with(['subject', 'causer'])
            ->latest()
            ->limit($limit)
            ->get();

        return $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'description' => $log->description,
                'log_name' => $log->log_name,
                'causer' => $log->causer ? [
                    'id' => $log->causer->id,
                    'name' => $log->causer->name,
                    'email' => $log->causer->email,
                ] : null,
                'properties' => $log->properties->toArray(),
                'created_at' => $log->created_at->toISOString(),
            ];
        })->toArray();
    }
}
