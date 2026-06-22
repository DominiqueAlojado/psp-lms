<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use App\Repositories\Contracts\EventRegistrationRepositoryInterface;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\MeetingAttendanceRepositoryInterface;

class EventReadService
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepository,
        private readonly EventRegistrationRepositoryInterface $eventRegistrationRepository,
        private readonly MeetingAttendanceRepositoryInterface $meetingAttendanceRepository,
    ) {}

    public function indexPayload(User $user, array $filters): array
    {
        $events = $this->eventRepository->paginatePublished($filters);
        $events = $this->eventRepository->attachUserRegistrations($events, $user);

        return [
            'events' => $events,
            'filters' => $filters,
        ];
    }

    public function showPayload(User $user, Event $event): array
    {
        $event = $this->eventRepository->loadEventDetails($event);
        $userRegistration = $this->eventRegistrationRepository->findUserRegistrationForEvent($event, $user->id);

        return [
            'event' => $event,
            'userRegistration' => $userRegistration,
            'registrationStats' => $this->eventRepository->getRegistrationStats($event),
            'canRegister' => $event->isRegistrationOpen() && ! $event->isFull() && ! $userRegistration,
        ];
    }

    public function managePayload(User $user, array $filters): array
    {
        return [
            'events' => $this->eventRepository->paginateForManagement(
                $user->currentOrganization?->id,
                $user->hasAnyRole(['System Admin', 'BOP']),
                $filters
            ),
            'filters' => $filters,
        ];
    }

    public function attendeesPayload(Event $event, array $filters): array
    {
        return [
            'event' => $event,
            'registrations' => $this->eventRegistrationRepository->paginateAttendeesForEvent($event, $filters),
            'filters' => $filters,
        ];
    }

    public function meetingAttendancePayload(Event $event, array $filters): array
    {
        return [
            'event' => $event,
            'attendances' => $this->meetingAttendanceRepository->paginateForEvent($event, $filters),
            'stats' => $this->meetingAttendanceRepository->getStatsForEvent($event),
            'filters' => $filters,
        ];
    }

    public function myRegistrationsPayload(User $user, array $filters): array
    {
        return [
            'registrations' => $this->eventRegistrationRepository->paginateForUser($user->id, $filters),
            'filters' => $filters,
        ];
    }

    public function canManageEvent(User $user, Event $event): bool
    {
        return $user->hasAnyRole(['System Admin', 'BOP'])
            || $event->organization_id === $user->currentOrganization?->id;
    }

    public function canManageRegistration(User $user, Event $event, \App\Models\EventRegistration $registration): bool
    {
        return $this->canManageEvent($user, $event) && $registration->event_id === $event->id;
    }
}
