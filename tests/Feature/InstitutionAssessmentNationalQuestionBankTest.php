<?php

namespace Tests\Feature;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Organization;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InstitutionAssessmentNationalQuestionBankTest extends TestCase
{
    use RefreshDatabase;

    public function test_national_org_institution_assessment_can_add_from_national_question_bank(): void
    {
        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \App\Http\Middleware\SetOrganizationFromUrl::class,
        ]);

        $organization = Organization::factory()->create([
            'name' => 'In-Service Exams',
            'slug' => 'in-service-exams',
            'type' => 'national',
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $user->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        Permission::firstOrCreate(['name' => 'create-assessments', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $role->givePermissionTo('create-assessments');
        $user->assignRole($role);

        $assessment = InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'Misrouted National Exam',
            'description' => 'Created in the wrong flow',
            'duration_minutes' => 60,
            'total_points' => 0,
            'passing_score' => 40,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => false,
            'created_by' => $user->id,
        ]);

        $bankQuestion = QuestionBank::create([
            'organization_id' => null,
            'owner_type' => 'national',
            'created_by' => $user->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'National bank question',
            'points' => 5,
            'is_approved' => true,
        ]);

        $bankQuestion->choices()->createMany([
            ['choice_text' => 'Correct', 'is_correct' => true, 'order' => 0],
            ['choice_text' => 'Wrong', 'is_correct' => false, 'order' => 1],
        ]);

        $response = $this->actingAs($user)->post(
            route('assessments.questions.from-bank', $assessment),
            ['question_ids' => [$bankQuestion->id]]
        );

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('institution_questions', [
            'assessment_id' => $assessment->id,
            'question_text' => 'National bank question',
        ]);
    }
}
