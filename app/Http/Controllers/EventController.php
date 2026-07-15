<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelEventRegistrationRequest;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Services\ActivityLog\EventActivityLogService;
use App\Services\EventAttendanceService;
use App\Services\EventManagementService;
use App\Services\EventReadService;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected EventActivityLogService $activityLogService,
        protected EventReadService $eventReadService,
        protected EventManagementService $eventManagementService,
        protected EventAttendanceService $eventAttendanceService,
    ) {}

    /**
     * Display a listing of events (public view for all users).
     */
    public function index(Request $request): Response
    {
        return Inertia::render('events/index', $this->eventReadService->indexPayload(
            $request->user(),
            $request->only(['category', 'type', 'filter', 'search'])
        ));
    }

    /**
     * Display the event details page.
     */
    public function show(Request $request, Event $event): Response
    {
        if (! $this->eventReadService->canViewEvent($request->user(), $event)) {
            abort(403, 'You do not have access to this event.');
        }

        return Inertia::render('events/show', $this->eventReadService->showPayload($request->user(), $event));
    }

    /**
     * Display events management page (for admins).
     */
    public function manage(Request $request): Response
    {
        return Inertia::render('events/manage', $this->eventReadService->managePayload(
            $request->user(),
            $request->only(['status', 'search', 'scope'])
        ));
    }

    /**
     * Store a newly created event.
     */
    public function store(StoreEventRequest $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if (($validated['scope'] ?? 'organization') === 'system' && ! $this->eventManagementService->canCreateSystem($user)) {
            return back()->withErrors(['scope' => 'You do not have permission to publish events across all organizations.']);
        }

        $event = $this->eventManagementService->create(
            $user,
            $validated,
            $request->file('image')
        );

        // Log event creation
        $this->activityLogService->logEventCreated($event);

        return redirect()->route('events.manage')
            ->with('success', 'Event created successfully!');
    }

    /**
     * Update the specified event.
     */
    public function update(UpdateEventRequest $request, Event $event): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if (! $this->eventReadService->canManageEvent($user, $event)) {
            abort(403, 'You do not have access to this event.');
        }

        $validated = $request->validated();

        if (($validated['scope'] ?? 'organization') === 'system' && ! $this->eventManagementService->canCreateSystem($user)) {
            return back()->withErrors(['scope' => 'You do not have permission to publish events across all organizations.']);
        }

        // Capture old values before update
        $oldValues = [
            'title' => $event->title,
            'scope' => $event->scope,
            'description' => $event->description,
            'event_category' => $event->event_category,
            'event_type' => $event->event_type,
            'start_date' => $event->start_date?->format('Y-m-d\TH:i'),
            'end_date' => $event->end_date?->format('Y-m-d\TH:i'),
            'registration_deadline' => $event->registration_deadline?->format('Y-m-d\TH:i'),
            'location' => $event->location,
            'virtual_link' => $event->virtual_link,
            'capacity' => $event->capacity,
            'price' => $event->price,
            'is_free' => $event->is_free,
            'cme_credits' => $event->cme_credits,
            'requires_approval' => $event->requires_approval,
            'is_published' => $event->is_published,
        ];

        // Update event without logging (to avoid duplicate logs)
        $updatedAttributes = [];
        $this->withoutActivityLogging(function () use ($user, $event, $validated, $request, &$updatedAttributes) {
            $updatedAttributes = $this->eventManagementService->update($user, $event, $validated, $request->file('image'));
        });

        // Build log data and log changes
        $logData = $this->activityLogService->buildUpdateLogData($event, $updatedAttributes, $oldValues);
        if (! empty($logData['attributes']) || ! empty($logData['old'])) {
            $this->activityLogService->logEventUpdated($event, $logData['attributes'], $logData['old']);
        }

        return back()->with('success', 'Event updated successfully.');
    }

    /**
     * Remove the specified event.
     */
    public function destroy(Request $request, Event $event): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if (! $this->eventReadService->canManageEvent($user, $event)) {
            abort(403, 'You do not have access to this event.');
        }

        // Check if there are confirmed registrations
        if ($this->eventManagementService->hasConfirmedRegistrations($event)) {
            return back()->with('error', 'Cannot delete event with confirmed registrations.');
        }

        // Log event deletion before deleting
        $this->activityLogService->logEventDeleted($event);

        $this->eventManagementService->delete($event);

        return redirect()->route('events.manage')
            ->with('success', 'Event deleted successfully.');
    }

    /**
     * Register for an event.
     */
    public function register(Request $request, Event $event): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if (! $this->eventReadService->canViewEvent($user, $event)) {
            abort(403, 'You do not have access to this event.');
        }

        $result = $this->eventAttendanceService->register($user, $event);

        return back()->with($result['type'], $result['message']);
    }

    /**
     * Cancel event registration.
     */
    public function cancelRegistration(CancelEventRegistrationRequest $request, Event $event): \Illuminate\Http\RedirectResponse
    {
        if (! $this->eventReadService->canViewEvent($request->user(), $event)) {
            abort(403, 'You do not have access to this event.');
        }

        $result = $this->eventAttendanceService->cancelRegistration(
            $request->user(),
            $event,
            $request->validated()['reason'] ?? null
        );

        return back()->with($result['type'], $result['message']);
    }

    /**
     * View attendees for an event (admin).
     */
    public function attendees(Request $request, Event $event): Response
    {
        if (! $this->eventReadService->canManageEvent($request->user(), $event)) {
            abort(403, 'You do not have access to this event.');
        }

        return Inertia::render('events/attendees', $this->eventReadService->attendeesPayload(
            $event,
            $request->only(['status', 'search'])
        ));
    }

    /**
     * View meeting attendance with metadata for virtual/hybrid events.
     */
    public function meetingAttendance(Request $request, Event $event): Response
    {
        if (! $this->eventReadService->canManageEvent($request->user(), $event)) {
            abort(403, 'You do not have access to this event.');
        }

        // Only show for virtual or hybrid events
        if (! in_array($event->event_type, ['virtual', 'hybrid'])) {
            return back()->with('error', 'Meeting attendance tracking is only available for virtual and hybrid events.');
        }

        return Inertia::render('events/meeting-attendance', $this->eventReadService->meetingAttendancePayload(
            $event,
            $request->only(['status', 'search', 'attendance_type'])
        ));
    }

    /**
     * Approve a pending registration.
     */
    public function approveRegistration(Request $request, Event $event, EventRegistration $registration): \Illuminate\Http\RedirectResponse
    {
        if (! $this->eventReadService->canManageRegistration($request->user(), $event, $registration)) {
            abort(403);
        }

        $this->eventAttendanceService->approveRegistration($registration);

        return back()->with('success', 'Registration approved successfully.');
    }

    /**
     * Get activity logs for an event.
     */
    public function logs(Request $request, Event $event): JsonResponse
    {
        if (! $this->eventReadService->canManageEvent($request->user(), $event)) {
            abort(403, 'You do not have access to this event.');
        }

        $logs = $this->activityLogService->getLogs($event);

        return response()->json([
            'logs' => $logs,
        ]);
    }

    /**
     * My registrations page.
     */
    public function myRegistrations(Request $request): Response
    {
        return Inertia::render('events/my-registrations', $this->eventReadService->myRegistrationsPayload(
            $request->user(),
            $request->only(['status'])
        ));
    }

    /**
     * Join meeting - record attendance start.
     */
    public function joinMeeting(Request $request, Event $event): \Illuminate\Http\JsonResponse
    {
        if (! $this->eventReadService->canViewEvent($request->user(), $event)) {
            abort(403, 'You do not have access to this event.');
        }

        $result = $this->eventAttendanceService->joinMeeting($request, $event);

        return response()->json($result['payload'], $result['status']);
    }

    /**
     * Update meeting heartbeat - track active status.
     */
    public function meetingHeartbeat(Request $request, Event $event): \Illuminate\Http\JsonResponse
    {
        if (! $this->eventReadService->canViewEvent($request->user(), $event)) {
            abort(403, 'You do not have access to this event.');
        }

        $result = $this->eventAttendanceService->meetingHeartbeat($request->user(), $event);

        return response()->json($result['payload'], $result['status']);
    }

    /**
     * Leave meeting - record attendance end.
     */
    public function leaveMeeting(Request $request, Event $event): \Illuminate\Http\JsonResponse
    {
        if (! $this->eventReadService->canViewEvent($request->user(), $event)) {
            abort(403, 'You do not have access to this event.');
        }

        $result = $this->eventAttendanceService->leaveMeeting($request->user(), $event);

        return response()->json($result['payload'], $result['status']);
    }
}
