<?php

namespace App\Http\Controllers;

use App\Exports\StaffExport;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Facades\Activity;
use Spatie\Activitylog\Models\Activity as ActivityLog;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StaffController extends Controller
{
    /**
     * Display a listing of staff members.
     */
    public function index(Request $request): Response
    {
        // Get all roles except 'Resident'
        $staffRoleNames = Role::where('name', '!=', 'Resident')->pluck('name')->toArray();

        // Get organization IDs that the current user belongs to
        $userOrgIds = auth()->user()->organizations()->pluck('organizations.id')->toArray();
        $isSystemAdmin = auth()->user()->hasRole('System Admin');

        $staff = User::query()
            ->whereHas('roles', function ($query) use ($staffRoleNames) {
                $query->whereIn('name', $staffRoleNames);
            })
            ->when(! $isSystemAdmin, function ($query) use ($userOrgIds) {
                $query->whereHas('organizations', function ($q) use ($userOrgIds) {
                    $q->whereIn('organizations.id', $userOrgIds);
                });
            })
            ->with(['roles', 'currentOrganization'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->input('role'), function ($query, $role) {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            })
            ->when($request->input('organization'), function ($query, $orgId) {
                $query->where('current_organization_id', $orgId);
            })
            ->orderBy($request->input('sort', 'name'), $request->input('direction', 'asc'))
            ->paginate(15)
            ->withQueryString()
            ->through(fn($user) => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
                'primary_role' => $user->roles->first()?->name ?? 'N/A',
                'current_organization' => $user->currentOrganization?->name ?? 'N/A',
                'organizations_count' => $user->organizations()->count(),
                'created_at' => $user->created_at->format('Y-m-d'),
                'updated_at' => $user->updated_at->diffForHumans(),
            ]);

        // Get role statistics (only for staff in user's organizations, unless System Admin)
        $roleStats = Role::where('name', '!=', 'Resident')
            ->get()
            ->map(function ($role) use ($userOrgIds, $isSystemAdmin) {
                $query = User::whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role->name);
                });

                if (! $isSystemAdmin) {
                    $query->whereHas('organizations', function ($q) use ($userOrgIds) {
                        $q->whereIn('organizations.id', $userOrgIds);
                    });
                }

                return [
                    'role' => $role->name,
                    'count' => $query->count(),
                ];
            });

        return Inertia::render('staff/index', [
            'staff' => $staff,
            'filters' => $request->only(['search', 'role', 'organization', 'sort', 'direction']),
            'roleStats' => $roleStats,
            'roles' => Role::where('name', '!=', 'Resident')->get(['id', 'name']),
            'organizations' => $isSystemAdmin
                ? Organization::where('is_active', true)->get(['id', 'name'])
                : auth()->user()->organizations()->wherePivot('organization_user.is_active', true)->get(['organizations.id', 'organizations.name']),
        ]);
    }

    /**
     * Store a newly created staff member.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,id'],
            'organizations' => ['nullable', 'array'],
            'organizations.*' => ['exists:organizations,id'],
            'current_organization_id' => ['nullable', 'exists:organizations,id'],
        ], [
            'name.required' => 'Name is required',
            'email.required' => 'Email is required',
            'email.unique' => 'This email is already registered',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'roles.required' => 'At least one role must be selected',
        ]);

        // Create user
        $user = User::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
            'current_organization_id' => $validated['current_organization_id'] ?? null,
        ]);

        // Assign roles
        $user->syncRoles($validated['roles']);

        // Attach organizations
        if (! empty($validated['organizations'])) {
            $organizationData = collect($validated['organizations'])->mapWithKeys(function ($orgId) {
                return [$orgId => ['joined_at' => now(), 'is_active' => true]];
            })->toArray();

            $user->organizations()->attach($organizationData);
        }

        return back()->with('success', 'Staff member created successfully');
    }

    /**
     * Update the specified staff member.
     */
    public function update(Request $request, User $staff): RedirectResponse
    {
        // Prevent duplicate submissions by checking if this is a retry
        $requestId = $request->header('X-Request-ID') ?: uniqid('update_', true);
        $cacheKey = "staff_update_{$staff->id}_{$requestId}";

        // Check if we've already processed this update (prevent duplicates)
        if (cache()->has($cacheKey)) {
            return back()->with('success', 'Staff member updated successfully');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $staff->id],
            'password' => ['nullable', 'string', 'min:8'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,id'],
            'organizations' => ['nullable', 'array'],
            'organizations.*' => ['exists:organizations,id'],
            'current_organization_id' => ['nullable', 'exists:organizations,id'],
        ], [
            'name.required' => 'Name is required',
            'email.required' => 'Email is required',
            'email.unique' => 'This email is already registered',
            'password.min' => 'Password must be at least 8 characters',
            'roles.required' => 'At least one role must be selected',
        ]);

        // Capture old values before updating
        $oldName = $staff->name;
        $oldEmail = $staff->email;
        $oldCurrentOrgId = $staff->current_organization_id;
        $oldRoles = $staff->roles->pluck('name')->sort()->values()->toArray();
        $oldOrganizations = $staff->organizations->pluck('name')->sort()->values()->toArray();

        // Update user (disable automatic logging to prevent duplicates)
        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'current_organization_id' => $validated['current_organization_id'] ?? null,
        ];

        // Check if password is being changed
        $passwordChanged = ! empty($validated['password']);

        if ($passwordChanged) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        // Temporarily disable automatic logging to prevent duplicates
        // We'll manually log all changes in one consolidated entry below
        try {
            Activity::disableLogging();
            $staff->update($updateData);
        } finally {
            Activity::enableLogging();
        }

        // Update roles
        $newRoleIds = $validated['roles'];
        $newRoles = Role::whereIn('id', $newRoleIds)->pluck('name')->sort()->values()->toArray();
        $staff->syncRoles($validated['roles']);

        // Update organizations
        $newOrganizations = $oldOrganizations;
        if (isset($validated['organizations'])) {
            $newOrgIds = $validated['organizations'];
            $newOrganizations = Organization::whereIn('id', $newOrgIds)->pluck('name')->sort()->values()->toArray();

            $organizationData = collect($validated['organizations'])->mapWithKeys(function ($orgId) {
                return [$orgId => ['joined_at' => now(), 'is_active' => true]];
            })->toArray();

            $staff->organizations()->sync($organizationData);
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

        // Check email change
        if ($oldEmail !== $validated['email']) {
            $attributes['email'] = $validated['email'];
            $oldValues['email'] = $oldEmail;
            $hasChanges = true;
        }

        // Check current organization change
        if ($oldCurrentOrgId != ($validated['current_organization_id'] ?? null)) {
            $newOrgName = $validated['current_organization_id']
                ? Organization::find($validated['current_organization_id'])?->name
                : null;
            $oldOrgName = $oldCurrentOrgId
                ? Organization::find($oldCurrentOrgId)?->name
                : null;
            $attributes['current_organization'] = $newOrgName;
            $oldValues['current_organization'] = $oldOrgName;
            $hasChanges = true;
        }

        // Check password change
        if ($passwordChanged) {
            $attributes['password'] = '***changed***';
            $oldValues['password'] = '***hidden***';
            $hasChanges = true;
        }

        // Check role changes
        if ($oldRoles !== $newRoles) {
            $attributes['roles'] = $newRoles;
            $oldValues['roles'] = $oldRoles;
            $hasChanges = true;
        }

        // Check organization changes
        if ($oldOrganizations !== $newOrganizations) {
            $attributes['organizations'] = $newOrganizations;
            $oldValues['organizations'] = $oldOrganizations;
            $hasChanges = true;
        }

        // Log all changes in a single entry (only once per request)
        if ($hasChanges) {
            // Use batch_uuid to prevent duplicate entries from the same update
            $batchUuid = (string) \Illuminate\Support\Str::uuid();

            activity()
                ->performedOn($staff)
                ->causedBy($request->user())
                ->useLog('users')
                ->withProperties([
                    'attributes' => $attributes,
                    'old' => $oldValues,
                ])
                ->log('User updated');
        }

        // Mark this update as processed (expires in 5 seconds to prevent duplicates)
        cache()->put($cacheKey, true, 5);

        return back()->with('success', 'Staff member updated successfully');
    }

    /**
     * Remove the specified staff member.
     */
    public function destroy(User $staff): RedirectResponse
    {
        // Check if user is a resident (should not delete via staff module)
        if ($staff->hasRole('Resident')) {
            return back()->with('error', 'Cannot delete residents from staff module');
        }

        $staff->delete();

        return back()->with('success', 'Staff member deleted successfully');
    }

    /**
     * Export staff to Excel.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $filters = $request->only(['search', 'role', 'organization']);

        return Excel::download(
            new StaffExport($filters),
            'staff-' . now()->format('Y-m-d-His') . '.xlsx'
        );
    }

    /**
     * Get staff member details.
     */
    public function show(User $staff): JsonResponse
    {
        $staff->load(['roles', 'currentOrganization', 'organizations']);

        return response()->json([
            'staff' => [
                'id' => $staff->id,
                'uuid' => $staff->uuid,
                'name' => $staff->name,
                'email' => $staff->email,
                'roles' => $staff->roles->pluck('id')->toArray(),
                'role_names' => $staff->roles->pluck('name')->toArray(),
                'current_organization_id' => $staff->current_organization_id,
                'organizations' => $staff->organizations->map(fn($org) => [
                    'id' => $org->id,
                    'name' => $org->name,
                ])->toArray(),
            ],
        ]);
    }

    /**
     * Get activity logs for a staff member.
     */
    public function logs(User $staff): JsonResponse
    {
        // Get activities where this user is:
        // 1. The subject (being updated/modified) - e.g., when their profile is updated
        // 2. The causer (performing actions) - e.g., when they grade submissions, update assessments, etc.
        $logs = ActivityLog::query()
            ->where(function ($query) use ($staff) {
                $query->where(function ($q) use ($staff) {
                    $q->where('subject_type', User::class)
                        ->where('subject_id', $staff->id);
                })->orWhere(function ($q) use ($staff) {
                    $q->where('causer_type', User::class)
                        ->where('causer_id', $staff->id);
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
