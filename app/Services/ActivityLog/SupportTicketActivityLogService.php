<?php

namespace App\Services\ActivityLog;

use App\Models\SupportTicket;

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
}
