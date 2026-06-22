<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Repositories\Contracts\EventRepositoryInterface;

class DeleteEventAction
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepository,
    ) {}

    public function execute(Event $event): bool
    {
        return $this->eventRepository->delete($event);
    }
}
