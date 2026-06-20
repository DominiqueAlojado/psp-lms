<?php

namespace App\Http\Controllers;

use App\Exports\InstitutionsExport;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use App\Services\ActivityLog\OrganizationActivityLogService;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrganizationController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected OrganizationActivityLogService $activityLogService,
        protected OrganizationRepositoryInterface $organizationRepository
    ) {}
    /**
     * Display a listing of institutions.
     */
    public function index(Request $request): Response
    {
        $institutions = $this->organizationRepository
            ->paginate($request->only(['search', 'type', 'status', 'sort', 'direction']))
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

        $typeStats = $this->organizationRepository->getTypeStats();
        $types = $this->organizationRepository->getDistinctTypes();

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
    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Generate slug from name
        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $validated['is_active'] ?? true;

        // Decode training officers JSON if provided
        if (isset($validated['training_officers'])) {
            $validated['training_officers'] = json_decode($validated['training_officers'], true);
        }

        $institution = $this->organizationRepository->create($validated);

        // Log organization creation
        $this->activityLogService->logOrganizationCreated($institution);

        return back()->with('success', 'Institution created successfully');
    }

    /**
     * Update the specified institution.
     */
    public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        // Prevent duplicate submissions
        $requestId = $request->header('X-Request-ID') ?: uniqid('update_', true);
        $cacheKey = "organization_update_{$organization->id}_{$requestId}";

        // Check if we've already processed this update (prevent duplicates)
        if (cache()->has($cacheKey)) {
            return back()->with('success', 'Institution updated successfully');
        }

        $validated = $request->validated();

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
        $this->withoutActivityLogging(function () use ($organization, $validated) {
            $this->organizationRepository->update($organization, $validated);
        });

        // Build consolidated log entry with all changes using service
        $logData = $this->activityLogService->buildUpdateLogData(
            $organization,
            $validated,
            $oldName,
            $oldDescription,
            $oldType,
            $oldIsActive,
            $oldTrainingOfficers
        );

        // Log all changes in a single entry
        if ($logData['hasChanges']) {
            $this->activityLogService->logOrganizationUpdated(
                $organization,
                $logData['attributes'],
                $logData['oldValues']
            );
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
        if ($this->organizationRepository->hasResidents($organization)) {
            return back()->withErrors([
                'error' => 'Cannot delete institution with active residents. Please reassign or delete residents first.',
            ]);
        }

        // Log deletion before deleting
        $this->activityLogService->logOrganizationDeleted($organization);

        $this->organizationRepository->delete($organization);

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
        $logs = $this->activityLogService->getLogs($organization);

        return response()->json([
            'logs' => $logs,
        ]);
    }
}
