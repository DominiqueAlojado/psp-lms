<?php

namespace App\Services\ActivityLog;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Spatie\Activitylog\Models\Activity as ActivityLog;

class OrganizationActivityLogService
{
    /**
     * Log organization creation.
     */
    public function logOrganizationCreated(Organization $organization, array $attributes = []): void
    {
        activity()
            ->performedOn($organization)
            ->causedBy(auth()->user() ?? null)
            ->useLog('organizations')
            ->withProperties([
                'attributes' => $attributes ?: [
                    'name' => $organization->name,
                    'type' => $organization->type,
                    'is_active' => $organization->is_active,
                    'training_officers' => $organization->training_officers ?? [],
                ],
            ])
            ->log('Organization created');
    }

    /**
     * Log organization update with consolidated changes.
     */
    public function logOrganizationUpdated(
        Organization $organization,
        array $attributes = [],
        array $oldValues = []
    ): void {
        if (empty($attributes) && empty($oldValues)) {
            return;
        }

        activity()
            ->performedOn($organization)
            ->causedBy(auth()->user() ?? null)
            ->useLog('organizations')
            ->withProperties([
                'attributes' => $attributes,
                'old' => $oldValues,
            ])
            ->log('Organization updated');
    }

    /**
     * Log organization deletion.
     */
    public function logOrganizationDeleted(Organization $organization): void
    {
        activity()
            ->performedOn($organization)
            ->causedBy(auth()->user() ?? null)
            ->useLog('organizations')
            ->withProperties([
                'attributes' => [
                    'name' => $organization->name,
                    'type' => $organization->type,
                ],
            ])
            ->log('Organization deleted');
    }

    /**
     * Get activity logs for an organization.
     */
    public function getLogs(Organization $organization, int $limit = 100): array
    {
        // Get activities where this organization is:
        // 1. The subject (being updated/modified) - e.g., when it's updated
        // 2. Related activities - e.g., when users are associated with it
        $logs = ActivityLog::query()
            ->where(function ($query) use ($organization) {
                $query->where(function ($q) use ($organization) {
                    $q->where('subject_type', Organization::class)
                        ->where('subject_id', $organization->id);
                })->orWhere(function ($q) use ($organization) {
                    // Also get activities where organization is mentioned in properties
                    // This might include activities like user organization changes
                    $q->where('log_name', 'organizations')
                        ->whereJsonContains('properties->attributes->current_organization_id', $organization->id);
                });
            })
            ->where('log_name', 'organizations')
            ->with(['subject', 'causer'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'log_name' => $activity->log_name,
                    'event' => $activity->event,
                    'properties' => $activity->properties,
                    'causer' => $activity->causer ? [
                        'id' => $activity->causer->id,
                        'name' => $activity->causer->name,
                        'email' => $activity->causer->email,
                    ] : null,
                    'subject' => $activity->subject ? [
                        'id' => $activity->subject->id,
                        'type' => class_basename($activity->subject_type),
                    ] : null,
                    'created_at' => $activity->created_at->toISOString(),
                ];
            })
            ->toArray();

        return $logs;
    }

    /**
     * Build consolidated log data for organization updates.
     */
    public function buildUpdateLogData(
        Organization $organization,
        array $validated,
        string $oldName,
        ?string $oldDescription,
        string $oldType,
        bool $oldIsActive,
        array $oldTrainingOfficers
    ): array {
        $attributes = [];
        $oldValues = [];
        $hasChanges = false;

        // Check name change
        if ($oldName !== $validated['name']) {
            $attributes['name'] = $validated['name'];
            $oldValues['name'] = $oldName;
            $hasChanges = true;
        }

        // Check description change
        if (($validated['description'] ?? null) !== $oldDescription) {
            $attributes['description'] = $validated['description'] ?? null;
            $oldValues['description'] = $oldDescription;
            $hasChanges = true;
        }

        // Check type change
        if ($oldType !== $validated['type']) {
            $attributes['type'] = $validated['type'];
            $oldValues['type'] = $oldType;
            $hasChanges = true;
        }

        // Check is_active change
        $newIsActive = $validated['is_active'] ?? true;
        if ($oldIsActive !== $newIsActive) {
            $attributes['is_active'] = $newIsActive;
            $oldValues['is_active'] = $oldIsActive;
            $hasChanges = true;
        }

        // Check training officers change
        $newTrainingOfficers = $validated['training_officers'] ?? [];

        // Ensure both are arrays of IDs
        $oldTrainingOfficersIds = is_array($oldTrainingOfficers)
            ? array_filter(array_map('intval', $oldTrainingOfficers))
            : [];
        $newTrainingOfficersIds = is_array($newTrainingOfficers)
            ? array_filter(array_map('intval', $newTrainingOfficers))
            : [];

        $oldTrainingOfficersSorted = array_values(array_unique($oldTrainingOfficersIds));
        $newTrainingOfficersSorted = array_values(array_unique($newTrainingOfficersIds));
        sort($oldTrainingOfficersSorted);
        sort($newTrainingOfficersSorted);

        if ($oldTrainingOfficersSorted !== $newTrainingOfficersSorted) {
            // Fetch user details for old training officers
            $oldTrainingOfficersDetails = [];
            if (! empty($oldTrainingOfficersIds)) {
                $oldUsers = User::whereIn('id', $oldTrainingOfficersIds)->get(['id', 'name', 'email']);
                $oldTrainingOfficersDetails = $oldUsers->map(fn($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ])->sortBy('id')->values()->toArray();
            }

            // Fetch user details for new training officers
            $newTrainingOfficersDetails = [];
            if (! empty($newTrainingOfficersIds)) {
                $newUsers = User::whereIn('id', $newTrainingOfficersIds)->get(['id', 'name', 'email']);
                $newTrainingOfficersDetails = $newUsers->map(fn($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ])->sortBy('id')->values()->toArray();
            }

            $attributes['training_officers'] = $newTrainingOfficersDetails;
            $oldValues['training_officers'] = $oldTrainingOfficersDetails;
            $hasChanges = true;
        }

        return [
            'hasChanges' => $hasChanges,
            'attributes' => $attributes,
            'oldValues' => $oldValues,
        ];
    }
}
