<?php

namespace App\Actions\Events;

use App\Models\EventRegistration;
use App\Repositories\Contracts\EventRegistrationRepositoryInterface;

class UpdateEventRegistrationAction
{
    public function __construct(
        private readonly EventRegistrationRepositoryInterface $eventRegistrationRepository,
    ) {}

    public function execute(EventRegistration $registration, array $attributes): bool
    {
        return $this->eventRegistrationRepository->update($registration, $attributes);
    }
}
