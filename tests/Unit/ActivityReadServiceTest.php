<?php

namespace Tests\Unit;

use App\Models\Announcement;
use App\Models\Organization;
use App\Models\User;
use App\Services\ActivityReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ActivityReadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_activity_feed_for_current_org_and_system_scope(): void
    {
        $service = app(ActivityReadService::class);

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $otherOrganization = Organization::create([
            'name' => 'Beta Chapter',
            'slug' => 'beta-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        Permission::findOrCreate('view-activity-logs', 'web');

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $user->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);
        $user->givePermissionTo('view-activity-logs');

        $currentAnnouncement = Announcement::create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'title' => 'Current Org Notice',
            'content' => 'Body',
            'scope' => 'organization',
            'priority' => 'normal',
            'is_published' => true,
            'is_pinned' => false,
            'views_count' => 0,
        ]);

        $systemAnnouncement = Announcement::create([
            'organization_id' => null,
            'created_by' => $user->id,
            'title' => 'System Notice',
            'content' => 'Body',
            'scope' => 'system',
            'priority' => 'important',
            'is_published' => true,
            'is_pinned' => false,
            'views_count' => 0,
        ]);

        $otherAnnouncement = Announcement::create([
            'organization_id' => $otherOrganization->id,
            'created_by' => $user->id,
            'title' => 'Other Org Notice',
            'content' => 'Body',
            'scope' => 'organization',
            'priority' => 'urgent',
            'is_published' => true,
            'is_pinned' => false,
            'views_count' => 0,
        ]);

        activity()
            ->performedOn($currentAnnouncement)
            ->causedBy($user)
            ->useLog('announcements')
            ->withProperties([
                'attributes' => ['title' => $currentAnnouncement->title],
            ])
            ->log('Announcement created');

        activity()
            ->performedOn($systemAnnouncement)
            ->causedBy($user)
            ->useLog('announcements')
            ->withProperties([
                'attributes' => ['title' => $systemAnnouncement->title],
            ])
            ->log('Announcement created');

        activity()
            ->performedOn($otherAnnouncement)
            ->causedBy($user)
            ->useLog('announcements')
            ->withProperties([
                'attributes' => ['title' => $otherAnnouncement->title],
            ])
            ->log('Announcement created');

        $payload = $service->indexPayload($user, []);
        $subjects = collect($payload['activities']->items())->pluck('subject_label');

        $this->assertTrue($subjects->contains('Current Org Notice'));
        $this->assertTrue($subjects->contains('System Notice'));
        $this->assertFalse($subjects->contains('Other Org Notice'));
        $this->assertSame(2, $payload['summary']['total']);
    }
}
