<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of announcements for all users.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $organizationId = $user->current_organization_id;

        $announcements = Announcement::query()
            ->with('creator:id,name', 'organization:id,name')
            ->visibleTo($organizationId)
            ->active()
            ->when($request->input('priority'), function ($query, $priority) {
                $query->where('priority', $priority);
            })
            ->orderBy('is_pinned', 'desc')
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($announcement) => [
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

        $query = Announcement::query()
            ->with('creator:id,name', 'organization:id,name');

        // System admins see all, regular users see only their org's announcements
        if (! $canCreateSystem) {
            $query->where('organization_id', $organizationId);
        }

        $announcements = $query
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->when($request->input('scope'), function ($query, $scope) {
                $query->where('scope', $scope);
            })
            ->when($request->has('is_published'), function ($query) use ($request) {
                $query->where('is_published', $request->input('is_published'));
            })
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($announcement) => [
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

        $announcement = Announcement::create([
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

        return redirect('/announcements/manage')->with('success', 'Announcement created successfully!');
    }

    /**
     * Update the specified announcement.
     */
    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $user = $request->user();
        $canCreateSystem = $user->hasPermissionTo('create-system-announcements');

        // Verify user has access (either their org or system admin)
        if ($announcement->scope === 'organization' && $announcement->organization_id !== $user->current_organization_id) {
            abort(403, 'You do not have access to this announcement.');
        }

        if ($announcement->scope === 'system' && ! $canCreateSystem) {
            abort(403, 'You do not have permission to edit system-wide announcements.');
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

        $announcement->update([
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

        return back()->with('success', 'Announcement updated successfully!');
    }

    /**
     * Remove the specified announcement.
     */
    public function destroy(Announcement $announcement): RedirectResponse
    {
        $user = auth()->user();
        $canCreateSystem = $user->hasPermissionTo('create-system-announcements');

        // Verify user has access (either their org or system admin)
        if ($announcement->scope === 'organization' && $announcement->organization_id !== $user->current_organization_id) {
            abort(403, 'You do not have access to this announcement.');
        }

        if ($announcement->scope === 'system' && ! $canCreateSystem) {
            abort(403, 'You do not have permission to delete system-wide announcements.');
        }

        $announcement->delete();

        return back()->with('success', 'Announcement deleted successfully!');
    }

    /**
     * Mark announcement as viewed.
     */
    public function markAsViewed(Announcement $announcement)
    {
        $user = auth()->user();

        // Verify user has access to view this announcement
        if ($announcement->scope === 'organization' && $announcement->organization_id !== $user->current_organization_id) {
            abort(403, 'You do not have access to this announcement.');
        }

        $announcement->markAsViewedBy($user->id);

        return response()->noContent();
    }
}
