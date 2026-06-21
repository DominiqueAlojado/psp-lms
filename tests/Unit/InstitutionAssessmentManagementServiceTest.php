<?php

namespace Tests\Unit;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Organization;
use App\Models\User;
use App\Services\InstitutionAssessmentManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionAssessmentManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_allows_create_for_non_national_context(): void
    {
        $service = app(InstitutionAssessmentManagementService::class);

        $organization = Organization::factory()->create([
            'name' => 'Institution Org',
            'slug' => 'institution-org',
            'type' => 'institution',
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $user->setRelation('currentOrganization', $organization);

        $this->assertTrue($service->canCreateFromCurrentOrganization($user));
    }

    public function test_it_blocks_create_for_national_context(): void
    {
        $service = app(InstitutionAssessmentManagementService::class);

        $organization = Organization::factory()->create([
            'name' => 'National Org',
            'slug' => 'national-org',
            'type' => 'national',
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $user->setRelation('currentOrganization', $organization);

        $this->assertFalse($service->canCreateFromCurrentOrganization($user));
    }

    public function test_it_creates_assessment_with_expected_defaults(): void
    {
        $service = app(InstitutionAssessmentManagementService::class);

        $organization = Organization::factory()->create([
            'name' => 'Create Org',
            'slug' => 'create-org',
            'type' => 'institution',
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $assessment = $service->create($user, [
            'title' => 'Managed Assessment',
            'description' => 'Managed description',
            'exam_category' => 'Midterm Exam',
            'duration_minutes' => 60,
            'passing_score' => 40,
            'randomize_questions' => true,
            'randomize_choices' => false,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => false,
        ]);

        $this->assertSame($organization->id, $assessment->organization_id);
        $this->assertSame($user->id, $assessment->created_by);
        $this->assertSame('Managed Assessment', $assessment->title);
        $this->assertSame(0, $assessment->total_points);
        $this->assertSame(40, $assessment->passing_score);
    }

    public function test_it_forces_in_service_category_for_national_org_assessment(): void
    {
        $service = app(InstitutionAssessmentManagementService::class);

        $organization = Organization::factory()->create([
            'name' => 'In-Service Exams',
            'slug' => 'in-service-exams',
            'type' => 'national',
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $assessment = InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'Managed National Assessment',
            'description' => null,
            'exam_category' => null,
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
        $assessment->setRelation('organization', $organization);

        $service->update($assessment, [
            'title' => 'Updated National Assessment',
            'description' => 'Updated description',
            'exam_category' => 'Midterm Exam',
            'duration_minutes' => 90,
            'passing_score' => 50,
            'randomize_questions' => true,
            'randomize_choices' => true,
            'show_results_immediately' => false,
            'allow_review' => false,
            'is_published' => true,
            'available_from' => now(),
            'available_until' => now()->addDay(),
        ]);

        $assessment->refresh();

        $this->assertSame('Updated National Assessment', $assessment->title);
        $this->assertSame('In-service', $assessment->exam_category);
    }

    public function test_it_allows_delete_when_assessment_has_no_attempts(): void
    {
        $service = app(InstitutionAssessmentManagementService::class);

        $organization = Organization::factory()->create([
            'name' => 'Delete Org',
            'slug' => 'delete-org',
            'type' => 'institution',
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $assessment = InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'Deletable Assessment',
            'description' => null,
            'exam_category' => null,
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

        $this->assertTrue($service->canDelete($assessment));
        $this->assertTrue($service->delete($assessment));
        $this->assertSoftDeleted('institution_assessments', [
            'id' => $assessment->id,
        ]);
    }
}
