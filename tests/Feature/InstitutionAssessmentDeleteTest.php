<?php

namespace Tests\Feature;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionAttempt;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InstitutionAssessmentDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_delete_assessment_without_attempts(): void
    {
        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \App\Http\Middleware\SetOrganizationFromUrl::class,
        ]);

        $organization = Organization::factory()->create([
            'name' => 'Delete Org',
            'slug' => 'delete-org',
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $user->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        Permission::firstOrCreate(['name' => 'delete-assessments', 'guard_name' => 'web']);
        $user->givePermissionTo('delete-assessments');

        $assessment = InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'Delete Me',
            'description' => null,
            'exam_category' => 'Practice Exam',
            'duration_minutes' => 30,
            'total_points' => 0,
            'passing_score' => 20,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => false,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->delete(route('assessments.destroy', $assessment));

        $response->assertSessionHas('success', 'Assessment deleted successfully');
        $this->assertSoftDeleted('institution_assessments', [
            'id' => $assessment->id,
        ]);
    }

    public function test_user_cannot_delete_assessment_with_attempts(): void
    {
        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \App\Http\Middleware\SetOrganizationFromUrl::class,
        ]);

        $organization = Organization::factory()->create([
            'name' => 'Attempt Org',
            'slug' => 'attempt-org',
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $resident = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $user->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);
        $resident->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        Permission::firstOrCreate(['name' => 'delete-assessments', 'guard_name' => 'web']);
        $user->givePermissionTo('delete-assessments');

        $assessment = InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'Locked Assessment',
            'description' => null,
            'exam_category' => 'Practice Exam',
            'duration_minutes' => 30,
            'total_points' => 0,
            'passing_score' => 20,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => true,
            'created_by' => $user->id,
        ]);

        InstitutionAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $resident->id,
            'organization_id' => $organization->id,
            'started_at' => now(),
            'score' => 0,
            'total_points' => 0,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($user)->delete(route('assessments.destroy', $assessment));

        $response->assertSessionHasErrors([
            'error' => 'Cannot delete assessment that has been attempted by residents.',
        ]);
        $this->assertDatabaseHas('institution_assessments', [
            'id' => $assessment->id,
        ]);
    }
}
