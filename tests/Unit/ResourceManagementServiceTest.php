<?php

namespace Tests\Unit;

use App\Models\LearningResource;
use App\Models\Organization;
use App\Models\User;
use App\Services\ResourceManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResourceManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_learning_resource_and_stores_the_file(): void
    {
        Storage::fake('public');

        $service = app(ResourceManagementService::class);

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $file = UploadedFile::fake()->create('guide.pdf', 100, 'application/pdf');

        $resource = $service->create($user, [
            'title' => 'Study Guide',
            'description' => 'Guide',
            'category' => 'Guides',
            'target_year_levels' => ['First Year'],
            'is_published' => true,
        ], $file);

        $this->assertSame($organization->id, $resource->organization_id);
        $this->assertSame($user->id, $resource->uploaded_by);
        Storage::disk('public')->assertExists($resource->file_path);
    }

    public function test_it_updates_a_learning_resource(): void
    {
        $service = app(ResourceManagementService::class);

        $organization = Organization::create([
            'name' => 'Beta Chapter',
            'slug' => 'beta-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $resource = LearningResource::create([
            'organization_id' => $organization->id,
            'uploaded_by' => $user->id,
            'title' => 'Old Title',
            'description' => 'Old description',
            'category' => 'Handouts',
            'file_path' => 'resources/old.pdf',
            'file_name' => 'old.pdf',
            'file_type' => 'pdf',
            'file_size' => 1024,
            'target_year_levels' => ['First Year'],
            'is_published' => true,
            'download_count' => 0,
        ]);

        $this->assertTrue($service->update($resource, [
            'title' => 'New Title',
            'description' => 'New description',
            'category' => 'Videos',
            'target_year_levels' => ['Second Year'],
            'is_published' => false,
        ]));

        $resource->refresh();

        $this->assertSame('New Title', $resource->title);
        $this->assertFalse($resource->is_published);
    }

    public function test_it_deletes_a_learning_resource_and_its_file(): void
    {
        Storage::fake('public');

        $service = app(ResourceManagementService::class);

        $organization = Organization::create([
            'name' => 'Gamma Chapter',
            'slug' => 'gamma-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $user = User::factory()->create();

        Storage::disk('public')->put('resources/delete.pdf', 'content');

        $resource = LearningResource::create([
            'organization_id' => $organization->id,
            'uploaded_by' => $user->id,
            'title' => 'Delete Me',
            'description' => 'Delete',
            'category' => 'Guides',
            'file_path' => 'resources/delete.pdf',
            'file_name' => 'delete.pdf',
            'file_type' => 'pdf',
            'file_size' => 7,
            'target_year_levels' => null,
            'is_published' => true,
            'download_count' => 0,
        ]);

        $this->assertTrue($service->delete($resource));
        Storage::disk('public')->assertMissing('resources/delete.pdf');
        $this->assertSoftDeleted('learning_resources', ['id' => $resource->id]);
    }

    public function test_it_increments_download_count_and_checks_access(): void
    {
        $service = app(ResourceManagementService::class);

        $organization = Organization::create([
            'name' => 'Home Chapter',
            'slug' => 'home-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $otherOrganization = Organization::create([
            'name' => 'Other Chapter',
            'slug' => 'other-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $resource = LearningResource::create([
            'organization_id' => $organization->id,
            'uploaded_by' => $user->id,
            'title' => 'Download Me',
            'description' => 'Download',
            'category' => 'Guides',
            'file_path' => 'resources/download.pdf',
            'file_name' => 'download.pdf',
            'file_type' => 'pdf',
            'file_size' => 7,
            'target_year_levels' => null,
            'is_published' => true,
            'download_count' => 0,
        ]);
        $otherResource = LearningResource::create([
            'organization_id' => $otherOrganization->id,
            'uploaded_by' => $user->id,
            'title' => 'Other Resource',
            'description' => 'Other',
            'category' => 'Guides',
            'file_path' => 'resources/other.pdf',
            'file_name' => 'other.pdf',
            'file_type' => 'pdf',
            'file_size' => 7,
            'target_year_levels' => null,
            'is_published' => true,
            'download_count' => 0,
        ]);

        $service->incrementDownloadCount($resource);

        $this->assertSame(1, $resource->fresh()->download_count);
        $this->assertTrue($service->canAccess($user, $resource));
        $this->assertFalse($service->canAccess($user, $otherResource));
    }
}
