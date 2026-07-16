<?php

namespace Tests\Unit;

use App\Models\Assignment;
use App\Models\Organization;
use App\Models\Resident;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\User;
use App\Services\AssignmentReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssignmentReadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_staff_index_payload(): void
    {
        $service = app(AssignmentReadService::class);

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $creator = User::factory()->create();

        Assignment::create([
            'organization_id' => $organization->id,
            'title' => 'Case Report',
            'description' => 'Desc',
            'instructions' => 'Instructions',
            'assignment_type' => 'case_report',
            'target_year_levels' => ['First Year'],
            'max_score' => 100,
            'due_date' => now()->addDay(),
            'allow_late_submission' => false,
            'late_penalty_percent' => 0,
            'allow_resubmission' => false,
            'max_submissions' => 1,
            'allowed_file_types' => ['pdf'],
            'max_file_size_mb' => 10,
            'max_files' => 1,
            'is_published' => true,
            'created_by' => $creator->id,
        ]);

        $payload = $service->indexPayload($user);

        $this->assertFalse($payload['isNationalOrg']);
        $this->assertSame('Case Report', $payload['assignments']->first()['title']);
    }

    public function test_it_builds_resident_assignments_payload(): void
    {
        $service = app(AssignmentReadService::class);

        $organization = Organization::create([
            'name' => 'Beta Chapter',
            'slug' => 'beta-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        Resident::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'year_level' => 'First Year',
        ]);
        $creator = User::factory()->create();
        $assignment = Assignment::create([
            'organization_id' => $organization->id,
            'title' => 'Journal Review',
            'description' => 'Desc',
            'instructions' => 'Instructions',
            'assignment_type' => 'journal_review',
            'target_year_levels' => ['First Year'],
            'max_score' => 100,
            'due_date' => now()->addDay(),
            'allow_late_submission' => false,
            'late_penalty_percent' => 0,
            'allow_resubmission' => true,
            'max_submissions' => 2,
            'allowed_file_types' => ['pdf'],
            'max_file_size_mb' => 10,
            'max_files' => 2,
            'is_published' => true,
            'created_by' => $creator->id,
        ]);
        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'year_level' => 'First Year',
            'submitted_at' => now(),
            'max_score' => 100,
            'status' => 'submitted',
            'submission_number' => 1,
        ]);
        SubmissionFile::create([
            'submission_id' => $submission->id,
            'file_name' => 'file.pdf',
            'original_name' => 'file.pdf',
            'file_path' => 'submissions/file.pdf',
            'file_type' => 'pdf',
            'file_size' => 100,
            'mime_type' => 'application/pdf',
        ]);

        $payload = $service->myAssignmentsPayload($user);

        $this->assertSame('Journal Review', $payload['assignments']->first()['title']);
        $this->assertNotNull($payload['assignments']->first()['submission']);
    }

    public function test_all_organizations_context_includes_other_organization_assignments_for_system_admin(): void
    {
        $service = app(AssignmentReadService::class);

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $otherOrganization = Organization::create([
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
        $user->organizations()->attach($otherOrganization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        Role::create([
            'name' => 'System Admin',
            'guard_name' => 'web',
        ]);
        $user->assignRole('System Admin');

        $creator = User::factory()->create();

        Assignment::create([
            'organization_id' => $otherOrganization->id,
            'title' => 'Cross Org Assignment',
            'description' => 'Desc',
            'instructions' => 'Instructions',
            'assignment_type' => 'case_report',
            'target_year_levels' => ['First Year'],
            'max_score' => 100,
            'due_date' => now()->addDay(),
            'allow_late_submission' => false,
            'late_penalty_percent' => 0,
            'allow_resubmission' => false,
            'max_submissions' => 1,
            'allowed_file_types' => ['pdf'],
            'max_file_size_mb' => 10,
            'max_files' => 1,
            'is_published' => true,
            'created_by' => $creator->id,
        ]);

        request()->query->set('org', 'all-organizations');

        $payload = $service->indexPayload($user);

        $this->assertTrue($payload['isAllOrganizationsContext']);
        $this->assertSame('Cross Org Assignment', $payload['assignments']->first()['title']);
    }
}
