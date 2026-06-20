<?php

namespace App\Repositories\Contracts;

use App\Models\Event;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EventRepositoryInterface
{
    public function paginatePublished(array $filters, int $perPage = 12): LengthAwarePaginator;

    public function attachUserRegistrations(LengthAwarePaginator $events, User $user): LengthAwarePaginator;

    public function loadEventDetails(Event $event): Event;

    public function getRegistrationStats(Event $event): array;

    public function paginateForManagement(?int $organizationId, bool $canManageAll, array $filters, int $perPage = 15): LengthAwarePaginator;

    public function create(array $attributes): Event;

    public function update(Event $event, array $attributes): bool;

    public function delete(Event $event): bool;

    public function hasConfirmedRegistrations(Event $event): bool;
}
