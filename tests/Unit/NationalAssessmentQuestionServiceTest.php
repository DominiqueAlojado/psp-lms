<?php

namespace Tests\Unit;

use App\Models\National\NationalAssessment;
use App\Models\QuestionBank;
use App\Models\User;
use App\Services\NationalAssessmentQuestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NationalAssessmentQuestionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_questions_and_updates_total_points(): void
    {
        $service = app(NationalAssessmentQuestionService::class);
        $user = User::factory()->create();

        $assessment = NationalAssessment::create([
            'title' => 'Question Service Exam',
            'description' => null,
            'exam_year' => 2026,
            'exam_period' => 'Q1',
            'category' => 'anatomic-pathology-theoretical',
            'duration_minutes' => 60,
            'total_points' => 0,
            'passing_score' => 5,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'show_results_immediately' => false,
            'allow_review' => false,
            'is_published' => false,
            'national_ranking_enabled' => true,
            'institution_comparison_enabled' => true,
            'created_by' => $user->id,
        ]);

        $service->storeQuestions($assessment, [[
            'question_type' => 'multiple_choice',
            'question_text' => 'Stored question',
            'points' => 3,
            'choices' => [
                ['choice_text' => 'A', 'is_correct' => true],
                ['choice_text' => 'B', 'is_correct' => false],
            ],
        ]]);

        $assessment->refresh();

        $this->assertSame(3, $assessment->total_points);
        $this->assertCount(1, $assessment->questions);
    }

    public function test_it_adds_bank_questions_and_skips_duplicates(): void
    {
        $service = app(NationalAssessmentQuestionService::class);
        $user = User::factory()->create();

        $assessment = NationalAssessment::create([
            'title' => 'Bank Question Exam',
            'description' => null,
            'exam_year' => 2026,
            'exam_period' => 'Q2',
            'category' => 'clinical-pathology-theoretical',
            'duration_minutes' => 60,
            'total_points' => 0,
            'passing_score' => 5,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'show_results_immediately' => false,
            'allow_review' => false,
            'is_published' => false,
            'national_ranking_enabled' => true,
            'institution_comparison_enabled' => true,
            'created_by' => $user->id,
        ]);

        $assessment->questions()->create([
            'question_type' => 'multiple_choice',
            'question_text' => 'Duplicate text',
            'points' => 2,
            'order' => 1,
        ]);

        $bankQuestion = QuestionBank::create([
            'organization_id' => null,
            'owner_type' => 'national',
            'created_by' => $user->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Duplicate text',
            'points' => 2,
            'is_approved' => true,
        ]);

        $bankQuestion->choices()->create([
            'choice_text' => 'Correct',
            'is_correct' => true,
            'order' => 1,
        ]);

        $result = $service->addFromBank($assessment, [$bankQuestion->id]);

        $this->assertSame(0, $result['added_count']);
        $this->assertSame(1, $result['skipped_count']);
    }
}
