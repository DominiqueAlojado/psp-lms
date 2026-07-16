<?php

namespace Tests\Feature;

use App\Models\FeedbackEntry;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FeedbackControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_feedback_page_loads_for_current_organization(): void
    {
        [$user, $organization] = $this->makeUserWithOrganization();

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
}
