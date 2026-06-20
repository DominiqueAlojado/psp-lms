<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MeetingAttendance;
use App\Repositories\Contracts\EventRegistrationRepositoryInterface;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\MeetingAttendanceRepositoryInterface;
use App\Services\ActivityLog\EventActivityLogService;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected EventActivityLogService $activityLogService,
        protected EventRepositoryInterface $eventRepository,
        protected EventRegistrationRepositoryInterface $eventRegistrationRepository,
        protected MeetingAttendanceRepositoryInterface $meetingAttendanceRepository
    ) {}

    /**
     * Display a listing of events (public view for all users).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $events = $this->eventRepository->paginatePublished(
            $request->only(['category', 'type', 'filter', 'search'])
        );
        $events = $this->eventRepository->attachUserRegistrations($events, $user);

        return Inertia::render('events/index', [
            'events' => $events,
            'filters' => $request->only(['category', 'type', 'filter', 'search']),
        ]);
    }

    /**
     * Display the event details page.
     */
    public function show(Request $request, Event $event): Response
    {
        $user = $request->user();

        $event = $this->eventRepository->loadEventDetails($event);
        $userRegistration = $this->eventRegistrationRepository->findUserRegistrationForEvent($event, $user->id);
        $registrationStats = $this->eventRepository->getRegistrationStats($event);

        return Inertia::render('events/show', [
            'event' => $event,
            'userRegistration' => $userRegistration,
            'registrationStats' => $registrationStats,
            'canRegister' => $event->isRegistrationOpen() && ! $event->isFull() && ! $userRegistration,
        ]);
    }

    /**
     * Display events management page (for admins).
     */
    public function manage(Request $request): Response
    {
        $user = $request->user();
        $organizationId = $user->currentOrganization?->id;

        $events = $this->eventRepository->paginateForManagement(
            $organizationId,
            $user->hasAnyRole(['System Admin', 'BOP']),
            $request->only(['status', 'search'])
        );

        return Inertia::render('events/manage', [
            'events' => $events,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    /**
     * Store a newly created event.
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        $organizationId = $user->currentOrganization?->id;

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'event_category' => ['required', 'in:convention,workshop,seminar,cme,conference,symposium,training,other'],
            'event_type' => ['required', 'in:in-person,virtual,hybrid'],
            'start_date' => ['required', 'date', 'after:now'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'registration_deadline' => ['nullable', 'date', 'before:start_date'],
            'location' => ['nullable', 'string', 'max:255'],
            'virtual_link' => ['nullable', 'url', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_free' => ['boolean'],
            'image' => ['nullable', 'image', 'max:5120', 'mimes:jpeg,jpg,png,gif,webp'], // 5MB max
            'cme_credits' => ['nullable', 'numeric', 'min:0'],
            'target_year_levels' => ['nullable', 'array'],
            'requirements' => ['nullable', 'string'],
            'requires_approval' => ['boolean'],
            'is_published' => ['boolean'],
        ]);

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $imagePath = $file->storeAs('event-posters', $fileName, 'public');
        }

        $event = $this->eventRepository->create([
            ...$validated,
            'image_path' => $imagePath,
            'organization_id' => $organizationId,
            'created_by' => $user->id,
        ]);

        // Log event creation
        $this->activityLogService->logEventCreated($event);

        return redirect()->route('events.manage')
            ->with('success', 'Event created successfully!');
    }

    /**
     * Update the specified event.
     */
    public function update(Request $request, Event $event): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        $organizationId = $user->currentOrganization?->id;

        // Check access (System Admins and BOP can access all events)
        if (! $user->hasAnyRole(['System Admin', 'BOP']) && $event->organization_id !== $organizationId) {
            abort(403, 'You do not have access to this event.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'event_category' => ['required', 'in:convention,workshop,seminar,cme,conference,symposium,training,other'],
            'event_type' => ['required', 'in:in-person,virtual,hybrid'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'registration_deadline' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'virtual_link' => ['nullable', 'url', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_free' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:5120', 'mimes:jpeg,jpg,png,gif,webp'],
            'cme_credits' => ['nullable', 'numeric', 'min:0'],
            'target_year_levels' => ['nullable', 'array'],
            'requirements' => ['nullable', 'string'],
            'requires_approval' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        // Capture old values before update
        $oldValues = [
            'title' => $event->title,
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

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($event->image_path && Storage::disk('public')->exists($event->image_path)) {
                Storage::disk('public')->delete($event->image_path);
            }

            // Store new image
            $file = $request->file('image');
            $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $validated['image_path'] = $file->storeAs('event-posters', $fileName, 'public');
        }

        // Update event without logging (to avoid duplicate logs)
        $this->withoutActivityLogging(function () use ($event, $validated) {
            $this->eventRepository->update($event, $validated);
        });

        // Build log data and log changes
        $logData = $this->activityLogService->buildUpdateLogData($event, $validated, $oldValues);
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
        $organizationId = $user->currentOrganization?->id;

        // Check access (System Admins and BOP can delete all events)
        if (! $user->hasAnyRole(['System Admin', 'BOP']) && $event->organization_id !== $organizationId) {
            abort(403, 'You do not have access to this event.');
        }

        // Check if there are confirmed registrations
        if ($this->eventRepository->hasConfirmedRegistrations($event)) {
            return back()->with('error', 'Cannot delete event with confirmed registrations.');
        }

        // Log event deletion before deleting
        $this->activityLogService->logEventDeleted($event);

        $this->eventRepository->delete($event);

        return redirect()->route('events.manage')
            ->with('success', 'Event deleted successfully.');
    }

    /**
     * Register for an event.
     */
    public function register(Request $request, Event $event): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        $organizationId = $user->currentOrganization?->id;

        // Check if event is published
        if (! $event->is_published) {
            return back()->with('error', 'This event is not available for registration.');
        }

        // Check if registration is open
        if (! $event->isRegistrationOpen()) {
            return back()->with('error', 'Registration is not currently open for this event.');
        }

        // Check if already registered
        $existingRegistration = $this->eventRegistrationRepository->findUserRegistrationForEvent($event, $user->id);

        if ($existingRegistration) {
            return back()->with('error', 'You are already registered for this event.');
        }

        // Check capacity
        if ($event->isFull()) {
            // Add to waitlist if capacity is full
            // Determine payment amount for waitlisted
            $paymentAmount = $event->is_free ? 0 : $event->price;

            $this->eventRegistrationRepository->create([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'organization_id' => $organizationId,
                'registration_status' => 'waitlisted',
                'payment_status' => 'not_required', // Payment only required when confirmed
                'payment_amount' => $paymentAmount,
            ]);

            return back()->with('info', 'Event is full. You have been added to the waitlist.');
        }

        // Determine initial status
        $status = $event->requires_approval ? 'pending' : 'confirmed';

        // Determine payment status and amount
        $paymentStatus = $event->is_free ? 'not_required' : 'pending';
        $paymentAmount = $event->is_free ? 0 : $event->price;

        // Create registration
        $this->eventRegistrationRepository->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'organization_id' => $organizationId,
            'registration_status' => $status,
            'payment_status' => $paymentStatus,
            'payment_amount' => $paymentAmount,
        ]);

        $message = $event->requires_approval
            ? 'Registration submitted. Awaiting approval from organizers.'
            : 'Successfully registered for the event!';

        return back()->with('success', $message);
    }

    /**
     * Cancel event registration.
     */
    public function cancelRegistration(Request $request, Event $event): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        $registration = $event->registrations()
            ->where('user_id', $user->id)
            ->first();

        if (! $registration) {
            return back()->with('error', 'Registration not found.');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $registration->cancel($validated['reason'] ?? null);

        return back()->with('success', 'Registration cancelled successfully.');
    }

    /**
     * View attendees for an event (admin).
     */
    public function attendees(Request $request, Event $event): Response
    {
        $user = $request->user();
        $organizationId = $user->currentOrganization?->id;

        // Check access (System Admins and BOP can view all event attendees)
        if (! $user->hasAnyRole(['System Admin', 'BOP']) && $event->organization_id !== $organizationId) {
            abort(403, 'You do not have access to this event.');
        }

        $registrations = $this->eventRegistrationRepository->paginateAttendeesForEvent(
            $event,
            $request->only(['status', 'search'])
        );

        return Inertia::render('events/attendees', [
            'event' => $event,
            'registrations' => $registrations,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    /**
     * View meeting attendance with metadata for virtual/hybrid events.
     */
    public function meetingAttendance(Request $request, Event $event): Response
    {
        $user = $request->user();
        $organizationId = $user->currentOrganization?->id;

        // Check access (System Admins and BOP can view all event attendance)
        if (! $user->hasAnyRole(['System Admin', 'BOP']) && $event->organization_id !== $organizationId) {
            abort(403, 'You do not have access to this event.');
        }

        // Only show for virtual or hybrid events
        if (! in_array($event->event_type, ['virtual', 'hybrid'])) {
            return back()->with('error', 'Meeting attendance tracking is only available for virtual and hybrid events.');
        }

        $attendances = $this->meetingAttendanceRepository->paginateForEvent(
            $event,
            $request->only(['status', 'search', 'attendance_type'])
        );
        $stats = $this->meetingAttendanceRepository->getStatsForEvent($event);

        return Inertia::render('events/meeting-attendance', [
            'event' => $event,
            'attendances' => $attendances,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search', 'attendance_type']),
        ]);
    }

    /**
     * Approve a pending registration.
     */
    public function approveRegistration(Request $request, Event $event, EventRegistration $registration): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        $organizationId = $user->currentOrganization?->id;

        // Check access (System Admins and BOP can approve any registration)
        if (! $user->hasAnyRole(['System Admin', 'BOP']) && $event->organization_id !== $organizationId) {
            abort(403);
        }

        if ($registration->event_id !== $event->id) {
            abort(403);
        }

        $this->eventRegistrationRepository->update($registration, ['registration_status' => 'approved']);

        return back()->with('success', 'Registration approved successfully.');
    }

    /**
     * Get activity logs for an event.
     */
    public function logs(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();
        $organizationId = $user->currentOrganization?->id;

        // Check access (System Admins and BOP can view all event logs)
        if (! $user->hasAnyRole(['System Admin', 'BOP']) && $event->organization_id !== $organizationId) {
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
        $user = $request->user();

        $registrations = $this->eventRegistrationRepository->paginateForUser(
            $user->id,
            $request->only(['status'])
        );

        return Inertia::render('events/my-registrations', [
            'registrations' => $registrations,
            'filters' => $request->only(['status']),
        ]);
    }

    /**
     * Join meeting - record attendance start.
     */
    public function joinMeeting(Request $request, Event $event): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $organizationId = $user->currentOrganization?->id;

        // Check if event is live (started and not ended)
        $now = now();
        if ($now->isBefore($event->start_date) || $now->isAfter($event->end_date)) {
            return response()->json(['error' => 'Event is not currently live.'], 403);
        }

        // Check if user is registered (optional - can track without registration)
        $registration = $this->eventRegistrationRepository->findUserRegistrationForEvent($event, $user->id);

        // Check for existing active attendance
        $existingAttendance = $this->meetingAttendanceRepository->findActiveForUserAndEvent($user->id, $event->id);

        if ($existingAttendance) {
            // Update existing attendance
            $existingAttendance->updateLastSeen();

            return response()->json([
                'success' => true,
                'attendance_id' => $existingAttendance->id,
                'message' => 'Attendance updated.',
            ]);
        }

        // Create new attendance record with comprehensive metadata
        $clientMetadata = $request->input('metadata');

        // Build metadata object with server-side and client-side data
        $metadata = [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        // Add client-captured metadata if available (for virtual/hybrid events)
        if ($clientMetadata && is_array($clientMetadata)) {
            // Merge client metadata (browser, connection, etc.)
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
            // For hybrid events, track if joining virtually or in-person
            if (isset($clientMetadata['attendance_type'])) {
                $metadata['attendance_type'] = $clientMetadata['attendance_type'];
            } elseif ($event->event_type === 'hybrid') {
                // Default to virtual if not specified for hybrid events
                $metadata['attendance_type'] = 'virtual';
            }
        } else {
            // Fallback to server-side detection for in-person events
            $metadata['device'] = $this->getDeviceInfo($request);
            $metadata['browser'] = $this->getBrowserInfo($request);

            // For hybrid events without client metadata, assume in-person
            if ($event->event_type === 'hybrid') {
                $metadata['attendance_type'] = 'in-person';
            }
        }

        $attendance = $this->meetingAttendanceRepository->create([
            'event_id' => $event->id,
            'event_registration_id' => $registration?->id, // Nullable - can track without registration
            'user_id' => $user->id,
            'organization_id' => $organizationId,
            'joined_at' => now(),
            'last_seen_at' => now(),
            'status' => 'joined',
            'metadata' => $metadata,
        ]);

        return response()->json([
            'success' => true,
            'attendance_id' => $attendance->id,
            'message' => 'Attendance recorded.',
        ]);
    }

    /**
     * Update meeting heartbeat - track active status.
     */
    public function meetingHeartbeat(Request $request, Event $event): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        // Find active attendance
        $attendance = $this->meetingAttendanceRepository->findActiveForUserAndEvent($user->id, $event->id);

        if (! $attendance) {
            return response()->json(['error' => 'No active attendance found.'], 404);
        }

        // Check if user has been inactive for too long (5 minutes = timeout)
        $inactiveThreshold = now()->subMinutes(5);
        if ($attendance->last_seen_at && $attendance->last_seen_at->isBefore($inactiveThreshold)) {
            $this->meetingAttendanceRepository->update($attendance, [
                'status' => 'timeout',
                'left_at' => $attendance->last_seen_at,
            ]);
            $attendance->calculateDuration();

            return response()->json([
                'success' => false,
                'status' => 'timeout',
                'message' => 'Attendance timed out due to inactivity.',
            ]);
        }

        // Update last seen
        $attendance->updateLastSeen();

        return response()->json([
            'success' => true,
            'status' => $attendance->status,
            'last_seen_at' => $attendance->last_seen_at->toISOString(),
        ]);
    }

    /**
     * Leave meeting - record attendance end.
     */
    public function leaveMeeting(Request $request, Event $event): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        // Find active attendance
        $attendance = $this->meetingAttendanceRepository->findActiveForUserAndEvent($user->id, $event->id);

        if (! $attendance) {
            return response()->json(['error' => 'No active attendance found.'], 404);
        }

        // Mark as left
        $attendance->markAsLeft();

        return response()->json([
            'success' => true,
            'duration_seconds' => $attendance->duration_seconds,
            'message' => 'Attendance ended.',
        ]);
    }

    /**
     * Get device info from request.
     */
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

    /**
     * Get browser info from request.
     */
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
