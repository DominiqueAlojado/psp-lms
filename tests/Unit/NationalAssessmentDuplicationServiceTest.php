<?php

namespace Tests\Unit;

use App\Models\National\NationalAssessment;
use App\Models\User;
use App\Services\NationalAssessmentDuplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NationalAssessmentDuplicationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_duplicates_assessment_and_questions(): void
    {
        $service = app(NationalAssessmentDuplicationService::class);
        $user = User::factory()->create();

        $assessment = NationalAssessment::create([
            'title' => 'Original National Exam',
            'description' => 'Original',
            'exam_year' => 2026,
            'exam_period' => 'Q4',
            'category' => 'clinical-pathology-theoretical',
            'duration_minutes' => 60,
            'total_points' => 5,
            'passing_score' => 3,
            'randomize_questions' => true,
            'randomize_choices' => true,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => true,
            'national_ranking_enabled' => true,
            'institution_comparison_enabled' => true,
            'scheduled_date' => now(),
            'results_release_date' => now()->addDay(),
            'created_by' => $user->id,
        ]);

        $question = $assessment->questions()->create([
            'question_type' => 'multiple_choice',
            'question_text' => 'Original question',
            'points' => 5,
            'order' => 1,
        ]);

        $question->choices()->create([
            'choice_text' => 'Correct',
            'is_correct' => true,
            'order' => 1,
        ]);

        $duplicate = $service->duplicate($assessment, $user->id);

        $this->assertSame('Original National Exam (Copy)', $duplicate->title);
        $this->assertFalse($duplicate->is_published);
        $this->assertNull($duplicate->scheduled_date);
        $this->assertSame(5, $duplicate->fresh()->total_points);
        $this->assertCount(1, $duplicate->questions);
    }
}
