<?php

namespace App\Services\ActivityLog;

use App\Models\Resident;
use Spatie\Activitylog\Models\Activity as ActivityLog;

class ResidentActivityLogService
{
    /**
     * Log resident creation.
     */
    public function logResidentCreated(Resident $resident, array $attributes = []): void
    {
        activity()
            ->performedOn($resident)
            ->causedBy(auth()->user() ?? null)
            ->useLog('residents')
            ->withProperties([
                'attributes' => $attributes ?: [
                    'name' => $resident->full_name,
                    'email' => $resident->email,
                    'organization_id' => $resident->organization_id,
                    'year_level' => $resident->year_level,
                    'status' => $resident->status,
                ],
            ])
            ->log('Resident created');
    }

    /**
     * Log resident update with consolidated changes.
     */
    public function logResidentUpdated(
        Resident $resident,
        array $attributes = [],
        array $oldValues = []
    ): void {
        if (empty($attributes) && empty($oldValues)) {
            return;
        }

        activity()
            ->performedOn($resident)
            ->causedBy(auth()->user() ?? null)
            ->useLog('residents')
            ->withProperties([
                'attributes' => $attributes,
                'old' => $oldValues,
            ])
            ->log('Resident updated');
    }

    /**
     * Log resident deletion.
     */
    public function logResidentDeleted(Resident $resident): void
    {
        activity()
            ->performedOn($resident)
            ->causedBy(auth()->user() ?? null)
            ->useLog('residents')
            ->withProperties([
                'attributes' => [
                    'name' => $resident->full_name,
                    'email' => $resident->email,
                    'organization_id' => $resident->organization_id,
                ],
            ])
            ->log('Resident deleted');
    }

    /**
     * Get activity logs for a resident.
     */
    public function getLogs(Resident $resident, int $limit = 100): array
    {
        // Get activities where this resident is:
        // 1. The subject (being updated/modified) - e.g., when their profile is updated
        // 2. The causer (performing actions) - e.g., when they take exams, submit assignments, etc.
        $logs = ActivityLog::query()
            ->where(function ($query) use ($resident) {
                $query->where(function ($q) use ($resident) {
                    $q->where('subject_type', Resident::class)
                        ->where('subject_id', $resident->id);
                })->orWhere(function ($q) use ($resident) {
                    // If resident has a user, also get activities where the user is the causer
                    if ($resident->user_id) {
                        $q->where('causer_type', \App\Models\User::class)
                            ->where('causer_id', $resident->user_id);
                    }
                });
            })
            ->where('log_name', 'residents')
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
     * Build consolidated log data for resident updates.
     */
    public function buildUpdateLogData(
        Resident $resident,
        array $validated,
        string $oldFirstName,
        ?string $oldMiddleName,
        string $oldLastName,
        string $oldEmail,
        string $oldContactNumber,
        string $oldCourse,
        string $oldYearLevel,
        string $oldStatus,
        bool $passwordChanged
    ): array {
        $attributes = [];
        $oldValues = [];
        $hasChanges = false;

        // Check first name change
        if ($oldFirstName !== $validated['first_name']) {
            $attributes['first_name'] = $validated['first_name'];
            $oldValues['first_name'] = $oldFirstName;
            $hasChanges = true;
        }

        // Check middle name change
        if (($validated['middle_name'] ?? null) !== $oldMiddleName) {
            $attributes['middle_name'] = $validated['middle_name'] ?? null;
            $oldValues['middle_name'] = $oldMiddleName;
            $hasChanges = true;
        }

        // Check last name change
        if ($oldLastName !== $validated['last_name']) {
            $attributes['last_name'] = $validated['last_name'];
            $oldValues['last_name'] = $oldLastName;
            $hasChanges = true;
        }

        // Check email change
        if ($oldEmail !== $validated['email']) {
            $attributes['email'] = $validated['email'];
            $oldValues['email'] = $oldEmail;
            $hasChanges = true;
        }

        // Check contact number change
        if ($oldContactNumber !== $validated['contact_number']) {
            $attributes['contact_number'] = $validated['contact_number'];
            $oldValues['contact_number'] = $oldContactNumber;
            $hasChanges = true;
        }

        // Check course change
        if ($oldCourse !== $validated['course']) {
            $attributes['course'] = $validated['course'];
            $oldValues['course'] = $oldCourse;
            $hasChanges = true;
        }

        // Check year level change
        if ($oldYearLevel !== $validated['year_level']) {
            $attributes['year_level'] = $validated['year_level'];
            $oldValues['year_level'] = $oldYearLevel;
            $hasChanges = true;
        }

        // Check status change
        if ($oldStatus !== $validated['status']) {
            $attributes['status'] = $validated['status'];
            $oldValues['status'] = $oldStatus;
            $hasChanges = true;
        }

        // Check password change
        if ($passwordChanged) {
            $attributes['password'] = '***changed***';
            $oldValues['password'] = '***hidden***';
            $hasChanges = true;
        }

        return [
            'hasChanges' => $hasChanges,
            'attributes' => $attributes,
            'oldValues' => $oldValues,
        ];
    }
}

