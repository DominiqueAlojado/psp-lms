<?php

namespace App\Services;

use App\Actions\Events\CreateEventRegistrationAction;
use App\Actions\Events\CreateMeetingAttendanceAction;
use App\Actions\Events\UpdateEventRegistrationAction;
use App\Actions\Events\UpdateMeetingAttendanceAction;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use App\Repositories\Contracts\EventRegistrationRepositoryInterface;
use App\Repositories\Contracts\MeetingAttendanceRepositoryInterface;
use Illuminate\Http\Request;

class EventAttendanceService
{
    public function __construct(
        private readonly EventRegistrationRepositoryInterface $eventRegistrationRepository,
        private readonly MeetingAttendanceRepositoryInterface $meetingAttendanceRepository,
        private readonly CreateEventRegistrationAction $createEventRegistrationAction,
        private readonly UpdateEventRegistrationAction $updateEventRegistrationAction,
        private readonly CreateMeetingAttendanceAction $createMeetingAttendanceAction,
        private readonly UpdateMeetingAttendanceAction $updateMeetingAttendanceAction,
    ) {}

    public function register(User $user, Event $event): array
    {
        $organizationId = $user->currentOrganization?->id;

        if (! $event->is_published) {
            return ['type' => 'error', 'message' => 'This event is not available for registration.'];
        }

        if (! $event->isRegistrationOpen()) {
            return ['type' => 'error', 'message' => 'Registration is not currently open for this event.'];
        }

        $existingRegistration = $this->eventRegistrationRepository->findUserRegistrationForEvent($event, $user->id);
        if ($existingRegistration) {
            return ['type' => 'error', 'message' => 'You are already registered for this event.'];
        }

        $paymentAmount = $event->is_free ? 0 : $event->price;

        if ($event->isFull()) {
            $this->createEventRegistrationAction->execute([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'organization_id' => $organizationId,
                'registration_status' => 'waitlisted',
                'payment_status' => 'not_required',
                'payment_amount' => $paymentAmount,
            ]);

            return ['type' => 'info', 'message' => 'Event is full. You have been added to the waitlist.'];
        }

        $status = $event->requires_approval ? 'pending' : 'confirmed';
        $paymentStatus = $event->is_free ? 'not_required' : 'pending';

        $this->createEventRegistrationAction->execute([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'organization_id' => $organizationId,
            'registration_status' => $status,
            'payment_status' => $paymentStatus,
            'payment_amount' => $paymentAmount,
        ]);

        return [
            'type' => 'success',
            'message' => $event->requires_approval
                ? 'Registration submitted. Awaiting approval from organizers.'
                : 'Successfully registered for the event!',
        ];
    }

    public function cancelRegistration(User $user, Event $event, ?string $reason): array
    {
        $registration = $event->registrations()->where('user_id', $user->id)->first();

        if (! $registration) {
            return ['type' => 'error', 'message' => 'Registration not found.'];
        }

        $registration->cancel($reason);

        return ['type' => 'success', 'message' => 'Registration cancelled successfully.'];
    }

    public function approveRegistration(EventRegistration $registration): bool
    {
        return $this->updateEventRegistrationAction->execute($registration, [
            'registration_status' => 'approved',
        ]);
    }

    public function joinMeeting(Request $request, Event $event): array
    {
        $user = $request->user();
        $organizationId = $user->currentOrganization?->id;

        $now = now();
        if ($now->isBefore($event->start_date) || $now->isAfter($event->end_date)) {
            return ['status' => 403, 'payload' => ['error' => 'Event is not currently live.']];
        }

        $registration = $this->eventRegistrationRepository->findUserRegistrationForEvent($event, $user->id);
        $existingAttendance = $this->meetingAttendanceRepository->findActiveForUserAndEvent($user->id, $event->id);

        if ($existingAttendance) {
            $existingAttendance->updateLastSeen();

            return [
                'status' => 200,
                'payload' => [
                    'success' => true,
                    'attendance_id' => $existingAttendance->id,
                    'message' => 'Attendance updated.',
                ],
            ];
        }

        $attendance = $this->createMeetingAttendanceAction->execute([
            'event_id' => $event->id,
            'event_registration_id' => $registration?->id,
            'user_id' => $user->id,
            'organization_id' => $organizationId,
            'joined_at' => now(),
            'last_seen_at' => now(),
            'status' => 'joined',
            'metadata' => $this->buildAttendanceMetadata($request, $event),
        ]);

        return [
            'status' => 200,
            'payload' => [
                'success' => true,
                'attendance_id' => $attendance->id,
                'message' => 'Attendance recorded.',
            ],
        ];
    }

    public function meetingHeartbeat(User $user, Event $event): array
    {
        $attendance = $this->meetingAttendanceRepository->findActiveForUserAndEvent($user->id, $event->id);

        if (! $attendance) {
            return ['status' => 404, 'payload' => ['error' => 'No active attendance found.']];
        }

        $inactiveThreshold = now()->subMinutes(5);
        if ($attendance->last_seen_at && $attendance->last_seen_at->isBefore($inactiveThreshold)) {
            $this->updateMeetingAttendanceAction->execute($attendance, [
                'status' => 'timeout',
                'left_at' => $attendance->last_seen_at,
            ]);
            $attendance->calculateDuration();

            return [
                'status' => 200,
                'payload' => [
                    'success' => false,
                    'status' => 'timeout',
                    'message' => 'Attendance timed out due to inactivity.',
                ],
            ];
        }

        $attendance->updateLastSeen();

        return [
            'status' => 200,
            'payload' => [
                'success' => true,
                'status' => $attendance->status,
                'last_seen_at' => $attendance->last_seen_at->toISOString(),
            ],
        ];
    }

    public function leaveMeeting(User $user, Event $event): array
    {
        $attendance = $this->meetingAttendanceRepository->findActiveForUserAndEvent($user->id, $event->id);

        if (! $attendance) {
            return ['status' => 404, 'payload' => ['error' => 'No active attendance found.']];
        }

        $attendance->markAsLeft();

        return [
            'status' => 200,
            'payload' => [
                'success' => true,
                'duration_seconds' => $attendance->duration_seconds,
                'message' => 'Attendance ended.',
            ],
        ];
    }

    private function buildAttendanceMetadata(Request $request, Event $event): array
    {
        $clientMetadata = $request->input('metadata');

        $metadata = [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        if ($clientMetadata && is_array($clientMetadata)) {
            if (isset($clientMetadata['browser_metadata'])) {
                $metadata['browser_metadata'] = $clientMetadata['browser_metadata'];
            }
            if (isset($clientMetadata['connection_type'])) {
                $metadata['connection_type'] = $clientMetadata['connection_type'];
            }
            if (isset($clientMetadata['connection_speed'])) {
                $metadata['connection_speed'] = $clientMetadata['connection_speed'];
            }
            if (isset($clientMetadata['user_agent'])) {
                $metadata['user_agent'] = $clientMetadata['user_agent'];
            }
            if (isset($clientMetadata['attendance_type'])) {
                $metadata['attendance_type'] = $clientMetadata['attendance_type'];
            } elseif ($event->event_type === 'hybrid') {
                $metadata['attendance_type'] = 'virtual';
            }

            return $metadata;
        }

        $metadata['device'] = $this->getDeviceInfo($request);
        $metadata['browser'] = $this->getBrowserInfo($request);
        if ($event->event_type === 'hybrid') {
            $metadata['attendance_type'] = 'in-person';
        }

        return $metadata;
    }

    private function getDeviceInfo(Request $request): string
    {
        $userAgent = $request->userAgent() ?? '';

        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            return 'mobile';
        }

        if (preg_match('/Tablet|iPad/', $userAgent)) {
            return 'tablet';
        }

        return 'desktop';
    }

    private function getBrowserInfo(Request $request): string
    {
        $userAgent = $request->userAgent() ?? '';

        if (preg_match('/Chrome/', $userAgent)) {
            return 'Chrome';
        }
        if (preg_match('/Firefox/', $userAgent)) {
            return 'Firefox';
        }
        if (preg_match('/Safari/', $userAgent)) {
            return 'Safari';
        }
        if (preg_match('/Edge/', $userAgent)) {
            return 'Edge';
        }

        return 'Unknown';
    }
}
