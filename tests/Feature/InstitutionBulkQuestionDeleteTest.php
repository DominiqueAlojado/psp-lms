<?php

namespace Tests\Feature;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InstitutionBulkQuestionDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_bulk_delete_questions_from_assessment(): void
    {
        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \App\Http\Middleware\SetOrganizationFromUrl::class,
        ]);

        $organization = Organization::factory()->create([
            'name' => 'Bulk Delete Org',
            'slug' => 'bulk-delete-org',
            'type' => 'institution',
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $user->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        Permission::firstOrCreate(['name' => 'edit-assessments', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $role->givePermissionTo('edit-assessments');
        $user->assignRole($role);

        $assessment = InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'Bulk Delete Exam',
            'duration_minutes' => 60,
            'total_points' => 10,
            'passing_score' => 5,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => false,
            'created_by' => $user->id,
        ]);

        $questionOne = $assessment->questions()->create([
            'question_type' => 'multiple_choice',
            'question_text' => 'Question one',
            'points' => 4,
            'order' => 0,
        ]);

        $questionTwo = $assessment->questions()->create([
            'question_type' => 'multiple_choice',
            'question_text' => 'Question two',
            'points' => 6,
            'order' => 1,
        ]);

        $response = $this->actingAs($user)->delete(
            route('assessments.questions.bulk-delete', $assessment),
            ['question_ids' => [$questionOne->id, $questionTwo->id]],
        );

        $response->assertSessionHas('success', 'Deleted 2 questions successfully');
        $this->assertSoftDeleted('institution_questions', ['id' => $questionOne->id]);
        $this->assertSoftDeleted('institution_questions', ['id' => $questionTwo->id]);
        $this->assertSame(0, (int) $assessment->fresh()->total_points);
    }
}
