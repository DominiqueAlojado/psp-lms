<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnnouncementRequest;
use App\Http\Requests\UpdateAnnouncementRequest;
use App\Models\Announcement;
use App\Services\ActivityLog\AnnouncementActivityLogService;
use App\Services\AnnouncementManagementService;
use App\Services\AnnouncementReadService;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected AnnouncementActivityLogService $activityLogService,
        protected AnnouncementManagementService $announcementManagementService,
        protected AnnouncementReadService $announcementReadService,
    ) {}

    /**
     * Display a listing of announcements for all users.
     */
    public function index(Request $request): Response
    {
        $payload = $this->announcementReadService->indexPayload(
            $request->user(),
            $request->only(['priority'])
        );

        return Inertia::render('announcements/index', [
            'announcements' => $payload['announcements'],
            'filters' => $request->only(['priority']),
        ]);
    }

    /**
     * Display announcement management page.
     */
    public function manage(Request $request): Response
    {
        $payload = $this->announcementReadService->managePayload(
            $request->user(),
            $request->only(['search', 'scope', 'is_published'])
        );

        return Inertia::render('announcements/manage', [
            'announcements' => $payload['announcements'],
            'filters' => $request->only(['search', 'scope', 'is_published']),
            'canCreateSystem' => $payload['canCreateSystem'],
        ]);
    }

    /**
     * Store a newly created announcement.
     */
    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Check if user has permission to create announcements
        if (! $user->hasPermissionTo('create-announcements')) {
            abort(403, 'You do not have permission to create announcements.');
        }

        $canCreateSystem = $this->announcementManagementService->canCreateSystem($user);

        $validated = $request->validated();

        // Ensure non-admins can only create organization-scoped announcements
        if ($validated['scope'] === 'system' && ! $canCreateSystem) {
            return back()->withErrors(['scope' => 'You do not have permission to create system-wide announcements.']);
        }

        $announcement = $this->announcementManagementService->create($user, $validated);

        // Log announcement creation
        $this->activityLogService->logAnnouncementCreated($announcement);

        return redirect('/announcements/manage')->with('success', 'Announcement created successfully!');
    }

    /**
     * Update the specified announcement.
     */
    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $user = $request->user();

        // Check if user has permission to edit announcements
        if (! $user->hasPermissionTo('edit-announcements')) {
            abort(403, 'You do not have permission to edit announcements.');
        }

        $canCreateSystem = $this->announcementManagementService->canCreateSystem($user);

        if (! $this->announcementManagementService->canManageAnnouncement($user, $announcement)) {
            if ($announcement->scope === 'system') {
                abort(403, 'You do not have permission to edit system-wide announcements.');
            }

            abort(403, 'You do not have access to this announcement.');
        }

        $validated = $request->validated();

        // Ensure non-admins can only create organization-scoped announcements
        if ($validated['scope'] === 'system' && ! $canCreateSystem) {
            return back()->withErrors(['scope' => 'You do not have permission to create system-wide announcements.']);
        }

        // Capture old values before update
        $oldValues = [
            'title' => $announcement->title,
            'content' => $announcement->content,
            'scope' => $announcement->scope,
            'priority' => $announcement->priority,
            'is_published' => $announcement->is_published,
            'is_pinned' => $announcement->is_pinned,
            'target_year_levels' => $announcement->target_year_levels,
            'expires_at' => $announcement->expires_at?->format('Y-m-d'),
        ];

        // Update announcement without logging (to avoid duplicate logs)
        $normalizedAttributes = [];
        $this->withoutActivityLogging(function () use ($announcement, $validated, $user, &$normalizedAttributes) {
            $normalizedAttributes = $this->announcementManagementService->update($user, $announcement, $validated);
        });

        // Build log data and log changes
        $logData = $this->activityLogService->buildUpdateLogData($announcement, $normalizedAttributes, $oldValues);
        if (! empty($logData['attributes']) || ! empty($logData['old'])) {
            $this->activityLogService->logAnnouncementUpdated($announcement, $logData['attributes'], $logData['old']);
        }

        return back()->with('success', 'Announcement updated successfully!');
    }

    /**
     * Remove the specified announcement.
     */
    public function destroy(Request $request, Announcement $announcement): RedirectResponse
    {
        $user = $request->user();

        // Check if user has permission to delete announcements
        if (! $user->hasPermissionTo('delete-announcements')) {
            abort(403, 'You do not have permission to delete announcements.');
        }

        if (! $this->announcementManagementService->canManageAnnouncement($user, $announcement)) {
            if ($announcement->scope === 'system') {
                abort(403, 'You do not have permission to delete system-wide announcements.');
            }

            abort(403, 'You do not have access to this announcement.');
        }

        // Log announcement deletion before deleting
        $this->activityLogService->logAnnouncementDeleted($announcement);

        $this->announcementManagementService->delete($announcement);

        return back()->with('success', 'Announcement deleted successfully!');
    }

    /**
     * Get activity logs for an announcement.
     */
    public function logs(Request $request, Announcement $announcement): JsonResponse
    {
        $user = $request->user();
        if (! $this->announcementManagementService->canManageAnnouncement($user, $announcement)) {
            if ($announcement->scope === 'system') {
                abort(403, 'You do not have permission to view system-wide announcement logs.');
            }

            abort(403, 'You do not have access to this announcement.');
        }

        $logs = $this->activityLogService->getLogs($announcement);

        return response()->json([
            'logs' => $logs,
        ]);
    }

    /**
     * Mark announcement as viewed.
     */
    public function markAsViewed(Request $request, Announcement $announcement)
    {
        $user = $request->user();

        // Verify user has access to view this announcement
        if (! $this->announcementManagementService->canViewAnnouncement($user, $announcement)) {
            abort(403, 'You do not have access to this announcement.');
        }

        $this->announcementManagementService->markAsViewed($announcement, $user->id);

        return response()->noContent();
    }
}
