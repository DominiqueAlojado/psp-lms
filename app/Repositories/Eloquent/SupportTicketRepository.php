<?php

namespace App\Repositories\Eloquent;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Repositories\Contracts\SupportTicketRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SupportTicketRepository implements SupportTicketRepositoryInterface
{
    private function applyManagementFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $builder, string $search) {
                $builder->where(function (Builder $nestedQuery) use ($search) {
                    $nestedQuery->where('ticket_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('details', 'like', "%{$search}%")
                        ->orWhereHas('creator', function (Builder $creatorQuery) use ($search) {
                            $creatorQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('assignee', function (Builder $assigneeQuery) use ($search) {
                            $assigneeQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['status'] ?? null, function (Builder $builder, string $status) {
                $builder->where('status', $status);
            })
            ->when($filters['priority'] ?? null, function (Builder $builder, string $priority) {
                $builder->where('priority', $priority);
            })
            ->when($filters['category'] ?? null, function (Builder $builder, string $category) {
                $builder->where('category', $category);
            })
            ->when($filters['organization_id'] ?? null, function (Builder $builder, string|int $organizationId) {
                $builder->where('organization_id', (int) $organizationId);
            })
            ->when($filters['assignee_user_id'] ?? null, function (Builder $builder, string|int $assigneeUserId) {
                if ($assigneeUserId === 'unassigned') {
                    $builder->whereNull('assigned_to_user_id');

                    return;
                }

                $builder->where('assigned_to_user_id', (int) $assigneeUserId);
            });
    }

    public function paginateForUser(int $userId, int $organizationId, array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return SupportTicket::query()
            ->with([
                'organization:id,name',
                'creator:id,name,email',
                'assignee:id,name,email',
            ])
            ->where('user_id', $userId)
            ->where('organization_id', $organizationId)
            ->when($filters['status'] ?? null, function (Builder $query, string $status) {
                $query->where('status', $status);
            })
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateForUserAcrossOrganizations(int $userId, array $organizationIds, array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return SupportTicket::query()
            ->with([
                'organization:id,name',
                'creator:id,name,email',
                'assignee:id,name,email',
            ])
            ->where('user_id', $userId)
            ->whereIn('organization_id', $organizationIds)
            ->when($filters['organization_id'] ?? null, function (Builder $query, string|int $organizationId) {
                $query->where('organization_id', (int) $organizationId);
            })
            ->when($filters['status'] ?? null, function (Builder $query, string $status) {
                $query->where('status', $status);
            })
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateForManagement(int $organizationId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = SupportTicket::query()
            ->with([
                'organization:id,name',
                'creator:id,name,email',
                'assignee:id,name,email',
            ])
            ->where('organization_id', $organizationId)
            ->orderByRaw("
                CASE status
                    WHEN 'open' THEN 1
                    WHEN 'in_review' THEN 2
                    WHEN 'resolved' THEN 3
                    ELSE 4
                END
            ")
            ->orderByDesc('updated_at')
            ;

        return $this->applyManagementFilters($query, $filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateForManagementAcrossOrganizations(array $organizationIds, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = SupportTicket::query()
            ->with([
                'organization:id,name',
                'creator:id,name,email',
                'assignee:id,name,email',
            ])
            ->whereIn('organization_id', $organizationIds)
            ->orderByRaw("
                CASE status
                    WHEN 'open' THEN 1
                    WHEN 'in_review' THEN 2
                    WHEN 'resolved' THEN 3
                    ELSE 4
                END
            ")
            ->orderByDesc('updated_at')
            ;

        return $this->applyManagementFilters($query, $filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getUserSummary(int $userId, int $organizationId): array
    {
        $query = SupportTicket::query()
            ->where('user_id', $userId)
            ->where('organization_id', $organizationId);

        return [
            'total' => (clone $query)->count(),
            'open' => (clone $query)->where('status', 'open')->count(),
            'in_review' => (clone $query)->where('status', 'in_review')->count(),
            'resolved' => (clone $query)->where('status', 'resolved')->count(),
        ];
    }

    public function getUserSummaryAcrossOrganizations(int $userId, array $organizationIds, array $filters = []): array
    {
        $query = SupportTicket::query()
            ->where('user_id', $userId)
            ->whereIn('organization_id', $organizationIds)
            ->when($filters['organization_id'] ?? null, function (Builder $builder, string|int $organizationId) {
                $builder->where('organization_id', (int) $organizationId);
            });

        return [
            'total' => (clone $query)->count(),
            'open' => (clone $query)->where('status', 'open')->count(),
            'in_review' => (clone $query)->where('status', 'in_review')->count(),
            'resolved' => (clone $query)->where('status', 'resolved')->count(),
        ];
    }

    public function getManagementSummary(int $organizationId, array $filters): array
    {
        $query = $this->applyManagementFilters(
            SupportTicket::query()->where('organization_id', $organizationId),
            collect($filters)->except('status')->all(),
        );

        return [
            'total' => (clone $query)->count(),
            'open' => (clone $query)->where('status', 'open')->count(),
            'in_review' => (clone $query)->where('status', 'in_review')->count(),
            'resolved' => (clone $query)->where('status', 'resolved')->count(),
        ];
    }

    public function getManagementSummaryAcrossOrganizations(array $organizationIds, array $filters): array
    {
        $query = $this->applyManagementFilters(
            SupportTicket::query()->whereIn('organization_id', $organizationIds),
            collect($filters)->except('status')->all(),
        );

        return [
            'total' => (clone $query)->count(),
            'open' => (clone $query)->where('status', 'open')->count(),
            'in_review' => (clone $query)->where('status', 'in_review')->count(),
            'resolved' => (clone $query)->where('status', 'resolved')->count(),
        ];
    }

    public function create(array $attributes): SupportTicket
    {
        return SupportTicket::create($attributes);
    }

    public function update(SupportTicket $ticket, array $attributes): bool
    {
        return $ticket->update($attributes);
    }

    public function createMessage(array $attributes): SupportTicketMessage
    {
        return SupportTicketMessage::create($attributes);
    }

    public function findWithRelations(int $ticketId): ?SupportTicket
    {
        return SupportTicket::query()
            ->with([
                'organization:id,name',
                'creator:id,name,email',
                'assignee:id,name,email',
                'messages.user:id,name,email',
            ])
            ->find($ticketId);
    }

    public function getAssignableStaff(int $organizationId): Collection
    {
        return User::query()
            ->where('current_organization_id', $organizationId)
            ->whereDoesntHave('roles', function (Builder $query) {
                $query->where('name', 'Resident');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    public function getAssignableStaffAcrossOrganizations(array $organizationIds): Collection
    {
        return User::query()
            ->whereIn('current_organization_id', $organizationIds)
            ->whereDoesntHave('roles', function (Builder $query) {
                $query->where('name', 'Resident');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->unique('id')
            ->values();
    }

    public function getCategories(): array
    {
        return ['bug', 'billing', 'content', 'account', 'feature'];
    }

    public function getPriorities(): array
    {
        return ['low', 'medium', 'high'];
    }

    public function getStatuses(): array
    {
        return ['open', 'in_review', 'resolved'];
    }
}
