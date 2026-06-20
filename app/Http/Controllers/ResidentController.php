<?php

namespace App\Http\Controllers;

use App\Exports\ResidentsExport;
use App\Http\Requests\StoreResidentRequest;
use App\Http\Requests\UpdateResidentRequest;
use App\Models\Resident;
use App\Repositories\Contracts\ResidentRepositoryInterface;
use App\Services\ActivityLog\ResidentActivityLogService;
use App\Services\ResidentManagementService;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResidentController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected ResidentActivityLogService $activityLogService,
        protected ResidentRepositoryInterface $residentRepository,
        protected ResidentManagementService $residentManagementService,
    ) {
    }
    /**
     * Display a listing of residents with search and filters.
     */
    public function index(Request $request): Response
    {
        $residents = $this->residentRepository
            ->paginate($request->only(['search', 'organization_id', 'year_level', 'status', 'course', 'sort', 'direction']))
            ->through(fn($resident) => [
                'id' => $resident->id,
                'uuid' => $resident->uuid,
                'full_name' => $resident->full_name,
                'full_name_with_middle_initial' => $resident->full_name_with_middle_initial,
                'first_name' => $resident->first_name,
                'middle_name' => $resident->middle_name,
                'last_name' => $resident->last_name,
                'email' => $resident->email,
                'contact_number' => $resident->contact_number,
                'course' => $resident->course,
                'year_level' => $resident->year_level,
                'status' => $resident->status,
                'updated_at' => $resident->updated_at->diffForHumans(),
                'organizations_count' => $resident->user ? $resident->user->organizations()->count() : 0,
                'organization' => [
                    'id' => $resident->organization->id,
                    'name' => $resident->organization->name,
                    'slug' => $resident->organization->slug,
                ],
            ]);

        $organizations = $this->residentRepository->getOrganizations();
        $yearLevelStats = $this->residentRepository->getYearLevelStats();

        return Inertia::render('residents/index', [
            'residents' => $residents,
            'organizations' => $organizations,
            'filters' => $request->only(['search', 'organization_id', 'year_level', 'status', 'course']),
            'yearLevels' => ['Pre Resident', 'First Year', 'Second Year', 'Third Year', 'Fourth Year', 'Graduate'],
            'statuses' => ['active', 'inactive'],
            'courses' => $this->residentRepository->getDistinctCourses(),
            'yearLevelStats' => $yearLevelStats,
        ]);
    }

    /**
     * Store a newly created resident.
     */
    public function store(StoreResidentRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $resident = $this->residentManagementService->create($validated);

            // Log resident creation
            $this->activityLogService->logResidentCreated($resident);

            return back()->with('success', 'Resident created successfully');
        } catch (\Exception $e) {
            \Log::error('Error creating resident: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => 'Failed to create resident: ' . $e->getMessage()]);
        }
    }

    /**
     * Get organization data for a resident (AJAX endpoint for sheet).
     */
    public function show(Resident $resident): \Illuminate\Http\JsonResponse
    {
        $resident->load(['organization', 'user.organizations']);

        // Get current organizations through user
        $currentOrganizations = $resident->user
            ? $resident->user->organizations->map(fn($org) => [
                'id' => $org->id,
                'name' => $org->name,
                'slug' => $org->slug,
                'type' => $org->type,
                'pivot' => [
                    'joined_at' => $org->pivot->joined_at,
                    'is_active' => $org->pivot->is_active,
                ],
            ])
            : [];

        // Get organizations not yet associated
        $associatedIds = $currentOrganizations->pluck('id')->toArray();
        $availableOrganizations = $this->residentRepository
            ->getActiveOrganizationsExcluding($associatedIds)
            ->map(fn($org) => [
                'id' => $org->id,
                'name' => $org->name,
                'slug' => $org->slug,
                'type' => $org->type,
            ]);

        return response()->json([
            'currentOrganizations' => $currentOrganizations,
            'availableOrganizations' => $availableOrganizations,
        ]);
    }

    /**
     * Update the specified resident.
     */
    public function update(UpdateResidentRequest $request, Resident $resident): RedirectResponse
    {
        $validated = $request->validated();

        // Capture old values before updating
        $oldFirstName = $resident->first_name;
        $oldMiddleName = $resident->middle_name;
        $oldLastName = $resident->last_name;
        $oldEmail = $resident->email;
        $oldContactNumber = $resident->contact_number;
        $oldCourse = $resident->course;
        $oldYearLevel = $resident->year_level;
        $oldStatus = $resident->status;
        // Temporarily disable automatic logging to prevent duplicates
        $updateResult = [];
        $this->withoutActivityLogging(function () use ($resident, $validated, &$updateResult) {
            $updateResult = $this->residentManagementService->update($resident, $validated);
        });

        // Build consolidated log entry with all changes using service
        $logData = $this->activityLogService->buildUpdateLogData(
            $resident,
            $validated,
            $oldFirstName,
            $oldMiddleName,
            $oldLastName,
            $oldEmail,
            $oldContactNumber,
            $oldCourse,
            $oldYearLevel,
            $oldStatus,
            $updateResult['passwordChanged']
        );

        // Log all changes in a single entry
        if ($logData['hasChanges']) {
            $this->activityLogService->logResidentUpdated(
                $resident,
                $logData['attributes'],
                $logData['oldValues']
            );
        }

        return back()->with('success', 'Resident updated successfully');
    }

    /**
     * Remove the specified resident.
     */
    public function destroy(Resident $resident): RedirectResponse
    {
        // Log deletion before deleting
        $this->activityLogService->logResidentDeleted($resident);

        $this->residentRepository->delete($resident);

        return back()->with('success', 'Resident deleted successfully');
    }

    /**
     * Export residents to Excel.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $filters = $request->only(['search', 'organization_id', 'year_level', 'status', 'course']);

        $filename = 'residents_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new ResidentsExport($filters), $filename);
    }

    /**
     * Attach a resident to an organization.
     */
    public function attachOrganization(Request $request, Resident $resident): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
        ]);

        $organization = $this->residentRepository->findOrganizationById((int) $validated['organization_id']);

        if ($organization && $resident->addToOrganization($organization)) {
            return back()->with('success', "Resident added to {$organization->name}");
        }

        return back()->withErrors(['error' => 'Resident is already associated with this organization']);
    }

    /**
     * Detach a resident from an organization.
     */
    public function detachOrganization(Request $request, Resident $resident): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
        ]);

        $organization = $this->residentRepository->findOrganizationById((int) $validated['organization_id']);

        if ($organization && $resident->removeFromOrganization($organization)) {
            return back()->with('success', "Resident removed from {$organization->name}");
        }

        return back()->withErrors(['error' => 'Cannot remove resident from their home organization or organization not found']);
    }

    /**
     * Get activity logs for a resident.
     */
    public function logs(Resident $resident): JsonResponse
    {
        $logs = $this->activityLogService->getLogs($resident);

        return response()->json([
            'logs' => $logs,
        ]);
    }
}
