<?php

namespace App\Services;

use App\Actions\Resources\CreateLearningResourceAction;
use App\Actions\Resources\DeleteLearningResourceAction;
use App\Actions\Resources\IncrementLearningResourceDownloadAction;
use App\Actions\Resources\UpdateLearningResourceAction;
use App\Models\LearningResource;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ResourceManagementService
{
    public function __construct(
        private readonly CreateLearningResourceAction $createLearningResourceAction,
        private readonly UpdateLearningResourceAction $updateLearningResourceAction,
        private readonly DeleteLearningResourceAction $deleteLearningResourceAction,
        private readonly IncrementLearningResourceDownloadAction $incrementLearningResourceDownloadAction,
    ) {}

    public function create(User $user, array $validated, UploadedFile $file): LearningResource
    {
        $path = $file->store('resources', 'public');

        return $this->createLearningResourceAction->execute([
            'organization_id' => $user->current_organization_id,
            'uploaded_by' => $user->id,
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
    }

    public function update(LearningResource $resource, array $validated): bool
    {
        return $this->updateLearningResourceAction->execute($resource, $validated);
    }

    public function delete(LearningResource $resource): bool
    {
        if (Storage::disk('public')->exists($resource->file_path)) {
            Storage::disk('public')->delete($resource->file_path);
        }

        return $this->deleteLearningResourceAction->execute($resource);
    }

    public function incrementDownloadCount(LearningResource $resource): void
    {
        $this->incrementLearningResourceDownloadAction->execute($resource);
    }

    public function canAccess(User $user, LearningResource $resource): bool
    {
        return $resource->organization_id === $user->current_organization_id;
    }
}
