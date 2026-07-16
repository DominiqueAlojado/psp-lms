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
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class ResourceManagementService
{
    private const ALL_ORGANIZATIONS_SLUG = 'all-organizations';

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
            'organization_id' => $validated['scope'] === 'organization' ? $user->current_organization_id : null,
            'uploaded_by' => $user->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'],
            'scope' => $validated['scope'],
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'target_year_levels' => $validated['target_year_levels'] ?? null,
            'is_published' => $validated['is_published'] ?? true,
        ]);
    }

    public function update(User $user, LearningResource $resource, array $validated): bool
    {
        $validated['organization_id'] = ($validated['scope'] ?? $resource->scope) === 'organization'
            ? $user->current_organization_id
            : null;

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
        if ($this->includeAllOrganizations($user) && $user->hasAnyRole(['System Admin', 'BOP'])) {
            return true;
        }

        if ($resource->scope === 'system') {
            return true;
        }

        return $resource->organization_id === $user->current_organization_id;
    }

    public function canCreateSystem(User $user): bool
    {
        try {
            return $user->hasPermissionTo('create-system-announcements')
                || $user->hasAnyRole(['System Admin', 'BOP']);
        } catch (PermissionDoesNotExist) {
            return $user->hasAnyRole(['System Admin', 'BOP']);
        }
    }

    public function canManageResource(User $user, LearningResource $resource): bool
    {
        if ($this->includeAllOrganizations($user) && $user->hasAnyRole(['System Admin', 'BOP'])) {
            return true;
        }

        if ($resource->scope === 'system') {
            return $this->canCreateSystem($user);
        }

        return $resource->organization_id === $user->current_organization_id;
    }

    private function includeAllOrganizations(User $user): bool
    {
        return request()->query('org') === self::ALL_ORGANIZATIONS_SLUG
            && $user->hasAnyRole(['System Admin', 'BOP']);
    }
}
