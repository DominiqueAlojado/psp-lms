<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Repositories\Contracts\EventRepositoryInterface;

class UpdateEventAction
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepository,
    ) {}

    public function execute(Event $event, array $attributes): bool
    {
        return $this->eventRepository->update($event, $attributes);
    }
}
