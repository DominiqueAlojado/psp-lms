<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Repositories\Contracts\EventRepositoryInterface;

class CreateEventAction
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepository,
    ) {}

    public function execute(array $attributes): Event
    {
        return $this->eventRepository->create($attributes);
    }
}
