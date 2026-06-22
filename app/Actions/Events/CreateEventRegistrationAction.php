<?php

namespace App\Actions\Events;

use App\Models\EventRegistration;
use App\Repositories\Contracts\EventRegistrationRepositoryInterface;

class CreateEventRegistrationAction
{
    public function __construct(
        private readonly EventRegistrationRepositoryInterface $eventRegistrationRepository,
    ) {}

    public function execute(array $attributes): EventRegistration
    {
        return $this->eventRegistrationRepository->create($attributes);
    }
}
