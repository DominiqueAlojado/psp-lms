<?php

namespace Tests\Unit;

use App\Models\LearningResource;
use App\Models\Organization;
use App\Models\User;
use App\Services\ResourceReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceReadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_index_payload(): void
    {
        $service = app(ResourceReadService::class);

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $uploader = User::factory()->create();

        LearningResource::create([
            'organization_id' => $organization->id,
            'uploaded_by' => $uploader->id,
            'title' => 'Study Guide',
            'description' => 'Guide',
            'category' => 'Guides',
            'file_path' => 'resources/guide.pdf',
            'file_name' => 'guide.pdf',
            'file_type' => 'pdf',
            'file_size' => 1024,
            'target_year_levels' => ['First Year'],
            'is_published' => true,
            'download_count' => 2,
        ]);

        $payload = $service->indexPayload($user, []);
        $item = $payload['resources']->items()[0];

        $this->assertSame('Study Guide', $item['title']);
        $this->assertSame('Guides', $item['category']);
        $this->assertSame($uploader->name, $item['uploaded_by']);
        $this->assertTrue($payload['categories']->contains('Guides'));
    }

    public function test_it_builds_manage_payload(): void
    {
        $service = app(ResourceReadService::class);

        $organization = Organization::create([
            'name' => 'Beta Chapter',
            'slug' => 'beta-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $uploader = User::factory()->create();

        LearningResource::create([
            'organization_id' => $organization->id,
            'uploaded_by' => $uploader->id,
            'title' => 'Video Lecture',
            'description' => 'Lecture',
            'category' => 'Videos',
            'file_path' => 'resources/lecture.mp4',
            'file_name' => 'lecture.mp4',
            'file_type' => 'mp4',
            'file_size' => 2048,
            'target_year_levels' => ['Second Year'],
            'is_published' => false,
            'download_count' => 0,
        ]);

        $payload = $service->managePayload($user, []);
        $item = $payload['resources']->items()[0];

        $this->assertSame('Video Lecture', $item['title']);
        $this->assertFalse($item['is_published']);
        $this->assertTrue($payload['categories']->contains('Videos'));
    }
}
