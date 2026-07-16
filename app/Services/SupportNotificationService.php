<?php

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportTicketNotification;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Illuminate\Support\Collection;

class SupportNotificationService
{
    public function notifyTicketCreated(SupportTicket $ticket, User $actor): void
    {
        $this->managerRecipients($ticket, $actor)
            ->each(fn (User $recipient) => $recipient->notify(
                SupportTicketNotification::ticketCreated($ticket, $actor)
            ));
    }

    public function notifyTicketUpdated(
        SupportTicket $ticket,
        User $actor,
        ?int $previousAssigneeId,
        string $previousStatus,
    ): void {
        if (
            $ticket->assigned_to_user_id !== null
            && $ticket->assigned_to_user_id !== $previousAssigneeId
            && $ticket->assigned_to_user_id !== $actor->id
        ) {
            $ticket->assignee?->notify(
                SupportTicketNotification::ticketAssigned($ticket, $actor)
            );
        }

        if (
            $ticket->status === 'resolved'
            && $previousStatus !== 'resolved'
            && $ticket->creator !== null
            && $ticket->creator->id !== $actor->id
        ) {
            $ticket->creator->notify(
                SupportTicketNotification::ticketResolved($ticket, $actor)
            );
        }
    }

    public function notifyReply(
        SupportTicket $ticket,
        User $actor,
        bool $isManagerReply,
    ): void {
        if ($isManagerReply) {
            if ($ticket->creator !== null && $ticket->creator->id !== $actor->id) {
                $ticket->creator->notify(
                    SupportTicketNotification::ticketReplied($ticket, $actor)
                );
            }

            return;
        }

        if ($ticket->assignee !== null && $ticket->assignee->id !== $actor->id) {
            $ticket->assignee->notify(
                SupportTicketNotification::ticketReplied($ticket, $actor)
            );

            return;
        }

        $this->managerRecipients($ticket, $actor)
            ->each(fn (User $recipient) => $recipient->notify(
                SupportTicketNotification::ticketReplied($ticket, $actor)
            ));
    }

    private function managerRecipients(SupportTicket $ticket, User $actor): Collection
    {
        return User::query()
            ->whereHas('organizations', function (Builder $query) use ($ticket) {
                $query->where('organizations.id', $ticket->organization_id)
                    ->where('organization_user.is_active', true);
            })
            ->whereDoesntHave('roles', function (Builder $query) {
                $query->where('name', 'Resident');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'current_organization_id'])
            ->filter(function (User $recipient) use ($actor) {
                if ($recipient->id === $actor->id) {
                    return false;
                }

                try {
                    return $recipient->hasPermissionTo('manage-support-tickets')
                        || $recipient->hasAnyRole(['System Admin', 'BOP']);
                } catch (PermissionDoesNotExist) {
                    return $recipient->hasAnyRole(['System Admin', 'BOP']);
                }
            })
            ->values();
    }
}
