<?php

namespace App\Repositories\Contracts;

use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EventRegistrationRepositoryInterface
{
    public function findUserRegistrationForEvent(Event $event, int $userId): ?EventRegistration;

    public function create(array $attributes): EventRegistration;

    public function paginateAttendeesForEvent(Event $event, array $filters, int $perPage = 20): LengthAwarePaginator;

    public function update(EventRegistration $registration, array $attributes): bool;

    public function paginateForUser(int $userId, array $filters, int $perPage = 15): LengthAwarePaginator;
}
