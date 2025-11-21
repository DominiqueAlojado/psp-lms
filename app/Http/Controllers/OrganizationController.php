<?php

namespace App\Http\Controllers;

use App\Exports\InstitutionsExport;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Facades\Activity;
use Spatie\Activitylog\Models\Activity as ActivityLog;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrganizationController extends Controller
{
    /**
     * Display a listing of institutions.
     */
    public function index(Request $request): Response
    {
        $institutions = Organization::query()
            ->withCount(['residents', 'users'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%");
                });
            })
            ->when($request->input('type'), function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($request->input('status'), function ($query, $status) {
                $isActive = $status === 'active';
                $query->where('is_active', $isActive);
            })
            ->orderBy($request->input('sort', 'name'), $request->input('direction', 'asc'))
            ->paginate(15)
            ->withQueryString()
            ->through(fn($institution) => [
                'id' => $institution->id,
                'name' => $institution->name,
                'slug' => $institution->slug,
                'description' => $institution->description,
                'type' => $institution->type,
                'is_active' => $institution->is_active,
                'residents_count' => $institution->residents_count,
                'users_count' => $institution->users_count,
                'training_officers' => $institution->training_officers ?? [],
                'training_officers_count' => is_array($institution->training_officers) ? count($institution->training_officers) : 0,
                'updated_at' => $institution->updated_at->diffForHumans(),
            ]);

        // Get statistics per type
        $typeStats = Organization::query()
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        // Get unique types
        $types = Organization::distinct()->pluck('type')->filter()->values();

        return Inertia::render('institutions/index', [
            'institutions' => $institutions,
            'filters' => $request->only(['search', 'type', 'status']),
            'types' => $types,
            'typeStats' => $typeStats,
        ]);
    }

    /**
     * Store a newly created institution.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:organizations,name'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:chapter,institution,main,national'],
            'is_active' => ['boolean'],
            'training_officers' => ['nullable', 'json'],
        ], [
            'name.required' => 'Institution name is required',
            'name.unique' => 'An institution with this name already exists',
            'type.required' => 'Institution type is required',
            'type.in' => 'Please select a valid institution type',
            'training_officers.json' => 'Invalid training officers data',
        ]);

        // Generate slug from name
        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $validated['is_active'] ?? true;

        // Decode training officers JSON if provided
        if (isset($validated['training_officers'])) {
            $validated['training_officers'] = json_decode($validated['training_officers'], true);
        }

        $institution = Organization::create($validated);

        // Log organization creation
        activity()
            ->performedOn($institution)
            ->causedBy($request->user())
            ->useLog('organizations')
            ->withProperties([
                'attributes' => [
                    'name' => $institution->name,
                    'type' => $institution->type,
                    'is_active' => $institution->is_active,
                    'training_officers' => $institution->training_officers ?? [],
                ],
            ])
            ->log('Organization created');

        return back()->with('success', 'Institution created successfully');
    }

    /**
     * Update the specified institution.
     */
    public function update(Request $request, Organization $organization): RedirectResponse
    {
        // Prevent duplicate submissions
        $requestId = $request->header('X-Request-ID') ?: uniqid('update_', true);
        $cacheKey = "organization_update_{$organization->id}_{$requestId}";

        // Check if we've already processed this update (prevent duplicates)
        if (cache()->has($cacheKey)) {
            return back()->with('success', 'Institution updated successfully');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:organizations,name,' . $organization->id],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:chapter,institution,main,national'],
            'is_active' => ['boolean'],
            'training_officers' => ['nullable', 'json'],
        ], [
            'name.required' => 'Institution name is required',
            'name.unique' => 'An institution with this name already exists',
            'type.required' => 'Institution type is required',
            'type.in' => 'Please select a valid institution type',
            'training_officers.json' => 'Invalid training officers data',
        ]);

        // Capture old values before updating
        $oldName = $organization->name;
        $oldDescription = $organization->description;
        $oldType = $organization->type;
        $oldIsActive = $organization->is_active;
        $oldTrainingOfficers = $organization->training_officers ?? [];

        // Update slug if name changed
        if ($validated['name'] !== $organization->name) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Decode training officers JSON if provided
        if (isset($validated['training_officers'])) {
            $validated['training_officers'] = json_decode($validated['training_officers'], true);
        }

        // Temporarily disable automatic logging to prevent duplicates
        try {
            Activity::disableLogging();
            $organization->update($validated);
        } finally {
            Activity::enableLogging();
        }

        // Build consolidated log entry with all changes
        $attributes = [];
        $oldValues = [];
        $hasChanges = false;

        // Check name change
        if ($oldName !== $validated['name']) {
            $attributes['name'] = $validated['name'];
            $oldValues['name'] = $oldName;
            $hasChanges = true;
        }

        // Check description change
        if (($validated['description'] ?? null) !== $oldDescription) {
            $attributes['description'] = $validated['description'] ?? null;
            $oldValues['description'] = $oldDescription;
            $hasChanges = true;
        }

        // Check type change
        if ($oldType !== $validated['type']) {
            $attributes['type'] = $validated['type'];
            $oldValues['type'] = $oldType;
            $hasChanges = true;
        }

        // Check is_active change
        $newIsActive = $validated['is_active'] ?? true;
        if ($oldIsActive !== $newIsActive) {
            $attributes['is_active'] = $newIsActive;
            $oldValues['is_active'] = $oldIsActive;
            $hasChanges = true;
        }

        // Check training officers change
        $newTrainingOfficers = $validated['training_officers'] ?? [];
        $oldTrainingOfficersSorted = array_values(array_unique($oldTrainingOfficers));
        $newTrainingOfficersSorted = array_values(array_unique($newTrainingOfficers));
        sort($oldTrainingOfficersSorted);
        sort($newTrainingOfficersSorted);

        if ($oldTrainingOfficersSorted !== $newTrainingOfficersSorted) {
            // Fetch user details for old training officers
            $oldTrainingOfficersDetails = [];
            if (! empty($oldTrainingOfficers)) {
                $oldUsers = User::whereIn('id', $oldTrainingOfficers)->get(['id', 'name', 'email']);
                $oldTrainingOfficersDetails = $oldUsers->map(fn($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ])->sortBy('id')->values()->toArray();
            }

            // Fetch user details for new training officers
            $newTrainingOfficersDetails = [];
            if (! empty($newTrainingOfficers)) {
                $newUsers = User::whereIn('id', $newTrainingOfficers)->get(['id', 'name', 'email']);
                $newTrainingOfficersDetails = $newUsers->map(fn($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ])->sortBy('id')->values()->toArray();
            }

            $attributes['training_officers'] = $newTrainingOfficersDetails;
            $oldValues['training_officers'] = $oldTrainingOfficersDetails;
            $hasChanges = true;
        }

        // Log all changes in a single entry
        if ($hasChanges) {
            activity()
                ->performedOn($organization)
                ->causedBy($request->user())
                ->useLog('organizations')
                ->withProperties([
                    'attributes' => $attributes,
                    'old' => $oldValues,
                ])
                ->log('Organization updated');
        }

        // Mark this update as processed (expires in 5 seconds to prevent duplicates)
        cache()->put($cacheKey, true, 5);

        return back()->with('success', 'Institution updated successfully');
    }

    /**
     * Remove the specified institution.
     */
    public function destroy(Organization $organization): RedirectResponse
    {
        // Check if institution has residents
        if ($organization->residents()->count() > 0) {
            return back()->withErrors([
                'error' => 'Cannot delete institution with active residents. Please reassign or delete residents first.',
            ]);
        }

        // Log deletion before deleting
        activity()
            ->performedOn($organization)
            ->causedBy(request()->user())
            ->useLog('organizations')
            ->withProperties([
                'attributes' => [
                    'name' => $organization->name,
                    'type' => $organization->type,
                ],
            ])
            ->log('Organization deleted');

        $organization->delete();

        return back()->with('success', 'Institution deleted successfully');
    }

    /**
     * Export institutions to Excel.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $filters = $request->only(['search', 'type', 'status']);

        $filename = 'institutions_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new InstitutionsExport($filters), $filename);
    }

    /**
     * Switch the user's current organization.
     */
    public function switch(Request $request, Organization $organization): RedirectResponse
    {
        $user = $request->user();

        // Verify user belongs to this organization
        if (! $user->organizations->contains($organization->id)) {
            abort(403, 'You do not belong to this organization.');
        }

        // Switch to the organization
        $user->switchOrganization($organization);

        // Get the referer URL
        $referer = $request->header('referer') ?? route('dashboard');
        $url = parse_url($referer);

        // Get the path, excluding any /organization/* routes
        $path = $url['path'] ?? '/dashboard';

        // If the path is an organization switch route, redirect to dashboard instead
        if (str_contains($path, '/organization/')) {
            $path = '/dashboard';
        }

        // Parse existing query parameters
        parse_str($url['query'] ?? '', $queryParams);

        // Add/update the org parameter
        $queryParams['org'] = $organization->slug;

        // Rebuild the URL with the org parameter
        $redirectUrl = $path . '?' . http_build_query($queryParams);

        return redirect($redirectUrl)->with('success', "Switched to {$organization->name}");
    }

    /**
     * Get activity logs for an organization.
     */
    public function logs(Organization $organization): JsonResponse
    {
        // Get activities where this organization is:
        // 1. The subject (being updated/modified) - e.g., when it's updated
        // 2. Related activities - e.g., when users are associated with it
        $logs = ActivityLog::query()
            ->where(function ($query) use ($organization) {
                $query->where(function ($q) use ($organization) {
                    $q->where('subject_type', Organization::class)
                        ->where('subject_id', $organization->id);
                })->orWhere(function ($q) use ($organization) {
                    // Also get activities where organization is mentioned in properties
                    // This might include activities like user organization changes
                    $q->where('log_name', 'organizations')
                        ->whereJsonContains('properties->attributes->current_organization_id', $organization->id);
                });
            })
            ->with(['subject', 'causer'])
            ->latest()
            ->limit(100)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'log_name' => $activity->log_name,
                    'event' => $activity->event,
                    'properties' => $activity->properties,
                    'causer' => $activity->causer ? [
                        'id' => $activity->causer->id,
                        'name' => $activity->causer->name,
                        'email' => $activity->causer->email,
                    ] : null,
                    'subject' => $activity->subject ? [
                        'id' => $activity->subject->id,
                        'type' => class_basename($activity->subject_type),
                    ] : null,
                    'created_at' => $activity->created_at->toISOString(),
                ];
            });

        return response()->json([
            'logs' => $logs,
        ]);
    }
}
