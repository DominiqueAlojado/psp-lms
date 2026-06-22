<?php

namespace Tests\Feature;

use App\Http\Middleware\SetOrganizationFromUrl;
use App\Models\Assignment;
use App\Models\Organization;
use App\Models\Resident;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AssignmentManagementFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_and_update_assignment(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        Permission::create(['name' => 'create-assignments', 'guard_name' => 'web']);
        Permission::create(['name' => 'edit-assignments', 'guard_name' => 'web']);

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
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
        $user->givePermissionTo('create-assignments');
        $user->givePermissionTo('edit-assignments');

        $createResponse = $this->actingAs($user)
            ->from(route('assignments.index'))
            ->post(route('assignments.store'), [
                'title' => 'Case Report',
                'description' => 'Desc',
                'instructions' => 'Instructions',
                'assignment_type' => 'case_report',
                'target_year_levels' => ['First Year'],
                'max_score' => 100,
                'due_date' => now()->addDay()->format('Y-m-d H:i:s'),
                'allow_late_submission' => false,
                'late_penalty_percent' => 0,
                'allow_resubmission' => false,
                'max_submissions' => 1,
                'allowed_file_types' => ['pdf'],
                'max_file_size_mb' => 10,
                'max_files' => 1,
                'is_published' => true,
            ]);

        $createResponse->assertSessionHasNoErrors()
            ->assertRedirect(route('assignments.index'));

        $assignment = Assignment::where('title', 'Case Report')->first();

        $updateResponse = $this->actingAs($user)
            ->from(route('assignments.index'))
            ->patch(route('assignments.update', $assignment), [
                'title' => 'Updated Report',
                'description' => 'Updated',
                'instructions' => 'Updated instructions',
                'assignment_type' => 'other',
                'target_year_levels' => ['Second Year'],
                'max_score' => 50,
                'due_date' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'allow_late_submission' => true,
                'late_submission_until' => now()->addDays(3)->format('Y-m-d H:i:s'),
                'late_penalty_percent' => 10,
                'allow_resubmission' => true,
                'max_submissions' => 2,
                'allowed_file_types' => ['docx'],
                'max_file_size_mb' => 20,
                'max_files' => 2,
                'is_published' => false,
            ]);

        $updateResponse->assertSessionHasNoErrors();
        $this->assertSame('Updated Report', $assignment->fresh()->title);
    }

    public function test_resident_can_submit_and_staff_can_grade_assignment(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        Storage::fake('public');

        Permission::create(['name' => 'grade-assignments', 'guard_name' => 'web']);

        $organization = Organization::create([
            'name' => 'Beta Chapter',
            'slug' => 'beta-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $staff = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $staff->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);
        $staff->givePermissionTo('grade-assignments');

        $residentUser = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $residentUser->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);
        Resident::factory()->create([
            'user_id' => $residentUser->id,
            'organization_id' => $organization->id,
            'year_level' => 'First Year',
        ]);

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
            'allow_resubmission' => false,
            'max_submissions' => 1,
            'allowed_file_types' => ['pdf'],
            'max_file_size_mb' => 10,
            'max_files' => 1,
            'is_published' => true,
            'created_by' => $staff->id,
        ]);

        $submitResponse = $this->actingAs($residentUser)
            ->from(route('assignments.my-assignments'))
            ->post(route('assignments.submit.store', $assignment), [
                'submission_text' => 'My submission',
                'files' => [UploadedFile::fake()->create('work.pdf', 100, 'application/pdf')],
            ]);

        $submitResponse->assertSessionHasNoErrors()
            ->assertRedirect(route('assignments.my-assignments'));

        $submission = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $residentUser->id)
            ->first();

        $gradeResponse = $this->actingAs($staff)
            ->from(route('submissions.grade', $submission))
            ->post(route('submissions.save-grade', $submission), [
                'score' => 90,
                'grader_feedback' => 'Strong work',
            ]);

        $gradeResponse->assertSessionHasNoErrors();
        $this->assertSame('graded', $submission->fresh()->status);
    }
}
