<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InstitutionAssessmentStoreFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_national_org_user_is_redirected_to_inservice_create_when_using_institution_store(): void
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

        $response = $this->actingAs($user)->post(route('assessments.store'), [
            'title' => 'Wrong Flow Exam',
            'passing_score' => 40,
        ]);

        $response->assertRedirect(route('inservice-exams.create'));
        $response->assertSessionHas('error', 'Use the In-Service Exams flow for national assessments.');
    }
}
