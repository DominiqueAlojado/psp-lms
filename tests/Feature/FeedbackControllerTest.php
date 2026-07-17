<?php

namespace Tests\Feature;

use App\Models\FeedbackEntry;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FeedbackControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_feedback_page_loads_for_current_organization(): void
    {
        [$user, $organization] = $this->makeUserWithOrganization();
        $this->grantFeedbackPermissions($user);

        $response = $this->actingAs($user)->get('/feedback?org=' . $organization->slug);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('feedback/index')
            ->where('canCreateFeedback', true)
            ->where('isAllOrganizationsContext', false)
        );
    }

    public function test_user_can_submit_feedback_for_current_organization(): void
    {
        [$user, $organization] = $this->makeUserWithOrganization();
        $this->grantFeedbackPermissions($user);

        $response = $this->actingAs($user)->post('/feedback?org=' . $organization->slug, [
            'overall_rating' => 4,
            'content_rating' => 4,
            'support_rating' => 3,
            'usability_rating' => 4,
            'context' => 'After completing an exam',
            'module_name' => 'My Exams',
            'page_url' => '/resident-exams',
            'comment' => 'The exam flow was smooth and easy to follow.',
            'would_recommend' => true,
        ]);

        $response->assertRedirect('/feedback?org=' . $organization->slug);

        $this->assertDatabaseHas('feedback_entries', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'overall_rating' => 4,
            'module_name' => 'My Exams',
        ]);
    }

    public function test_all_organizations_context_disables_feedback_submission(): void
    {
        [$user, $organization] = $this->makeUserWithOrganization('System Admin');
        $this->grantFeedbackPermissions($user);

        $response = $this->actingAs($user)->post('/feedback?org=all-organizations', [
            'overall_rating' => 4,
            'content_rating' => 4,
            'support_rating' => 3,
            'usability_rating' => 4,
            'comment' => 'Blocked in aggregate context.',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('feedback_entries', 0);
    }

    public function test_feedback_routes_require_permissions(): void
    {
        [$user, $organization] = $this->makeUserWithOrganization();

        $this->actingAs($user)
            ->get('/feedback?org=' . $organization->slug)
            ->assertForbidden();

        Permission::findOrCreate('view-feedback', 'web');
        $user->givePermissionTo('view-feedback');

        $this->actingAs($user)
            ->post('/feedback?org=' . $organization->slug, [
                'overall_rating' => 4,
                'content_rating' => 4,
                'support_rating' => 3,
                'usability_rating' => 4,
                'comment' => 'Should be blocked without create permission.',
            ])
            ->assertForbidden();
    }

    public function test_staff_with_permission_can_access_feedback_manage_page(): void
    {
        [$user, $organization] = $this->makeUserWithOrganization();
        $this->grantFeedbackManagerPermissions($user);

        FeedbackEntry::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'overall_rating' => 4,
            'content_rating' => 4,
            'support_rating' => 3,
            'usability_rating' => 4,
            'module_name' => 'My Exams',
            'comment' => 'Useful workflow.',
            'would_recommend' => true,
        ]);

        $response = $this->actingAs($user)->get('/feedback/manage?org=' . $organization->slug);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('feedback/manage')
            ->where('entries.total', 1)
            ->where('canDeleteFeedback', true)
        );
    }

    public function test_user_without_manage_permission_cannot_access_feedback_manage_page(): void
    {
        [$user, $organization] = $this->makeUserWithOrganization();
        $this->grantFeedbackPermissions($user);

        $this->actingAs($user)
            ->get('/feedback/manage?org=' . $organization->slug)
            ->assertForbidden();
    }

    public function test_feedback_manager_can_delete_feedback_in_current_scope(): void
    {
        [$manager, $organization] = $this->makeUserWithOrganization();
        $this->grantFeedbackManagerPermissions($manager);

        $entry = FeedbackEntry::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $manager->id,
            'overall_rating' => 4,
            'content_rating' => 4,
            'support_rating' => 3,
            'usability_rating' => 4,
            'module_name' => 'Support',
            'comment' => 'Delete me.',
            'would_recommend' => false,
        ]);

        $this->actingAs($manager)
            ->delete('/feedback/' . $entry->id . '?org=' . $organization->slug)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('feedback_entries', [
            'id' => $entry->id,
        ]);
    }

    public function test_feedback_manager_cannot_delete_feedback_from_unmanaged_organization(): void
    {
        [$manager, $organization] = $this->makeUserWithOrganization();
        $otherOrganization = Organization::create([
            'name' => 'Beta Hospital',
            'slug' => 'beta-hospital',
            'type' => 'institution',
            'is_active' => true,
        ]);

        $this->grantFeedbackManagerPermissions($manager);

        $entry = FeedbackEntry::query()->create([
            'organization_id' => $otherOrganization->id,
            'user_id' => $manager->id,
            'overall_rating' => 4,
            'content_rating' => 4,
            'support_rating' => 3,
            'usability_rating' => 4,
            'module_name' => 'Support',
            'comment' => 'Should stay.',
            'would_recommend' => true,
        ]);

        $this->actingAs($manager)
            ->delete('/feedback/' . $entry->id . '?org=' . $organization->slug)
            ->assertForbidden();
    }

    private function makeUserWithOrganization(?string $role = null): array
    {
        $organization = Organization::create([
            'name' => 'Alpha Hospital',
            'slug' => 'alpha-hospital',
            'type' => 'institution',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $user->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        if ($role !== null) {
            Role::findOrCreate($role, 'web');
            $user->assignRole($role);
        }

        return [$user, $organization];
    }

    private function grantFeedbackPermissions(User $user): void
    {
        Permission::findOrCreate('view-feedback', 'web');
        Permission::findOrCreate('create-feedback', 'web');

        $user->givePermissionTo([
            'view-feedback',
            'create-feedback',
        ]);
    }

    private function grantFeedbackManagerPermissions(User $user): void
    {
        Permission::findOrCreate('view-all-feedback', 'web');
        Permission::findOrCreate('delete-feedback', 'web');

        $this->grantFeedbackPermissions($user);

        $user->givePermissionTo([
            'view-all-feedback',
            'delete-feedback',
        ]);
    }
}
