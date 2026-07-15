<?php

namespace App\Services\ActivityLog;

use App\Models\Event;
use Spatie\Activitylog\Models\Activity as ActivityLog;

class EventActivityLogService
{
    /**
     * Log event creation.
     */
    public function logEventCreated(Event $event, array $attributes = []): void
    {
        activity()
            ->performedOn($event)
            ->causedBy(auth()->user() ?? null)
            ->useLog('events')
            ->withProperties([
                'attributes' => $attributes ?: [
                    'title' => $event->title,
                    'scope' => $event->scope,
                    'description' => $event->description ? substr(strip_tags($event->description), 0, 100) : null,
                    'event_category' => $event->event_category,
                    'event_type' => $event->event_type,
                    'start_date' => $event->start_date?->format('Y-m-d\TH:i'),
                    'end_date' => $event->end_date?->format('Y-m-d\TH:i'),
                    'registration_deadline' => $event->registration_deadline?->format('Y-m-d\TH:i'),
                    'location' => $event->location,
                    'virtual_link' => $event->virtual_link,
                    'capacity' => $event->capacity,
                    'price' => $event->price,
                    'is_free' => $event->is_free,
                    'cme_credits' => $event->cme_credits,
                    'requires_approval' => $event->requires_approval,
                    'is_published' => $event->is_published,
                ],
            ])
            ->log('Event created');
    }

    /**
     * Log event update with consolidated changes.
     */
    public function logEventUpdated(
        Event $event,
        array $attributes = [],
        array $oldValues = []
    ): void {
        if (empty($attributes) && empty($oldValues)) {
            return;
        }

        activity()
            ->performedOn($event)
            ->causedBy(auth()->user() ?? null)
            ->useLog('events')
            ->withProperties([
                'attributes' => $attributes,
                'old' => $oldValues,
            ])
            ->log('Event updated');
    }

    /**
     * Log event deletion.
     */
    public function logEventDeleted(Event $event): void
    {
        activity()
            ->performedOn($event)
            ->causedBy(auth()->user() ?? null)
            ->useLog('events')
            ->withProperties([
                'attributes' => [
                    'title' => $event->title,
                    'event_category' => $event->event_category,
                    'event_type' => $event->event_type,
                ],
            ])
            ->log('Event deleted');
    }

    /**
     * Build consolidated log data for event updates.
     */
    public function buildUpdateLogData(
        Event $event,
        array $validated,
        array $oldValues = []
    ): array {
        $attributes = [];
        $old = [];

        // Title
        if (isset($validated['title']) && $validated['title'] !== $oldValues['title'] ?? $event->title) {
            $attributes['title'] = $validated['title'];
            $old['title'] = $oldValues['title'] ?? $event->title;
        }

        // Description
        if (isset($validated['description']) && $validated['description'] !== ($oldValues['description'] ?? $event->description)) {
            $oldDesc = $oldValues['description'] ?? $event->description ?? '';
            $newDesc = $validated['description'] ?? '';
            $attributes['description'] = substr(strip_tags($newDesc), 0, 100);
            $old['description'] = substr(strip_tags($oldDesc), 0, 100);
        }

        if (isset($validated['scope']) && $validated['scope'] !== ($oldValues['scope'] ?? $event->scope)) {
            $attributes['scope'] = $validated['scope'];
            $old['scope'] = $oldValues['scope'] ?? $event->scope;
        }

        // Event Category
        if (isset($validated['event_category']) && $validated['event_category'] !== ($oldValues['event_category'] ?? $event->event_category)) {
            $attributes['event_category'] = $validated['event_category'];
            $old['event_category'] = $oldValues['event_category'] ?? $event->event_category;
        }

        // Event Type
        if (isset($validated['event_type']) && $validated['event_type'] !== ($oldValues['event_type'] ?? $event->event_type)) {
            $attributes['event_type'] = $validated['event_type'];
            $old['event_type'] = $oldValues['event_type'] ?? $event->event_type;
        }

        // Start Date - normalize format for comparison
        $oldStartDate = $oldValues['start_date'] ?? $event->start_date?->format('Y-m-d\TH:i');
        $newStartDate = isset($validated['start_date']) ? $validated['start_date'] : null;
        // Normalize: convert space to T if needed
        if ($newStartDate && str_contains($newStartDate, ' ')) {
            $newStartDate = str_replace(' ', 'T', $newStartDate);
        }
        if ($newStartDate && $newStartDate !== $oldStartDate) {
            $attributes['start_date'] = $newStartDate;
            $old['start_date'] = $oldStartDate;
        }

        // End Date - normalize format for comparison
        $oldEndDate = $oldValues['end_date'] ?? $event->end_date?->format('Y-m-d\TH:i');
        $newEndDate = isset($validated['end_date']) ? $validated['end_date'] : null;
        // Normalize: convert space to T if needed
        if ($newEndDate && str_contains($newEndDate, ' ')) {
            $newEndDate = str_replace(' ', 'T', $newEndDate);
        }
        if ($newEndDate && $newEndDate !== $oldEndDate) {
            $attributes['end_date'] = $newEndDate;
            $old['end_date'] = $oldEndDate;
        }

        // Registration Deadline - normalize format for comparison
        $oldRegistrationDeadline = $oldValues['registration_deadline'] ?? $event->registration_deadline?->format('Y-m-d\TH:i');
        $newRegistrationDeadline = isset($validated['registration_deadline']) ? $validated['registration_deadline'] : null;
        // Normalize: convert space to T if needed
        if ($newRegistrationDeadline && str_contains($newRegistrationDeadline, ' ')) {
            $newRegistrationDeadline = str_replace(' ', 'T', $newRegistrationDeadline);
        }
        if ($newRegistrationDeadline && $newRegistrationDeadline !== $oldRegistrationDeadline) {
            $attributes['registration_deadline'] = $newRegistrationDeadline;
            $old['registration_deadline'] = $oldRegistrationDeadline;
        }

        // Location
        if (isset($validated['location']) && $validated['location'] !== ($oldValues['location'] ?? $event->location)) {
            $attributes['location'] = $validated['location'];
            $old['location'] = $oldValues['location'] ?? $event->location;
        }

        // Virtual Link
        if (isset($validated['virtual_link']) && $validated['virtual_link'] !== ($oldValues['virtual_link'] ?? $event->virtual_link)) {
            $attributes['virtual_link'] = $validated['virtual_link'];
            $old['virtual_link'] = $oldValues['virtual_link'] ?? $event->virtual_link;
        }

        // Capacity
        if (isset($validated['capacity']) && $validated['capacity'] != ($oldValues['capacity'] ?? $event->capacity)) {
            $attributes['capacity'] = (int) $validated['capacity'];
            $old['capacity'] = $oldValues['capacity'] ?? $event->capacity;
        }

        // Price
        if (isset($validated['price']) && $validated['price'] != ($oldValues['price'] ?? $event->price)) {
            $attributes['price'] = (float) $validated['price'];
            $old['price'] = $oldValues['price'] ?? $event->price;
        }

        // Is Free
        if (isset($validated['is_free']) && $validated['is_free'] !== ($oldValues['is_free'] ?? $event->is_free)) {
            $attributes['is_free'] = (bool) $validated['is_free'];
            $old['is_free'] = $oldValues['is_free'] ?? $event->is_free;
        }

        // CME Credits
        if (isset($validated['cme_credits']) && $validated['cme_credits'] != ($oldValues['cme_credits'] ?? $event->cme_credits)) {
            $attributes['cme_credits'] = (float) $validated['cme_credits'];
            $old['cme_credits'] = $oldValues['cme_credits'] ?? $event->cme_credits;
        }

        // Requires Approval
        if (isset($validated['requires_approval']) && $validated['requires_approval'] !== ($oldValues['requires_approval'] ?? $event->requires_approval)) {
            $attributes['requires_approval'] = (bool) $validated['requires_approval'];
            $old['requires_approval'] = $oldValues['requires_approval'] ?? $event->requires_approval;
        }

        // Is Published
        if (isset($validated['is_published']) && $validated['is_published'] !== ($oldValues['is_published'] ?? $event->is_published)) {
            $attributes['is_published'] = (bool) $validated['is_published'];
            $old['is_published'] = $oldValues['is_published'] ?? $event->is_published;
        }

        return [
            'attributes' => $attributes,
            'old' => $old,
        ];
    }

    /**
     * Get activity logs for an event.
     */
    public function getLogs(Event $event, int $limit = 100): array
    {
        $logs = ActivityLog::query()
            ->where('subject_type', Event::class)
            ->where('subject_id', $event->id)
            ->where('log_name', 'events')
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
