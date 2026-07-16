<?php

namespace App\Http\Controllers;

use App\Exports\StaffExport;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Models\User;
use App\Services\ActivityLog\StaffActivityLogService;
use App\Services\StaffManagementService;
use App\Services\StaffReadService;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StaffController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected StaffActivityLogService $activityLogService,
        protected StaffManagementService $staffManagementService,
        protected StaffReadService $staffReadService,
    ) {}
    /**
     * Display a listing of staff members.
     */
    public function index(Request $request): Response
    {
        $payload = $this->staffReadService->indexPayload(
            $request->user(),
            $request->only(['search', 'role', 'organization', 'sort', 'direction'])
        );

        return Inertia::render('staff/index', [
            'staff' => $payload['staff'],
            'filters' => $request->only(['search', 'role', 'organization', 'sort', 'direction']),
            'roleStats' => $payload['roleStats'],
            'roles' => $payload['roles'],
            'organizations' => $payload['organizations'],
            'isAllOrganizationsContext' => $payload['isAllOrganizationsContext'],
        ]);
    }

    /**
     * Store a newly created staff member.
     */
    public function store(StoreStaffRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = $this->staffManagementService->create($request->user(), $validated);

        // Log user creation
        $this->activityLogService->logUserCreated($user);

        return back()->with('success', 'Staff member created successfully');
    }

    /**
     * Update the specified staff member.
     */
    public function update(UpdateStaffRequest $request, User $staff): RedirectResponse
    {
        // Prevent duplicate submissions by checking if this is a retry
        $requestId = $request->header('X-Request-ID') ?: uniqid('update_', true);
        $cacheKey = "staff_update_{$staff->id}_{$requestId}";

        // Check if we've already processed this update (prevent duplicates)
        if (cache()->has($cacheKey)) {
            return back()->with('success', 'Staff member updated successfully');
        }

        $validated = $request->validated();

        // Capture old values before updating
        $oldName = $staff->name;
        $oldEmail = $staff->email;
        $oldCurrentOrgId = $staff->current_organization_id;
        $oldRoles = $staff->roles->pluck('name')->sort()->values()->toArray();
        $oldOrganizations = $staff->organizations->pluck('name')->sort()->values()->toArray();

        // Temporarily disable automatic logging to prevent duplicates
        // We'll manually log all changes in one consolidated entry below
        $updateResult = [];
        $this->withoutActivityLogging(function () use ($request, $staff, $validated, &$updateResult) {
            $updateResult = $this->staffManagementService->update($request->user(), $staff, $validated);
        });

        // Build consolidated log entry with all changes using service
        $logData = $this->activityLogService->buildUpdateLogData(
            $staff,
            $validated,
            $oldName,
            $oldEmail,
            $oldCurrentOrgId,
            $oldRoles,
            $oldOrganizations,
            $updateResult['passwordChanged'],
            $updateResult['newRoles'],
            $updateResult['newOrganizations']
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

        $this->staffManagementService->delete($staff);

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
        return response()->json($this->staffReadService->showPayload($staff));
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
