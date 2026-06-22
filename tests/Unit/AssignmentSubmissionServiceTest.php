<?php

namespace Tests\Unit;

use App\Models\Assignment;
use App\Models\Organization;
use App\Models\Resident;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\User;
use App\Services\AssignmentSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssignmentSubmissionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_submission_with_files_and_grades_it(): void
    {
        Storage::fake('public');

        $service = app(AssignmentSubmissionService::class);

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
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
        $grader = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $assignment = Assignment::create([
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
            'max_files' => 2,
            'is_published' => true,
            'created_by' => $grader->id,
        ]);

        $submission = $service->createSubmission($user, $assignment, [
            'submission_text' => 'My work',
        ], [UploadedFile::fake()->create('work.pdf', 100, 'application/pdf')]);

        $this->assertSame('submitted', $submission->status);
        $this->assertDatabaseCount('submission_files', 1);

        $service->gradeSubmission($grader, $submission, [
            'score' => 85,
            'grader_feedback' => 'Good work',
        ]);

        $this->assertSame('graded', $submission->fresh()->status);
        $this->assertSame('85.00', $submission->fresh()->score);
    }

    public function test_it_increments_submission_file_download_count(): void
    {
        $service = app(AssignmentSubmissionService::class);

        $organization = Organization::create([
            'name' => 'Beta Chapter',
            'slug' => 'beta-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $user = User::factory()->create();
        $assignment = Assignment::create([
            'organization_id' => $organization->id,
            'title' => 'Paper',
            'description' => 'Desc',
            'instructions' => 'Instructions',
            'assignment_type' => 'research_paper',
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
            'created_by' => $user->id,
        ]);
        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'submitted_at' => now(),
            'max_score' => 100,
            'status' => 'submitted',
            'submission_number' => 1,
        ]);
        $file = SubmissionFile::create([
            'submission_id' => $submission->id,
            'file_name' => 'paper.pdf',
            'original_name' => 'paper.pdf',
            'file_path' => 'submissions/paper.pdf',
            'file_type' => 'pdf',
            'file_size' => 100,
            'mime_type' => 'application/pdf',
            'download_count' => 0,
        ]);

        $service->incrementDownloadCount($file);

        $this->assertSame(1, $file->fresh()->download_count);
    }
}
