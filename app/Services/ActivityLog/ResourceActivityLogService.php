<?php

namespace App\Services\ActivityLog;

use App\Models\LearningResource;
use Spatie\Activitylog\Models\Activity as ActivityLog;

class ResourceActivityLogService
{
    /**
     * Log resource creation.
     */
    public function logResourceCreated(LearningResource $resource): void
    {
        activity()
            ->performedOn($resource)
            ->causedBy(auth()->user() ?? null)
            ->useLog('resources')
            ->withProperties([
                'attributes' => [
                    'title' => $resource->title,
                    'description' => substr(strip_tags($resource->description ?? ''), 0, 100),
                    'category' => $resource->category,
                    'file_name' => $resource->file_name,
                    'file_type' => $resource->file_type,
                    'file_size' => $resource->file_size,
                    'target_year_levels' => ! empty($resource->target_year_levels) ? implode(', ', $resource->target_year_levels) : 'None',
                    'is_published' => $resource->is_published,
                ],
            ])
            ->log('Resource created');
    }

    /**
     * Log resource update.
     */
    public function logResourceUpdated(
        LearningResource $resource,
        array $attributes,
        array $oldValues
    ): void {
        activity()
            ->performedOn($resource)
            ->causedBy(auth()->user() ?? null)
            ->useLog('resources')
            ->withProperties([
                'attributes' => $attributes,
                'old' => $oldValues,
            ])
            ->log('Resource updated');
    }

    /**
     * Log resource deletion.
     */
    public function logResourceDeleted(LearningResource $resource): void
    {
        activity()
            ->performedOn($resource)
            ->causedBy(auth()->user() ?? null)
            ->useLog('resources')
            ->withProperties([
                'attributes' => [
                    'title' => $resource->title,
                    'file_name' => $resource->file_name,
                ],
            ])
            ->log('Resource deleted');
    }

    /**
     * Build update log data by comparing old and new values.
     */
    public function buildUpdateLogData(
        LearningResource $resource,
        array $validated,
        array $oldValues = []
    ): array {
        $attributes = [];
        $old = [];

        // Title
        $oldTitle = $oldValues['title'] ?? $resource->title;
        if (isset($validated['title']) && $validated['title'] !== $oldTitle) {
            $attributes['title'] = $validated['title'];
            $old['title'] = $oldTitle;
        }

        // Description
        $oldDescription = $oldValues['description'] ?? $resource->description ?? '';
        $newDescription = $validated['description'] ?? $oldDescription;
        if ($newDescription !== $oldDescription) {
            $attributes['description'] = substr(strip_tags($newDescription), 0, 100);
            $old['description'] = substr(strip_tags($oldDescription), 0, 100);
        }

        // Category
        $oldCategory = $oldValues['category'] ?? $resource->category;
        if (isset($validated['category']) && $validated['category'] !== $oldCategory) {
            $attributes['category'] = $validated['category'];
            $old['category'] = $oldCategory;
        }

        // Target Year Levels
        $oldYearLevels = $oldValues['target_year_levels'] ?? $resource->target_year_levels ?? [];
        $newYearLevels = $validated['target_year_levels'] ?? $oldYearLevels;
        $oldYearLevelsArray = is_array($oldYearLevels) ? $oldYearLevels : [];
        $newYearLevelsArray = is_array($newYearLevels) ? $newYearLevels : [];

        $oldSorted = collect($oldYearLevelsArray)->sort()->values()->toArray();
        $newSorted = collect($newYearLevelsArray)->sort()->values()->toArray();

        if (json_encode($oldSorted) !== json_encode($newSorted)) {
            $attributes['target_year_levels'] = ! empty($newYearLevelsArray) ? implode(', ', $newYearLevelsArray) : 'None';
            $old['target_year_levels'] = ! empty($oldYearLevelsArray) ? implode(', ', $oldYearLevelsArray) : 'None';
        }

        // Is Published - normalize boolean comparison
        $oldIsPublished = (bool) ($oldValues['is_published'] ?? $resource->is_published);
        $newIsPublished = isset($validated['is_published']) ? (bool) $validated['is_published'] : $oldIsPublished;
        if ($newIsPublished !== $oldIsPublished) {
            $attributes['is_published'] = $newIsPublished;
            $old['is_published'] = $oldIsPublished;
        }

        return [
            'attributes' => $attributes,
            'old' => $old,
        ];
    }

    /**
     * Get activity logs for a resource.
     */
    public function getLogs(LearningResource $resource, int $limit = 100): array
    {
        $logs = ActivityLog::query()
            ->where('subject_type', LearningResource::class)
            ->where('subject_id', $resource->id)
            ->where('log_name', 'resources')
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
                'created_at' => $log->created_at->toIso8601String(),
            ];
        })->toArray();
    }
}
