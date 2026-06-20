<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Repositories\Contracts\AnnouncementRepositoryInterface;
use App\Services\ActivityLog\AnnouncementActivityLogService;
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
        protected AnnouncementRepositoryInterface $announcementRepository
    ) {}

    /**
     * Display a listing of announcements for all users.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $organizationId = $user->current_organization_id;

        $announcements = $this->announcementRepository
            ->paginateVisibleToOrganization($organizationId, $request->only(['priority']))
            ->through(fn($announcement) => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'content' => $announcement->content,
                'scope' => $announcement->scope,
                'priority' => $announcement->priority,
                'is_pinned' => $announcement->is_pinned,
                'target_year_levels' => $announcement->target_year_levels,
                'expires_at' => $announcement->expires_at?->format('M d, Y'),
                'organization_name' => $announcement->organization?->name,
                'created_by' => $announcement->creator->name,
                'created_at' => $announcement->created_at->format('M d, Y'),
                'views_count' => $announcement->views_count,
            ]);

        return Inertia::render('announcements/index', [
            'announcements' => $announcements,
            'filters' => $request->only(['priority']),
        ]);
    }

    /**
     * Display announcement management page.
     */
    public function manage(Request $request): Response
    {
        $user = $request->user();
        $organizationId = $user->current_organization_id;
        $canCreateSystem = $user->hasPermissionTo('create-system-announcements');

        $announcements = $this->announcementRepository
            ->paginateForManagement($organizationId, $canCreateSystem, $request->only(['search', 'scope', 'is_published']))
            ->through(fn($announcement) => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'content' => $announcement->content,
                'scope' => $announcement->scope,
                'priority' => $announcement->priority,
                'is_published' => $announcement->is_published,
                'is_pinned' => $announcement->is_pinned,
                'target_year_levels' => $announcement->target_year_levels,
                'expires_at' => $announcement->expires_at?->format('Y-m-d'),
                'organization_name' => $announcement->organization?->name,
                'created_by' => $announcement->creator->name,
                'created_at' => $announcement->created_at->format('M d, Y'),
                'updated_at' => $announcement->updated_at->diffForHumans(),
                'views_count' => $announcement->views_count,
            ]);

        return Inertia::render('announcements/manage', [
            'announcements' => $announcements,
            'filters' => $request->only(['search', 'scope', 'is_published']),
            'canCreateSystem' => $canCreateSystem,
        ]);
    }

    /**
     * Store a newly created announcement.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Check if user has permission to create announcements
        if (! $user->hasPermissionTo('create-announcements')) {
            abort(403, 'You do not have permission to create announcements.');
        }

        $canCreateSystem = $user->hasPermissionTo('create-system-announcements');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'scope' => ['required', 'in:organization,system'],
            'priority' => ['required', 'in:normal,important,urgent'],
            'is_published' => ['boolean'],
            'is_pinned' => ['boolean'],
            'target_year_levels' => ['nullable', 'array'],
            'target_year_levels.*' => ['string'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        // Ensure non-admins can only create organization-scoped announcements
        if ($validated['scope'] === 'system' && ! $canCreateSystem) {
            return back()->withErrors(['scope' => 'You do not have permission to create system-wide announcements.']);
        }

        $announcement = $this->announcementRepository->create([
            'organization_id' => $validated['scope'] === 'organization' ? $user->current_organization_id : null,
            'created_by' => $user->id,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'scope' => $validated['scope'],
            'priority' => $validated['priority'],
            'is_published' => $validated['is_published'] ?? true,
            'is_pinned' => $validated['is_pinned'] ?? false,
            'target_year_levels' => $validated['target_year_levels'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        // Log announcement creation
        $this->activityLogService->logAnnouncementCreated($announcement);

        return redirect('/announcements/manage')->with('success', 'Announcement created successfully!');
    }

    /**
     * Update the specified announcement.
     */
    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $user = $request->user();

        // Check if user has permission to edit announcements
        if (! $user->hasPermissionTo('edit-announcements')) {
            abort(403, 'You do not have permission to edit announcements.');
        }

        $canCreateSystem = $user->hasPermissionTo('create-system-announcements');
        $isSystemAdmin = $user->hasAnyRole(['System Admin', 'BOP']);

        // System Admins and BOP can edit any announcement
        if (! $isSystemAdmin) {
            // Verify user has access (either their org or system admin)
            if ($announcement->scope === 'organization' && $announcement->organization_id !== $user->current_organization_id) {
                abort(403, 'You do not have access to this announcement.');
            }

            if ($announcement->scope === 'system' && ! $canCreateSystem) {
                abort(403, 'You do not have permission to edit system-wide announcements.');
            }
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'scope' => ['required', 'in:organization,system'],
            'priority' => ['required', 'in:normal,important,urgent'],
            'is_published' => ['boolean'],
            'is_pinned' => ['boolean'],
            'target_year_levels' => ['nullable', 'array'],
            'target_year_levels.*' => ['string'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

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
        $this->withoutActivityLogging(function () use ($announcement, $validated, $user) {
            $this->announcementRepository->update($announcement, [
                'organization_id' => $validated['scope'] === 'organization' ? $user->current_organization_id : null,
                'title' => $validated['title'],
                'content' => $validated['content'],
                'scope' => $validated['scope'],
                'priority' => $validated['priority'],
                'is_published' => $validated['is_published'],
                'is_pinned' => $validated['is_pinned'],
                'target_year_levels' => $validated['target_year_levels'] ?? null,
                'expires_at' => $validated['expires_at'] ?? null,
            ]);
        });

        // Build log data and log changes
        $logData = $this->activityLogService->buildUpdateLogData($announcement, $validated, $oldValues);
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

        $canCreateSystem = $user->hasPermissionTo('create-system-announcements');

        // Verify user has access (either their org or system admin)
        if ($announcement->scope === 'organization' && $announcement->organization_id !== $user->current_organization_id) {
            abort(403, 'You do not have access to this announcement.');
        }

        if ($announcement->scope === 'system' && ! $canCreateSystem) {
            abort(403, 'You do not have permission to delete system-wide announcements.');
        }

        // Log announcement deletion before deleting
        $this->activityLogService->logAnnouncementDeleted($announcement);

        $this->announcementRepository->delete($announcement);

        return back()->with('success', 'Announcement deleted successfully!');
    }

    /**
     * Get activity logs for an announcement.
     */
    public function logs(Request $request, Announcement $announcement): JsonResponse
    {
        $user = $request->user();
        $canCreateSystem = $user->hasPermissionTo('create-system-announcements');
        $isSystemAdmin = $user->hasAnyRole(['System Admin', 'BOP']);

        // System Admins and BOP can view any announcement logs
        if (! $isSystemAdmin) {
            // Check access
            if ($announcement->scope === 'organization' && $announcement->organization_id !== $user->current_organization_id) {
                abort(403, 'You do not have access to this announcement.');
            }

            if ($announcement->scope === 'system' && ! $canCreateSystem) {
                abort(403, 'You do not have permission to view system-wide announcement logs.');
            }
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
        if ($announcement->scope === 'organization' && $announcement->organization_id !== $user->current_organization_id) {
            abort(403, 'You do not have access to this announcement.');
        }

        $this->announcementRepository->markAsViewedBy($announcement, $user->id);

        return response()->noContent();
    }
}
