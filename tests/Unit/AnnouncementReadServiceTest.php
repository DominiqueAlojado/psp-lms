<?php

namespace Tests\Unit;

use App\Models\Announcement;
use App\Models\Organization;
use App\Models\User;
use App\Services\AnnouncementReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementReadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_index_payload(): void
    {
        $service = app(AnnouncementReadService::class);

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        Announcement::create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'title' => 'Org notice',
            'content' => 'Announcement body',
            'scope' => 'organization',
            'priority' => 'important',
            'is_published' => true,
            'is_pinned' => false,
            'views_count' => 0,
        ]);

        $payload = $service->indexPayload($user, []);
        $item = $payload['announcements']->items()[0];

        $this->assertSame('Org notice', $item['title']);
        $this->assertSame($user->name, $item['created_by']);
    }

    public function test_it_builds_manage_payload(): void
    {
        $service = app(AnnouncementReadService::class);

        $organization = Organization::create([
            'name' => 'Beta Chapter',
            'slug' => 'beta-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $permission = \Spatie\Permission\Models\Permission::create([
            'name' => 'create-system-announcements',
            'guard_name' => 'web',
        ]);
        $user->givePermissionTo($permission);

        Announcement::create([
            'organization_id' => null,
            'created_by' => $user->id,
            'title' => 'System notice',
            'content' => 'Announcement body',
            'scope' => 'system',
            'priority' => 'urgent',
            'is_published' => true,
            'is_pinned' => false,
            'views_count' => 0,
        ]);

        $payload = $service->managePayload($user, []);

        $this->assertTrue($payload['canCreateSystem']);
        $this->assertSame('System notice', $payload['announcements']->items()[0]['title']);
    }
}
