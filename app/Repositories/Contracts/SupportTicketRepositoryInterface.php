<?php

namespace App\Repositories\Contracts;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SupportTicketRepositoryInterface
{
    public function paginateForUser(int $userId, int $organizationId, array $filters, int $perPage = 10): LengthAwarePaginator;

    public function paginateForManagement(int $organizationId, array $filters, int $perPage = 15): LengthAwarePaginator;

    public function getUserSummary(int $userId, int $organizationId): array;

    public function getManagementSummary(int $organizationId, array $filters): array;

    public function create(array $attributes): SupportTicket;

    public function update(SupportTicket $ticket, array $attributes): bool;

    public function createMessage(array $attributes): SupportTicketMessage;

    public function findWithRelations(int $ticketId): ?SupportTicket;

    public function getAssignableStaff(int $organizationId): Collection;

    public function getCategories(): array;

    public function getPriorities(): array;

    public function getStatuses(): array;
}
