<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use App\Repositories\Contracts\EventRegistrationRepositoryInterface;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\MeetingAttendanceRepositoryInterface;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class EventReadService
{
    private const ALL_ORGANIZATIONS_SLUG = 'all-organizations';

    public function __construct(
        private readonly EventRepositoryInterface $eventRepository,
        private readonly EventRegistrationRepositoryInterface $eventRegistrationRepository,
        private readonly MeetingAttendanceRepositoryInterface $meetingAttendanceRepository,
    ) {}

    public function indexPayload(User $user, array $filters): array
    {
        $includeAllOrganizations = $this->includeAllOrganizations($user);
        $events = $this->eventRepository->paginatePublished(
            $user->current_organization_id,
            $filters,
            includeAllOrganizations: $includeAllOrganizations
        );
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
        try {
            $canCreateSystem = $user->hasPermissionTo('create-system-announcements')
                || $user->hasAnyRole(['System Admin', 'BOP']);
        } catch (PermissionDoesNotExist) {
            $canCreateSystem = $user->hasAnyRole(['System Admin', 'BOP']);
        }

        return [
            'events' => $this->eventRepository->paginateForManagement(
                $user->currentOrganization?->id,
                $user->hasAnyRole(['System Admin', 'BOP']) || $canCreateSystem,
                $filters
            ),
            'filters' => $filters,
            'canCreateSystem' => $canCreateSystem,
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
        if ($this->includeAllOrganizations($user) && $user->hasAnyRole(['System Admin', 'BOP'])) {
            return true;
        }

        if ($event->scope === 'system') {
            try {
                return $user->hasPermissionTo('create-system-announcements')
                    || $user->hasAnyRole(['System Admin', 'BOP']);
            } catch (PermissionDoesNotExist) {
                return $user->hasAnyRole(['System Admin', 'BOP']);
            }
        }

        return $user->hasAnyRole(['System Admin', 'BOP'])
            || $event->organization_id === $user->currentOrganization?->id;
    }

    public function canViewEvent(User $user, Event $event): bool
    {
        if ($this->includeAllOrganizations($user) && $user->hasAnyRole(['System Admin', 'BOP'])) {
            return true;
        }

        if ($event->scope === 'system') {
            return true;
        }

        return $event->organization_id === $user->currentOrganization?->id;
    }

    public function canManageRegistration(User $user, Event $event, \App\Models\EventRegistration $registration): bool
    {
        return $this->canManageEvent($user, $event) && $registration->event_id === $event->id;
    }

    private function includeAllOrganizations(User $user): bool
    {
        return request()->query('org') === self::ALL_ORGANIZATIONS_SLUG
            && $user->hasAnyRole(['System Admin', 'BOP']);
    }
}
