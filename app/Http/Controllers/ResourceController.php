<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLearningResourceRequest;
use App\Http\Requests\UpdateLearningResourceRequest;
use App\Models\LearningResource;
use App\Repositories\Contracts\LearningResourceRepositoryInterface;
use App\Services\ActivityLog\ResourceActivityLogService;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ResourceController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected ResourceActivityLogService $activityLogService,
        protected LearningResourceRepositoryInterface $learningResourceRepository
    ) {}

    /**
     * Display a listing of resources for residents.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $organizationId = $user->current_organization_id;

        $resources = $this->learningResourceRepository
            ->paginatePublishedByOrganization($organizationId, $request->only(['search', 'category']))
            ->through(fn($resource) => [
                'id' => $resource->id,
                'title' => $resource->title,
                'description' => $resource->description,
                'category' => $resource->category,
                'file_name' => $resource->file_name,
                'file_type' => $resource->file_type,
                'file_size_formatted' => $resource->file_size_formatted,
                'target_year_levels' => $resource->target_year_levels,
                'download_count' => $resource->download_count,
                'uploaded_by' => $resource->uploader->name,
                'created_at' => $resource->created_at->format('M d, Y'),
            ]);

        $categories = $this->learningResourceRepository->getPublishedCategoriesByOrganization($organizationId);

        return Inertia::render('resources/index', [
            'resources' => $resources,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category']),
        ]);
    }

    /**
     * Display resource management page for training officers.
     */
    public function manage(Request $request): Response
    {
        $user = $request->user();
        $organizationId = $user->current_organization_id;

        $resources = $this->learningResourceRepository
            ->paginateForManagementByOrganization($organizationId, $request->only(['search', 'category', 'is_published']))
            ->through(fn($resource) => [
                'id' => $resource->id,
                'title' => $resource->title,
                'description' => $resource->description,
                'category' => $resource->category,
                'file_name' => $resource->file_name,
                'file_type' => $resource->file_type,
                'file_size_formatted' => $resource->file_size_formatted,
                'file_url' => $resource->file_url,
                'target_year_levels' => $resource->target_year_levels,
                'is_published' => $resource->is_published,
                'download_count' => $resource->download_count,
                'uploaded_by' => $resource->uploader->name,
                'created_at' => $resource->created_at->format('M d, Y'),
                'updated_at' => $resource->updated_at->diffForHumans(),
            ]);

        $categories = $this->learningResourceRepository->getCategoriesByOrganization($organizationId);

        return Inertia::render('resources/manage', [
            'resources' => $resources,
            'categories' => $categories,
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

            $resource = $this->learningResourceRepository->create([
                'organization_id' => $request->user()->current_organization_id,
                'uploaded_by' => $request->user()->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'category' => $validated['category'],
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'target_year_levels' => $validated['target_year_levels'] ?? null,
                'is_published' => $validated['is_published'] ?? true,
            ]);

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
        if ($resource->organization_id !== $request->user()->current_organization_id) {
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
            $this->learningResourceRepository->update($resource, $validated);
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
        if ($resource->organization_id !== $request->user()->current_organization_id) {
            abort(403, 'You do not have access to this resource.');
        }

        // Log resource deletion before deleting
        $this->activityLogService->logResourceDeleted($resource);

        // Delete the file from storage
        if (Storage::disk('public')->exists($resource->file_path)) {
            Storage::disk('public')->delete($resource->file_path);
        }

        $this->learningResourceRepository->delete($resource);

        return back()->with('success', 'Resource deleted successfully!');
    }

    /**
     * Get activity logs for a resource.
     */
    public function logs(Request $request, LearningResource $resource): JsonResponse
    {
        // Verify user has access
        if ($resource->organization_id !== $request->user()->current_organization_id) {
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
        if ($resource->organization_id !== $request->user()->current_organization_id) {
            abort(403, 'You do not have access to this resource.');
        }

        // Increment download count
        $this->learningResourceRepository->incrementDownloadCount($resource);

        return Storage::disk('public')->download($resource->file_path, $resource->file_name);
    }
}
