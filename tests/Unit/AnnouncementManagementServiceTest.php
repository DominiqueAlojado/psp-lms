<?php

namespace Tests\Unit;

use App\Models\Announcement;
use App\Models\Organization;
use App\Models\User;
use App\Services\AnnouncementManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AnnouncementManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_announcement(): void
    {
        $service = app(AnnouncementManagementService::class);

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $announcement = $service->create($user, [
            'title' => 'Notice',
            'content' => 'Body',
            'scope' => 'organization',
            'priority' => 'important',
        ]);

        $this->assertSame($organization->id, $announcement->organization_id);
        $this->assertSame($user->id, $announcement->created_by);
        $this->assertTrue($announcement->is_published);
        $this->assertFalse($announcement->is_pinned);
    }

    public function test_it_updates_an_announcement_and_returns_attributes(): void
    {
        $service = app(AnnouncementManagementService::class);

        $organization = Organization::create([
            'name' => 'Beta Chapter',
            'slug' => 'beta-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $announcement = Announcement::create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'title' => 'Original',
            'content' => 'Original body',
            'scope' => 'organization',
            'priority' => 'normal',
            'is_published' => true,
            'is_pinned' => false,
            'views_count' => 0,
        ]);

        $attributes = $service->update($user, $announcement, [
            'title' => 'Updated',
            'content' => 'Updated body',
            'scope' => 'organization',
            'priority' => 'urgent',
            'is_published' => false,
            'is_pinned' => true,
        ]);

        $announcement->refresh();

        $this->assertSame('Updated', $announcement->title);
        $this->assertSame('urgent', $announcement->priority);
        $this->assertFalse($announcement->is_published);
        $this->assertTrue($announcement->is_pinned);
        $this->assertSame('Updated', $attributes['title']);
    }

    public function test_it_marks_an_announcement_as_viewed(): void
    {
        $service = app(AnnouncementManagementService::class);

        $organization = Organization::create([
            'name' => 'Gamma Chapter',
            'slug' => 'gamma-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $creator = User::factory()->create();
        $announcement = Announcement::create([
            'organization_id' => $organization->id,
            'created_by' => $creator->id,
            'title' => 'Viewed notice',
            'content' => 'Viewed body',
            'scope' => 'organization',
            'priority' => 'normal',
            'is_published' => true,
            'is_pinned' => false,
            'views_count' => 0,
        ]);
        $user = User::factory()->create();

        $service->markAsViewed($announcement, $user->id);

        $this->assertTrue($announcement->fresh()->hasBeenViewedBy($user->id));
        $this->assertSame(1, $announcement->fresh()->views_count);
    }

    public function test_it_checks_management_access(): void
    {
        $service = app(AnnouncementManagementService::class);

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
        Permission::create(['name' => 'create-system-announcements', 'guard_name' => 'web']);
        $creator = User::factory()->create();

        $orgAnnouncement = Announcement::create([
            'organization_id' => $organization->id,
            'created_by' => $creator->id,
            'title' => 'Org notice',
            'content' => 'Org body',
            'scope' => 'organization',
            'priority' => 'normal',
            'is_published' => true,
            'is_pinned' => false,
            'views_count' => 0,
        ]);
        $otherOrgAnnouncement = Announcement::create([
            'organization_id' => $otherOrganization->id,
            'created_by' => $creator->id,
            'title' => 'Other notice',
            'content' => 'Other body',
            'scope' => 'organization',
            'priority' => 'normal',
            'is_published' => true,
            'is_pinned' => false,
            'views_count' => 0,
        ]);
        $systemAnnouncement = Announcement::create([
            'organization_id' => null,
            'created_by' => $creator->id,
            'title' => 'System notice',
            'content' => 'System body',
            'scope' => 'system',
            'priority' => 'urgent',
            'is_published' => true,
            'is_pinned' => false,
            'views_count' => 0,
        ]);

        $this->assertTrue($service->canManageAnnouncement($user, $orgAnnouncement));
        $this->assertFalse($service->canManageAnnouncement($user, $otherOrgAnnouncement));
        $this->assertFalse($service->canManageAnnouncement($user, $systemAnnouncement));

        $user->givePermissionTo('create-system-announcements');
        $this->assertTrue($service->canManageAnnouncement($user, $systemAnnouncement));

        Role::create(['name' => 'System Admin', 'guard_name' => 'web']);
        $user->assignRole('System Admin');
        $this->assertTrue($service->canManageAnnouncement($user, $otherOrgAnnouncement));
    }
}
