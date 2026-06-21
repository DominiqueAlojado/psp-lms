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

class InstitutionQuestionBankDuplicatePreventionTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_from_bank_skips_questions_already_in_exam(): void
    {
        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \App\Http\Middleware\SetOrganizationFromUrl::class,
        ]);

        $organization = Organization::factory()->create([
            'name' => 'Duplicate Guard Org',
            'slug' => 'duplicate-guard-org',
            'type' => 'institution',
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
            'title' => 'Duplicate Safe Exam',
            'duration_minutes' => 60,
            'total_points' => 5,
            'passing_score' => 3,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => false,
            'created_by' => $user->id,
        ]);

        $assessment->questions()->create([
            'question_type' => 'multiple_choice',
            'question_text' => '<p>Existing question</p>',
            'points' => 5,
            'order' => 0,
        ]);

        $bankQuestion = QuestionBank::create([
            'organization_id' => $organization->id,
            'owner_type' => 'institution',
            'created_by' => $user->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Existing question',
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

        $response->assertSessionHas('warning', 'All selected questions are already in this exam.');
        $this->assertSame(1, $assessment->fresh()->questions()->count());
    }
}
