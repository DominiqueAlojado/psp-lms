<?php

namespace App\Http\Controllers;

use App\Exports\StaffExport;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Models\User;
use App\Services\ActivityLog\StaffActivityLogService;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StaffController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected StaffActivityLogService $activityLogService,
        protected StaffRepositoryInterface $staffRepository
    ) {}
    /**
     * Display a listing of staff members.
     */
    public function index(Request $request): Response
    {
        $staffRoleNames = $this->staffRepository->getStaffRoleNames();

        // Get organization IDs that the current user belongs to
        $userOrgIds = auth()->user()->organizations()->pluck('organizations.id')->toArray();
        $isSystemAdmin = auth()->user()->hasRole('System Admin');

        $staff = $this->staffRepository
            ->paginate(
                $request->only(['search', 'role', 'organization', 'sort', 'direction']),
                $staffRoleNames,
                $userOrgIds,
                $isSystemAdmin
            )
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

        $roleStats = $this->staffRepository->getRoleStats($userOrgIds, $isSystemAdmin);

        return Inertia::render('staff/index', [
            'staff' => $staff,
            'filters' => $request->only(['search', 'role', 'organization', 'sort', 'direction']),
            'roleStats' => $roleStats,
            'roles' => $this->staffRepository->getSelectableRoles(),
            'organizations' => $this->staffRepository->getSelectableOrganizations(auth()->user(), $isSystemAdmin),
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
        $user = $this->staffRepository->create([
            'uuid' => Str::uuid(),
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
            'current_organization_id' => $validated['current_organization_id'] ?? null,
        ]);

        // Assign roles
        $this->staffRepository->syncRoles($user, $validated['roles']);

        // Attach organizations
        if (! empty($validated['organizations'])) {
            $this->staffRepository->attachOrganizations($user, $validated['organizations']);
        }

        // Log user creation
        $this->activityLogService->logUserCreated($user);

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
        $this->withoutActivityLogging(function () use ($staff, $updateData) {
            $this->staffRepository->update($staff, $updateData);
        });

        // Update roles
        $newRoleIds = $validated['roles'];
        $newRoles = Role::whereIn('id', $newRoleIds)->pluck('name')->sort()->values()->toArray();
        $this->staffRepository->syncRoles($staff, $validated['roles']);

        // Update organizations
        $newOrganizations = $oldOrganizations;
        if (isset($validated['organizations'])) {
            $newOrgIds = $validated['organizations'];
            $newOrganizations = $this->staffRepository->getOrganizationNamesByIds($newOrgIds);
            $this->staffRepository->syncOrganizations($staff, $validated['organizations']);
        }

        // Build consolidated log entry with all changes using service
        $logData = $this->activityLogService->buildUpdateLogData(
            $staff,
            $validated,
            $oldName,
            $oldEmail,
            $oldCurrentOrgId,
            $oldRoles,
            $oldOrganizations,
            $passwordChanged,
            $newRoles,
            $newOrganizations
        );

        // Log all changes in a single entry (only once per request)
        if ($logData['hasChanges']) {
            $this->activityLogService->logUserUpdated(
                $staff,
                $logData['attributes'],
                $logData['oldValues']
            );
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

        // Log deletion before deleting
        $this->activityLogService->logUserDeleted($staff);

        $this->staffRepository->delete($staff);

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
        $logs = $this->activityLogService->getLogs($staff);

        return response()->json([
            'logs' => $logs,
        ]);
    }
}
