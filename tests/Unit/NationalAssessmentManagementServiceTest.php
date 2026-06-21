<?php

namespace Tests\Unit;

use App\Models\National\NationalAssessment;
use App\Models\User;
use App\Services\NationalAssessmentManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NationalAssessmentManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_assessment_with_defaults(): void
    {
        $service = app(NationalAssessmentManagementService::class);
        $user = User::factory()->create();

        $assessment = $service->create($user, [
            'title' => 'Created National Exam',
            'description' => 'Description',
            'exam_year' => 2026,
            'exam_period' => 'Q3',
            'category' => 'anatomic-pathology-projection',
            'duration_minutes' => 50,
            'passing_score' => 30,
        ]);

        $this->assertSame('Created National Exam', $assessment->title);
        $this->assertSame(0, $assessment->total_points);
        $this->assertFalse($assessment->is_published);
        $this->assertTrue($assessment->national_ranking_enabled);
    }

    public function test_it_updates_and_can_delete_assessment_without_attempts(): void
    {
        $service = app(NationalAssessmentManagementService::class);
        $user = User::factory()->create();

        $assessment = NationalAssessment::create([
            'title' => 'Original National Exam',
            'description' => null,
            'exam_year' => 2026,
            'exam_period' => 'Q1',
            'category' => 'clinical-pathology-projection',
            'duration_minutes' => 60,
            'total_points' => 0,
            'passing_score' => 10,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'show_results_immediately' => false,
            'allow_review' => false,
            'is_published' => false,
            'national_ranking_enabled' => true,
            'institution_comparison_enabled' => true,
            'created_by' => $user->id,
        ]);

        $service->update($assessment, [
            'title' => 'Updated National Exam',
            'description' => 'Updated',
            'passing_score' => 20,
            'duration_minutes' => 70,
        ]);

        $assessment->refresh();

        $this->assertSame('Updated National Exam', $assessment->title);
        $this->assertTrue($service->canDelete($assessment));
        $this->assertTrue($service->delete($assessment));
        $this->assertSoftDeleted('national_assessments', ['id' => $assessment->id]);
    }
}
