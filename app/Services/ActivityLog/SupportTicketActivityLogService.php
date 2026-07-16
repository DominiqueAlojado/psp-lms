<?php

namespace App\Services\ActivityLog;

use App\Models\SupportTicket;
use Spatie\Activitylog\Models\Activity as ActivityLog;

class SupportTicketActivityLogService
{
    public function logCreated(SupportTicket $ticket): void
    {
        activity()
            ->performedOn($ticket)
            ->causedBy(auth()->user() ?? null)
            ->useLog('support')
            ->withProperties([
                'attributes' => [
                    'ticket_number' => $ticket->ticket_number,
                    'title' => $ticket->title,
                    'category' => $ticket->category,
                    'priority' => $ticket->priority,
                    'status' => $ticket->status,
                ],
            ])
            ->log('Support ticket created');
    }

    public function logUpdated(SupportTicket $ticket, array $attributes, array $old): void
    {
        if (empty($attributes) && empty($old)) {
            return;
        }

        activity()
            ->performedOn($ticket)
            ->causedBy(auth()->user() ?? null)
            ->useLog('support')
            ->withProperties([
                'attributes' => $attributes,
                'old' => $old,
            ])
            ->log('Support ticket updated');
    }

    public function logReply(SupportTicket $ticket, string $message): void
    {
        activity()
            ->performedOn($ticket)
            ->causedBy(auth()->user() ?? null)
            ->useLog('support')
            ->withProperties([
                'attributes' => [
                    'ticket_number' => $ticket->ticket_number,
                    'message' => str($message)->limit(160)->toString(),
                ],
            ])
            ->log('Support ticket replied');
    }

    public function getLogs(SupportTicket $ticket, int $limit = 50): array
    {
        $logs = ActivityLog::query()
            ->where('subject_type', SupportTicket::class)
            ->where('subject_id', $ticket->id)
            ->where('log_name', 'support')
            ->with('causer')
            ->latest()
            ->limit($limit)
            ->get();

        return $logs->map(function (ActivityLog $log) {
            $properties = $log->properties->toArray();

            return [
                'id' => $log->id,
                'description' => $log->description,
                'causer' => $log->causer ? [
                    'id' => $log->causer->id,
                    'name' => $log->causer->name,
                    'email' => $log->causer->email,
                ] : null,
                'changes' => $this->formatChanges($properties),
                'created_at' => $log->created_at->format('M d, Y h:i A'),
                'created_at_human' => $log->created_at->diffForHumans(),
            ];
        })->all();
    }

    private function formatChanges(array $properties): array
    {
        $attributes = $properties['attributes'] ?? [];
        $old = $properties['old'] ?? [];
        $changes = [];

        foreach ($attributes as $key => $newValue) {
            $label = str($key)->replace('_', ' ')->title()->toString();
            $oldValue = $old[$key] ?? null;

            if (array_key_exists($key, $old)) {
                $changes[] = sprintf(
                    '%s: %s -> %s',
                    $label,
                    $this->formatValue($oldValue),
                    $this->formatValue($newValue),
                );

                continue;
            }

            $changes[] = sprintf('%s: %s', $label, $this->formatValue($newValue));
        }

        return $changes;
    }

    private function formatValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'None';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value) ?: '[]';
        }

        return str((string) $value)->replace('_', ' ')->title()->toString();
    }
}
