<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly SupportTicket $ticket,
        private readonly string $event,
        private readonly string $title,
        private readonly string $message,
        private readonly ?User $actor = null,
    ) {}

    public static function ticketCreated(SupportTicket $ticket, ?User $actor = null): self
    {
        return new self(
            $ticket,
            'support.ticket.created',
            'New support ticket submitted',
            sprintf('%s was submitted as %s.', $ticket->title, $ticket->ticket_number),
            $actor,
        );
    }

    public static function ticketAssigned(SupportTicket $ticket, ?User $actor = null): self
    {
        return new self(
            $ticket,
            'support.ticket.assigned',
            'Support ticket assigned to you',
            sprintf('%s is now assigned to you.', $ticket->ticket_number),
            $actor,
        );
    }

    public static function ticketReplied(SupportTicket $ticket, ?User $actor = null): self
    {
        return new self(
            $ticket,
            'support.ticket.replied',
            'New reply on support ticket',
            sprintf('There is a new reply on %s.', $ticket->ticket_number),
            $actor,
        );
    }

    public static function ticketResolved(SupportTicket $ticket, ?User $actor = null): self
    {
        return new self(
            $ticket,
            'support.ticket.resolved',
            'Support ticket resolved',
            sprintf('%s has been marked resolved.', $ticket->ticket_number),
            $actor,
        );
    }

    public function via(object $notifiable): array
    {
        return filled($notifiable->email)
            ? ['database', 'mail']
            : ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $organizationSlug = $this->ticket->organization?->slug;
        $url = "/support/{$this->ticket->id}";

        if ($organizationSlug !== null && $organizationSlug !== '') {
            $url .= '?org='.$organizationSlug;
        }

        return [
            'category' => 'support',
            'event' => $this->event,
            'title' => $this->title,
            'message' => $this->message,
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'ticket_title' => $this->ticket->title,
            'ticket_status' => $this->ticket->status,
            'organization_id' => $this->ticket->organization_id,
            'organization_name' => $this->ticket->organization?->name,
            'organization_slug' => $organizationSlug,
            'actor_name' => $this->actor?->name,
            'url' => $url,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payload = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject($this->title)
            ->greeting(sprintf('Hello %s,', $notifiable->name ?? 'there'))
            ->line($this->message)
            ->line(sprintf('Ticket: %s', $payload['ticket_number']))
            ->line(sprintf('Status: %s', str($payload['ticket_status'])->replace('_', ' ')->title()))
            ->action('Open Ticket', url($payload['url']));
    }
}
