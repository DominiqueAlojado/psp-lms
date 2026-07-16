<?php

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\User;
use App\Repositories\Contracts\SupportTicketRepositoryInterface;

class SupportReadService
{
    private const ALL_ORGANIZATIONS_SLUG = 'all-organizations';

    public function __construct(
        private readonly SupportTicketRepositoryInterface $supportTicketRepository,
        private readonly SupportManagementService $supportManagementService,
    ) {}

    public function indexPayload(User $user, array $filters): array
    {
        if ($this->isAllOrganizationsContext($user)) {
            $organizationIds = $this->organizationIdsForAllContext($user);
            $canManage = $this->supportManagementService->canManage($user);

            return [
                'tickets' => ($canManage
                    ? $this->supportTicketRepository->paginateForManagementAcrossOrganizations($organizationIds, $filters)
                    : $this->supportTicketRepository->paginateForUserAcrossOrganizations($user->id, $organizationIds, $filters))
                    ->through(fn (SupportTicket $ticket) => $this->ticketListItem($ticket)),
                'summary' => $canManage
                    ? $this->supportTicketRepository->getManagementSummaryAcrossOrganizations($organizationIds, $filters)
                    : $this->supportTicketRepository->getUserSummaryAcrossOrganizations($user->id, $organizationIds),
                'categories' => $this->options($this->supportTicketRepository->getCategories()),
                'priorities' => $this->options($this->supportTicketRepository->getPriorities()),
                'statuses' => $this->options($this->supportTicketRepository->getStatuses()),
                'canManage' => $canManage,
                'isAllOrganizationsContext' => true,
                'canCreateTicket' => false,
                'showsManagedTickets' => $canManage,
            ];
        }

        abort_if($user->current_organization_id === null, 403, 'No active organization selected.');

        return [
            'tickets' => $this->supportTicketRepository
                ->paginateForUser($user->id, $user->current_organization_id, $filters)
                ->through(fn (SupportTicket $ticket) => $this->ticketListItem($ticket)),
            'summary' => $this->supportTicketRepository->getUserSummary($user->id, $user->current_organization_id),
            'categories' => $this->options($this->supportTicketRepository->getCategories()),
            'priorities' => $this->options($this->supportTicketRepository->getPriorities()),
            'statuses' => $this->options($this->supportTicketRepository->getStatuses()),
            'canManage' => $this->supportManagementService->canManage($user),
            'isAllOrganizationsContext' => false,
            'canCreateTicket' => true,
            'showsManagedTickets' => false,
        ];
    }

    public function managePayload(User $user, array $filters): array
    {
        abort_unless($this->supportManagementService->canManage($user), 403);

        if ($this->isAllOrganizationsContext($user)) {
            $organizationIds = $this->organizationIdsForAllContext($user);

            return [
                'tickets' => $this->supportTicketRepository
                    ->paginateForManagementAcrossOrganizations($organizationIds, $filters)
                    ->through(fn (SupportTicket $ticket) => $this->ticketListItem($ticket)),
                'summary' => $this->supportTicketRepository->getManagementSummaryAcrossOrganizations($organizationIds, $filters),
                'categories' => $this->options($this->supportTicketRepository->getCategories()),
                'priorities' => $this->options($this->supportTicketRepository->getPriorities()),
                'statuses' => $this->options($this->supportTicketRepository->getStatuses()),
                'assignees' => $this->supportTicketRepository
                    ->getAssignableStaffAcrossOrganizations($organizationIds)
                    ->map(fn ($staff) => [
                        'value' => (string) $staff->id,
                        'label' => $staff->name,
                    ])
                    ->values()
                    ->all(),
                'isAllOrganizationsContext' => true,
            ];
        }

        abort_if($user->current_organization_id === null, 403, 'No active organization selected.');

        $organizationId = $user->current_organization_id;

        return [
            'tickets' => $this->supportTicketRepository
                ->paginateForManagement($organizationId, $filters)
                ->through(fn (SupportTicket $ticket) => $this->ticketListItem($ticket)),
            'summary' => $this->supportTicketRepository->getManagementSummary($organizationId, $filters),
            'categories' => $this->options($this->supportTicketRepository->getCategories()),
            'priorities' => $this->options($this->supportTicketRepository->getPriorities()),
            'statuses' => $this->options($this->supportTicketRepository->getStatuses()),
            'assignees' => $this->supportTicketRepository
                ->getAssignableStaff($organizationId)
                ->map(fn ($staff) => [
                    'value' => (string) $staff->id,
                    'label' => $staff->name,
                ])
                ->values()
                ->all(),
            'isAllOrganizationsContext' => false,
        ];
    }

    public function showPayload(User $user, SupportTicket $ticket): array
    {
        $ticket = $this->supportTicketRepository->findWithRelations($ticket->id);

        abort_if($ticket === null, 404);
        abort_unless($this->supportManagementService->canViewTicket($user, $ticket), 403);

        $canManage = $this->supportManagementService->canManageTicket($user, $ticket);

        return [
            'ticket' => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'title' => $ticket->title,
                'category' => $ticket->category,
                'priority' => $ticket->priority,
                'status' => $ticket->status,
                'module_name' => $ticket->module_name,
                'page_url' => $ticket->page_url,
                'details' => $ticket->details,
                'organization_name' => $ticket->organization?->name,
                'creator' => [
                    'name' => $ticket->creator?->name,
                    'email' => $ticket->creator?->email,
                ],
                'assignee' => $ticket->assignee ? [
                    'id' => $ticket->assignee->id,
                    'name' => $ticket->assignee->name,
                    'email' => $ticket->assignee->email,
                ] : null,
                'created_at' => $ticket->created_at->format('M d, Y h:i A'),
                'updated_at_human' => $ticket->updated_at->diffForHumans(),
                'resolved_at' => $ticket->resolved_at?->format('M d, Y h:i A'),
            ],
            'messages' => $ticket->messages->map(fn ($message) => [
                'id' => $message->id,
                'message' => $message->message,
                'created_at' => $message->created_at->format('M d, Y h:i A'),
                'created_at_human' => $message->created_at->diffForHumans(),
                'user' => [
                    'id' => $message->user?->id,
                    'name' => $message->user?->name,
                    'email' => $message->user?->email,
                ],
                'is_current_user' => $message->user_id === $user->id,
            ])->values(),
            'canManage' => $canManage,
            'priorities' => $this->options($this->supportTicketRepository->getPriorities()),
            'statuses' => $this->options($this->supportTicketRepository->getStatuses()),
            'assignees' => $canManage
                ? $this->supportTicketRepository->getAssignableStaff($ticket->organization_id)
                    ->map(fn ($staff) => [
                        'id' => $staff->id,
                        'name' => $staff->name,
                        'email' => $staff->email,
                    ])->values()
                : [],
        ];
    }

    private function ticketListItem(SupportTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'title' => $ticket->title,
            'category' => $ticket->category,
            'priority' => $ticket->priority,
            'status' => $ticket->status,
            'module_name' => $ticket->module_name,
            'organization_name' => $ticket->organization?->name,
            'creator_name' => $ticket->creator?->name,
            'creator_email' => $ticket->creator?->email,
            'assignee_name' => $ticket->assignee?->name,
            'details_preview' => str($ticket->details)->limit(140)->toString(),
            'created_at' => $ticket->created_at->format('M d, Y'),
            'updated_at_human' => $ticket->updated_at->diffForHumans(),
        ];
    }

    private function options(array $values): array
    {
        return collect($values)
            ->map(fn (string $value) => [
                'value' => $value,
                'label' => str($value)->replace('_', ' ')->title()->toString(),
            ])
            ->values()
            ->all();
    }

    private function isAllOrganizationsContext(User $user): bool
    {
        return $user->hasAnyRole(['System Admin', 'BOP'])
            && request()->query('org') === self::ALL_ORGANIZATIONS_SLUG;
    }

    private function organizationIdsForAllContext(User $user): array
    {
        return $user->organizations()
            ->wherePivot('is_active', true)
            ->pluck('organizations.id')
            ->all();
    }
}
