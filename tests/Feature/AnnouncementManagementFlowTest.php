<?php

namespace Tests\Feature;

use App\Http\Middleware\SetOrganizationFromUrl;
use App\Models\Announcement;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AnnouncementManagementFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_organization_announcement(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        Permission::create(['name' => 'create-announcements', 'guard_name' => 'web']);
        Permission::create(['name' => 'create-system-announcements', 'guard_name' => 'web']);

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $user->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);
        $user->givePermissionTo('create-announcements');

        $response = $this->actingAs($user)
            ->from(route('announcements.manage'))
            ->post(route('announcements.store'), [
                'title' => 'Important Notice',
                'content' => 'Announcement body',
                'scope' => 'organization',
                'priority' => 'important',
                'is_published' => true,
                'is_pinned' => true,
                'target_year_levels' => ['First Year'],
                'expires_at' => now()->addDays(3)->toDateString(),
            ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect('/announcements/manage');

        $announcement = Announcement::where('title', 'Important Notice')->first();

        $this->assertNotNull($announcement);
        $this->assertSame($organization->id, $announcement->organization_id);
        $this->assertSame($user->id, $announcement->created_by);
        $this->assertSame('organization', $announcement->scope);
        $this->assertSame('important', $announcement->priority);
        $this->assertTrue($announcement->is_published);
        $this->assertTrue($announcement->is_pinned);
        $this->assertSame(['First Year'], $announcement->target_year_levels);
    }

    public function test_non_system_user_cannot_create_system_announcement(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        Permission::create(['name' => 'create-announcements', 'guard_name' => 'web']);
        Permission::create(['name' => 'create-system-announcements', 'guard_name' => 'web']);

        $organization = Organization::create([
            'name' => 'Beta Chapter',
            'slug' => 'beta-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $user->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);
        $user->givePermissionTo('create-announcements');

        $response = $this->actingAs($user)
            ->from(route('announcements.manage'))
            ->post(route('announcements.store'), [
                'title' => 'System Notice',
                'content' => 'Announcement body',
                'scope' => 'system',
                'priority' => 'urgent',
                'is_published' => true,
                'is_pinned' => false,
            ]);

        $response->assertSessionHasErrors('scope')
            ->assertRedirect(route('announcements.manage'));

        $this->assertDatabaseMissing('announcements', [
            'title' => 'System Notice',
        ]);
    }

    public function test_authorized_user_can_update_organization_announcement(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        Permission::create(['name' => 'edit-announcements', 'guard_name' => 'web']);
        Permission::create(['name' => 'create-system-announcements', 'guard_name' => 'web']);

        $organization = Organization::create([
            'name' => 'Gamma Chapter',
            'slug' => 'gamma-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $user->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);
        $user->givePermissionTo('edit-announcements');

        $announcement = Announcement::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'title' => 'Old Title',
            'content' => 'Old content',
            'scope' => 'organization',
            'priority' => 'normal',
            'is_published' => true,
            'is_pinned' => false,
        ]);

        $response = $this->actingAs($user)
            ->from(route('announcements.manage'))
            ->patch(route('announcements.update', $announcement), [
                'title' => 'Updated Title',
                'content' => 'Updated content',
                'scope' => 'organization',
                'priority' => 'urgent',
                'is_published' => false,
                'is_pinned' => true,
                'target_year_levels' => ['Second Year'],
                'expires_at' => now()->addDays(5)->toDateString(),
            ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('announcements.manage'));

        $announcement->refresh();

        $this->assertSame('Updated Title', $announcement->title);
        $this->assertSame('Updated content', $announcement->content);
        $this->assertSame('urgent', $announcement->priority);
        $this->assertFalse($announcement->is_published);
        $this->assertTrue($announcement->is_pinned);
        $this->assertSame(['Second Year'], $announcement->target_year_levels);
    }
}
