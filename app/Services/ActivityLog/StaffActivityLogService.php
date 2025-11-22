<?php

namespace App\Services\ActivityLog;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Spatie\Activitylog\Models\Activity as ActivityLog;

class StaffActivityLogService
{
    /**
     * Log user creation.
     */
    public function logUserCreated(User $user, array $attributes = []): void
    {
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user() ?? null)
            ->useLog('users')
            ->withProperties([
                'attributes' => $attributes ?: [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ])
            ->log('User created');
    }

    /**
     * Log user update with consolidated changes.
     */
    public function logUserUpdated(
        User $user,
        array $attributes = [],
        array $oldValues = []
    ): void {
        if (empty($attributes) && empty($oldValues)) {
            return;
        }

        activity()
            ->performedOn($user)
            ->causedBy(auth()->user() ?? null)
            ->useLog('users')
            ->withProperties([
                'attributes' => $attributes,
                'old' => $oldValues,
            ])
            ->log('User updated');
    }

    /**
     * Log user deletion.
     */
    public function logUserDeleted(User $user): void
    {
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user() ?? null)
            ->useLog('users')
            ->withProperties([
                'attributes' => [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ])
            ->log('User deleted');
    }

    /**
     * Get activity logs for a staff member.
     */
    public function getLogs(User $staff, int $limit = 100): array
    {
        // Get activities where this user is:
        // 1. The subject (being updated/modified) - e.g., when their profile is updated
        // 2. The causer (performing actions) - e.g., when they grade submissions, update assessments, etc.
        $logs = ActivityLog::query()
            ->where(function ($query) use ($staff) {
                $query->where(function ($q) use ($staff) {
                    $q->where('subject_type', User::class)
                        ->where('subject_id', $staff->id);
                })->orWhere(function ($q) use ($staff) {
                    $q->where('causer_type', User::class)
                        ->where('causer_id', $staff->id);
                });
            })
            ->where('log_name', 'users')
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
     * Build consolidated log data for user updates.
     */
    public function buildUpdateLogData(
        User $user,
        array $validated,
        string $oldName,
        string $oldEmail,
        ?int $oldCurrentOrgId,
        array $oldRoles,
        array $oldOrganizations,
        bool $passwordChanged,
        array $newRoles,
        array $newOrganizations
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

        // Check email change
        if ($oldEmail !== $validated['email']) {
            $attributes['email'] = $validated['email'];
            $oldValues['email'] = $oldEmail;
            $hasChanges = true;
        }

        // Check current organization change
        if ($oldCurrentOrgId != ($validated['current_organization_id'] ?? null)) {
            $newOrgName = $validated['current_organization_id']
                ? Organization::find($validated['current_organization_id'])?->name
                : null;
            $oldOrgName = $oldCurrentOrgId
                ? Organization::find($oldCurrentOrgId)?->name
                : null;
            $attributes['current_organization'] = $newOrgName;
            $oldValues['current_organization'] = $oldOrgName;
            $hasChanges = true;
        }

        // Check password change
        if ($passwordChanged) {
            $attributes['password'] = '***changed***';
            $oldValues['password'] = '***hidden***';
            $hasChanges = true;
        }

        // Check role changes
        if ($oldRoles !== $newRoles) {
            $attributes['roles'] = $newRoles;
            $oldValues['roles'] = $oldRoles;
            $hasChanges = true;
        }

        // Check organization changes
        if ($oldOrganizations !== $newOrganizations) {
            $attributes['organizations'] = $newOrganizations;
            $oldValues['organizations'] = $oldOrganizations;
            $hasChanges = true;
        }

        return [
            'hasChanges' => $hasChanges,
            'attributes' => $attributes,
            'oldValues' => $oldValues,
        ];
    }
}

