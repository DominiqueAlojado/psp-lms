<?php

namespace Tests\Unit;

use App\Models\Assignment;
use App\Models\Organization;
use App\Models\User;
use App\Services\AssignmentManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_updates_and_deletes_assignment(): void
    {
        $service = app(AssignmentManagementService::class);

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $assignment = $service->create($user, [
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
        ]);

        $this->assertSame($organization->id, $assignment->organization_id);

        $this->assertTrue($service->update($assignment, [
            'title' => 'Updated Report',
            'description' => 'Updated',
            'instructions' => 'Updated instructions',
            'assignment_type' => 'other',
            'target_year_levels' => ['Second Year'],
            'max_score' => 50,
            'due_date' => now()->addDays(2),
            'allow_late_submission' => true,
            'late_submission_until' => now()->addDays(3),
            'late_penalty_percent' => 10,
            'allow_resubmission' => true,
            'max_submissions' => 2,
            'allowed_file_types' => ['docx'],
            'max_file_size_mb' => 20,
            'max_files' => 2,
            'is_published' => false,
        ]));

        $this->assertSame('Updated Report', $assignment->fresh()->title);
        $this->assertTrue($service->delete($assignment));
        $this->assertSoftDeleted('assignments', ['id' => $assignment->id]);
    }
}
