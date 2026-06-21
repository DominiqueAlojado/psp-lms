<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLearningResourceRequest;
use App\Http\Requests\UpdateLearningResourceRequest;
use App\Models\LearningResource;
use App\Services\ActivityLog\ResourceActivityLogService;
use App\Services\ResourceManagementService;
use App\Services\ResourceReadService;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ResourceController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected ResourceActivityLogService $activityLogService,
        protected ResourceManagementService $resourceManagementService,
        protected ResourceReadService $resourceReadService,
    ) {}

    /**
     * Display a listing of resources for residents.
     */
    public function index(Request $request): Response
    {
        $payload = $this->resourceReadService->indexPayload(
            $request->user(),
            $request->only(['search', 'category'])
        );

        return Inertia::render('resources/index', [
            'resources' => $payload['resources'],
            'categories' => $payload['categories'],
            'filters' => $request->only(['search', 'category']),
        ]);
    }

    /**
     * Display resource management page for training officers.
     */
    public function manage(Request $request): Response
    {
        $payload = $this->resourceReadService->managePayload(
            $request->user(),
            $request->only(['search', 'category', 'is_published'])
        );

        return Inertia::render('resources/manage', [
            'resources' => $payload['resources'],
            'categories' => $payload['categories'],
            'filters' => $request->only(['search', 'category', 'is_published']),
        ]);
    }

    /**
     * Store a newly uploaded resource.
     */
    public function store(StoreLearningResourceRequest $request): RedirectResponse
    {
        Log::info('Resource upload attempt', [
            'user' => $request->user()->email,
            'has_file' => $request->hasFile('file'),
            'all_data' => $request->except(['file']),
        ]);

        try {
            $validated = $request->validated();

            if (! $request->hasFile('file')) {
                return back()->withErrors(['file' => 'No file uploaded']);
            }

            $file = $request->file('file');

            if (! $file->isValid()) {
                return back()->withErrors(['file' => 'File upload failed - invalid file']);
            }

            // Store file in public disk under resources folder
            $path = $file->store('resources', 'public');

            if (! $path) {
                return back()->withErrors(['file' => 'Failed to store file']);
            }

            $resource = $this->resourceManagementService->create($request->user(), $validated, $file);

            // Log resource creation
            $this->activityLogService->logResourceCreated($resource);

            Log::info('Resource created successfully', ['id' => $resource->id, 'title' => $resource->title]);

            return redirect('/resources/manage')->with('success', 'Resource uploaded successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Resource validation failed', ['errors' => $e->errors()]);

            return back()->withErrors($e->errors());
        } catch (\Exception $e) {
            Log::error('Resource upload failed', ['error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to upload resource: ' . $e->getMessage()]);
        }
    }

    /**
     * Update the specified resource.
     */
    public function update(UpdateLearningResourceRequest $request, LearningResource $resource): RedirectResponse
    {
        // Verify user has access
        if (! $this->resourceManagementService->canAccess($request->user(), $resource)) {
            abort(403, 'You do not have access to this resource.');
        }

        $validated = $request->validated();

        // Capture old values before update
        $oldValues = [
            'title' => $resource->title,
            'description' => $resource->description,
            'category' => $resource->category,
            'target_year_levels' => $resource->target_year_levels,
            'is_published' => $resource->is_published,
        ];

        // Update resource without logging (to avoid duplicate logs)
        $this->withoutActivityLogging(function () use ($resource, $validated) {
            $this->resourceManagementService->update($resource, $validated);
        });

        // Build log data and log changes
        $logData = $this->activityLogService->buildUpdateLogData($resource, $validated, $oldValues);
        if (! empty($logData['attributes']) || ! empty($logData['old'])) {
            $this->activityLogService->logResourceUpdated($resource, $logData['attributes'], $logData['old']);
        }

        return back()->with('success', 'Resource updated successfully!');
    }

    /**
     * Remove the specified resource.
     */
    public function destroy(Request $request, LearningResource $resource): RedirectResponse
    {
        // Verify user has access
        if (! $this->resourceManagementService->canAccess($request->user(), $resource)) {
            abort(403, 'You do not have access to this resource.');
        }

        // Log resource deletion before deleting
        $this->activityLogService->logResourceDeleted($resource);

        $this->resourceManagementService->delete($resource);

        return back()->with('success', 'Resource deleted successfully!');
    }

    /**
     * Get activity logs for a resource.
     */
    public function logs(Request $request, LearningResource $resource): JsonResponse
    {
        // Verify user has access
        if (! $this->resourceManagementService->canAccess($request->user(), $resource)) {
            abort(403, 'You do not have access to this resource.');
        }

        $logs = $this->activityLogService->getLogs($resource);

        return response()->json([
            'logs' => $logs,
        ]);
    }

    /**
     * Download the specified resource.
     */
    public function download(Request $request, LearningResource $resource)
    {
        // Verify user has access
        if (! $this->resourceManagementService->canAccess($request->user(), $resource)) {
            abort(403, 'You do not have access to this resource.');
        }

        // Increment download count
        $this->resourceManagementService->incrementDownloadCount($resource);

        return \Illuminate\Support\Facades\Storage::disk('public')->download($resource->file_path, $resource->file_name);
    }
}
