<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuestionBankSelectorScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_question_bank_list_can_be_forced_to_institution_scope(): void
    {
        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \App\Http\Middleware\SetOrganizationFromUrl::class,
        ]);

        $organization = Organization::factory()->create([
            'name' => 'Scope Org',
            'slug' => 'scope-org',
            'type' => 'national',
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $user->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        Permission::firstOrCreate(['name' => 'view-assessments', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $role->givePermissionTo('view-assessments');
        $user->assignRole($role);

        $institutionQuestion = QuestionBank::create([
            'organization_id' => $organization->id,
            'owner_type' => 'institution',
            'created_by' => $user->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Institution-only question',
            'points' => 2,
            'is_approved' => true,
        ]);

        $nationalQuestion = QuestionBank::create([
            'organization_id' => null,
            'owner_type' => 'national',
            'created_by' => $user->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'National-only question',
            'points' => 2,
            'is_approved' => true,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/question-bank/list?scope=institution&approval=approved');

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $institutionQuestion->id,
            'question_text' => 'Institution-only question',
        ]);
        $response->assertJsonMissing([
            'id' => $nationalQuestion->id,
            'question_text' => 'National-only question',
        ]);
    }
}
