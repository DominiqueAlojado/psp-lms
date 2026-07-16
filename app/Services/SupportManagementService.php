<?php

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Repositories\Contracts\SupportTicketRepositoryInterface;
use App\Services\ActivityLog\SupportTicketActivityLogService;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class SupportManagementService
{
    public function __construct(
        private readonly SupportTicketRepositoryInterface $supportTicketRepository,
        private readonly SupportTicketActivityLogService $activityLogService,
    ) {}

    public function create(User $user, array $validated): SupportTicket
    {
        abort_if($user->current_organization_id === null, 403, 'No active organization selected.');

        $ticket = $this->supportTicketRepository->create([
            'organization_id' => $user->current_organization_id,
            'user_id' => $user->id,
            'title' => $validated['title'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'status' => 'open',
            'module_name' => $validated['module_name'] ?? null,
            'page_url' => $validated['page_url'] ?? null,
            'details' => $validated['details'],
            'last_replied_at' => now(),
        ]);

        $this->supportTicketRepository->update($ticket, [
            'ticket_number' => sprintf('SUP-%05d', $ticket->id),
        ]);

        $ticket->refresh();

        $this->activityLogService->logCreated($ticket);

        return $ticket;
    }

    public function update(User $user, SupportTicket $ticket, array $validated): void
    {
        if (! $this->canManageTicket($user, $ticket)) {
            abort(403, 'You do not have permission to manage this support ticket.');
        }

        $assigneeId = $validated['assigned_to_user_id'] ?? null;

        if ($assigneeId !== null && ! $this->assignableUserIds($ticket->organization_id)->contains($assigneeId)) {
            abort(403, 'The selected assignee is not available for this organization.');
        }

        $old = [
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'assigned_to_user_id' => $ticket->assigned_to_user_id,
        ];

        $attributes = [
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'assigned_to_user_id' => $assigneeId,
            'resolved_at' => $validated['status'] === 'resolved' ? now() : null,
        ];

        $this->supportTicketRepository->update($ticket, $attributes);
        $ticket->refresh();

        $changes = array_filter([
            'status' => $old['status'] !== $ticket->status ? $ticket->status : null,
            'priority' => $old['priority'] !== $ticket->priority ? $ticket->priority : null,
            'assigned_to_user_id' => $old['assigned_to_user_id'] !== $ticket->assigned_to_user_id ? $ticket->assigned_to_user_id : null,
        ], fn ($value) => $value !== null);

        $oldChanges = array_filter([
            'status' => array_key_exists('status', $changes) ? $old['status'] : null,
            'priority' => array_key_exists('priority', $changes) ? $old['priority'] : null,
            'assigned_to_user_id' => array_key_exists('assigned_to_user_id', $changes) ? $old['assigned_to_user_id'] : null,
        ], fn ($value) => $value !== null);

        $this->activityLogService->logUpdated($ticket, $changes, $oldChanges);
    }

    public function addReply(User $user, SupportTicket $ticket, string $message): SupportTicketMessage
    {
        if (! $this->canViewTicket($user, $ticket)) {
            abort(403, 'You do not have access to this support ticket.');
        }

        $reply = $this->supportTicketRepository->createMessage([
            'support_ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $message,
        ]);

        $nextStatus = $ticket->status;
        if ($this->canManageTicket($user, $ticket) && $ticket->status === 'open') {
            $nextStatus = 'in_review';
        }

        if (! $this->canManageTicket($user, $ticket) && $ticket->status === 'resolved') {
            $nextStatus = 'open';
        }

        $this->supportTicketRepository->update($ticket, [
            'status' => $nextStatus,
            'resolved_at' => $nextStatus === 'resolved' ? $ticket->resolved_at : null,
            'last_replied_at' => now(),
        ]);

        $ticket->refresh();
        $this->activityLogService->logReply($ticket, $message);

        return $reply;
    }

    public function canManage(User $user): bool
    {
        try {
            return $user->hasPermissionTo('manage-support-tickets')
                || $user->hasAnyRole(['System Admin', 'BOP']);
        } catch (PermissionDoesNotExist) {
            return $user->hasAnyRole(['System Admin', 'BOP']);
        }
    }

    public function canViewTicket(User $user, SupportTicket $ticket): bool
    {
        if (
            $ticket->user_id === $user->id
            && $ticket->organization_id === $user->current_organization_id
        ) {
            return true;
        }

        return $this->canManageTicket($user, $ticket);
    }

    public function canManageTicket(User $user, SupportTicket $ticket): bool
    {
        return $this->canManage($user)
            && $ticket->organization_id === $user->current_organization_id;
    }

    private function assignableUserIds(int $organizationId)
    {
        return $this->supportTicketRepository
            ->getAssignableStaff($organizationId)
            ->pluck('id');
    }
}
