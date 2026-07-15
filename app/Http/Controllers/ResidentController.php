<?php

namespace App\Http\Controllers;

use App\Exports\ResidentsExport;
use App\Http\Requests\AttachResidentOrganizationRequest;
use App\Http\Requests\DetachResidentOrganizationRequest;
use App\Http\Requests\StoreResidentRequest;
use App\Http\Requests\UpdateResidentRequest;
use App\Models\Resident;
use App\Repositories\Contracts\ResidentRepositoryInterface;
use App\Services\ActivityLog\ResidentActivityLogService;
use App\Services\ResidentManagementService;
use App\Services\ResidentReadService;
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
        protected ResidentReadService $residentReadService,
    ) {
    }
    /**
     * Display a listing of residents with search and filters.
     */
    public function index(Request $request): Response
    {
        $payload = $this->residentReadService->indexPayload(
            $request->only(['search', 'organization_id', 'year_level', 'status', 'course', 'sort', 'direction'])
        );

        return Inertia::render('residents/index', [
            'residents' => $payload['residents'],
            'organizations' => $payload['organizations'],
            'filters' => $request->only(['search', 'organization_id', 'year_level', 'status', 'course']),
            'yearLevels' => ['Pre Resident', 'First Year', 'Second Year', 'Third Year', 'Fourth Year', 'Graduate'],
            'statuses' => ['active', 'inactive'],
            'courses' => $payload['courses'],
            'yearLevelStats' => $payload['yearLevelStats'],
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
        return response()->json($this->residentReadService->showOrganizationsPayload($resident));
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
        $oldOrganizationId = $resident->organization_id;
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
            $oldOrganizationId,
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

        $this->residentManagementService->delete($resident);

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
    public function attachOrganization(AttachResidentOrganizationRequest $request, Resident $resident): RedirectResponse
    {
        $organization = $this->residentRepository->findOrganizationById((int) $request->validated('organization_id'));

        if ($organization && $this->residentManagementService->attachOrganization($resident, $organization)) {
            return back()->with('success', "Resident added to {$organization->name}");
        }

        return back()->withErrors(['error' => 'Resident is already associated with this organization']);
    }

    /**
     * Detach a resident from an organization.
     */
    public function detachOrganization(DetachResidentOrganizationRequest $request, Resident $resident): RedirectResponse
    {
        $organization = $this->residentRepository->findOrganizationById((int) $request->validated('organization_id'));

        if ($organization && $this->residentManagementService->detachOrganization($resident, $organization)) {
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
