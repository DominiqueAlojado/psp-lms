<?php

namespace Tests\Unit;

use App\Actions\InstitutionExams\ImportInstitutionAssessmentQuestionsAction;
use App\Models\Institution\InstitutionAssessment;
use App\Models\Organization;
use App\Models\User;
use App\Services\InstitutionAssessmentImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class InstitutionAssessmentImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_success_message_when_import_has_no_errors(): void
    {
        $organization = Organization::factory()->create([
            'name' => 'Import Service Org',
            'slug' => 'import-service-org',
            'type' => 'institution',
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $assessment = InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'Import Service Exam',
            'duration_minutes' => 60,
            'total_points' => 0,
            'passing_score' => 1,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => false,
            'created_by' => $user->id,
        ]);

        $action = Mockery::mock(ImportInstitutionAssessmentQuestionsAction::class);
        $action->shouldReceive('execute')
            ->once()
            ->andReturn([
                'success_count' => 3,
                'errors' => [],
            ]);

        $service = new InstitutionAssessmentImportService($action);

        $result = $service->importQuestions(
            $assessment,
            UploadedFile::fake()->create('questions.xlsx'),
            $user,
        );

        $this->assertSame([
            'status' => 'success',
            'message' => 'Successfully imported 3 questions!',
        ], $result);
    }

    public function test_it_returns_warning_message_when_import_has_errors(): void
    {
        $organization = Organization::factory()->create([
            'name' => 'Import Warning Org',
            'slug' => 'import-warning-org',
            'type' => 'institution',
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $assessment = InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'Import Warning Exam',
            'duration_minutes' => 60,
            'total_points' => 0,
            'passing_score' => 1,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => false,
            'created_by' => $user->id,
        ]);

        $action = Mockery::mock(ImportInstitutionAssessmentQuestionsAction::class);
        $action->shouldReceive('execute')
            ->once()
            ->andReturn([
                'success_count' => 2,
                'errors' => [
                    'Row 2: Missing required fields',
                    'Row 3: Invalid type',
                    'Row 4: At least 2 choices required',
                    'Row 5: Other issue',
                ],
            ]);

        $service = new InstitutionAssessmentImportService($action);

        $result = $service->importQuestions(
            $assessment,
            UploadedFile::fake()->create('questions.xlsx'),
            $user,
        );

        $this->assertSame('warning', $result['status']);
        $this->assertSame(
            'Imported 2 questions with 4 errors: Row 2: Missing required fields; Row 3: Invalid type; Row 4: At least 2 choices required... and 1 more errors.',
            $result['message']
        );
    }

    public function test_it_formats_import_failure_message(): void
    {
        $service = new InstitutionAssessmentImportService(
            Mockery::mock(ImportInstitutionAssessmentQuestionsAction::class)
        );

        $result = $service->formatImportFailure(new \RuntimeException('Spreadsheet is corrupted'));

        $this->assertSame([
            'file' => 'Import failed: Spreadsheet is corrupted',
        ], $result);
    }
}
