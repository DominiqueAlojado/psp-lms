<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MeetingAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /**
     * Display a listing of events (public view for all users).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Show ALL published events to residents (cross-organization)
        $query = Event::query()
            ->with(['creator:id,name', 'organization:id,name'])
            ->published();

        // Filter by category
        if ($request->filled('category')) {
            $query->where('event_category', $request->category);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('event_type', $request->type);
        }

        // Filter upcoming/past
        if ($request->get('filter') === 'upcoming') {
            $query->upcoming();
        } elseif ($request->get('filter') === 'past') {
            $query->where('end_date', '<', now());
        }

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%')
                    ->orWhere('location', 'like', '%'.$request->search.'%');
            });
        }

        $events = $query->orderBy('start_date', 'asc')
            ->paginate(12)
            ->withQueryString();

        // Add registration status for current user
        $events->getCollection()->transform(function ($event) use ($user) {
            $event->user_registration = $event->registrations()
                ->where('user_id', $user->id)
                ->first();

            return $event;
        });

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

        // Load relationships
        $event->load([
            'creator:id,name',
            'organization:id,name',
        ]);

        // Get user's registration if exists
        $userRegistration = $event->registrations()
            ->where('user_id', $user->id)
            ->first();

        // Get registration statistics
        $registrationStats = [
            'total' => $event->registrations()->whereIn('registration_status', ['confirmed', 'approved'])->count(),
            'capacity' => $event->capacity,
            'remaining' => $event->getRemainingCapacity(),
        ];

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

        $query = Event::query()
            ->with(['creator:id,name', 'organization:id,name'])
            ->withCount('registrations');

        // System Admins and BOP see ALL events, others see only their organization's events
        if (! $user->hasAnyRole(['System Admin', 'BOP'])) {
            $query->where('organization_id', $organizationId);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'published') {
                $query->where('is_published', true);
            } elseif ($request->status === 'draft') {
                $query->where('is_published', false);
            }
        }

        // Search
        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        $events = $query->orderBy('start_date', 'desc')
            ->paginate(15)
            ->withQueryString();

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
            $fileName = Str::uuid().'.'.$file->getClientOriginalExtension();
            $imagePath = $file->storeAs('event-posters', $fileName, 'public');
        }

        $event = Event::create([
            ...$validated,
            'image_path' => $imagePath,
            'organization_id' => $organizationId,
            'created_by' => $user->id,
        ]);

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

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($event->image_path && Storage::disk('public')->exists($event->image_path)) {
                Storage::disk('public')->delete($event->image_path);
            }

            // Store new image
            $file = $request->file('image');
            $fileName = Str::uuid().'.'.$file->getClientOriginalExtension();
            $validated['image_path'] = $file->storeAs('event-posters', $fileName, 'public');
        }

        $event->update($validated);

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
        $confirmedCount = $event->registrations()->confirmed()->count();
        if ($confirmedCount > 0) {
            return back()->with('error', 'Cannot delete event with confirmed registrations.');
        }

        $event->delete();

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
        $existingRegistration = $event->registrations()
            ->where('user_id', $user->id)
            ->first();

        if ($existingRegistration) {
            return back()->with('error', 'You are already registered for this event.');
        }

        // Check capacity
        if ($event->isFull()) {
            // Add to waitlist if capacity is full
            // Determine payment amount for waitlisted
            $paymentAmount = $event->is_free ? 0 : $event->price;

            EventRegistration::create([
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
        EventRegistration::create([
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

        $query = $event->registrations()
            ->with(['user.resident', 'organization:id,name']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('registration_status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        $registrations = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('events/attendees', [
            'event' => $event,
            'registrations' => $registrations,
            'filters' => $request->only(['status', 'search']),
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

        $registration->update(['registration_status' => 'approved']);

        return back()->with('success', 'Registration approved successfully.');
    }

    /**
     * My registrations page.
     */
    public function myRegistrations(Request $request): Response
    {
        $user = $request->user();

        $query = EventRegistration::query()
            ->where('user_id', $user->id)
            ->with(['event.organization:id,name']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('registration_status', $request->status);
        }

        $registrations = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

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
        $registration = $event->registrations()
            ->where('user_id', $user->id)
            ->first();

        // Check for existing active attendance
        $existingAttendance = MeetingAttendance::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('status', '!=', 'left')
            ->whereNull('left_at')
            ->first();

        if ($existingAttendance) {
            // Update existing attendance
            $existingAttendance->updateLastSeen();
            return response()->json([
                'success' => true,
                'attendance_id' => $existingAttendance->id,
                'message' => 'Attendance updated.',
            ]);
        }

        // Create new attendance record
        $metadata = [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device' => $this->getDeviceInfo($request),
            'browser' => $this->getBrowserInfo($request),
        ];

        $attendance = MeetingAttendance::create([
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
        $attendance = MeetingAttendance::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->latest('joined_at')
            ->first();

        if (! $attendance) {
            return response()->json(['error' => 'No active attendance found.'], 404);
        }

        // Check if user has been inactive for too long (5 minutes = timeout)
        $inactiveThreshold = now()->subMinutes(5);
        if ($attendance->last_seen_at && $attendance->last_seen_at->isBefore($inactiveThreshold)) {
            $attendance->update([
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
        $attendance = MeetingAttendance::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->latest('joined_at')
            ->first();

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
