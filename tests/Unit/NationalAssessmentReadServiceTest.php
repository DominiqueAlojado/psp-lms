<?php

namespace Tests\Unit;

use App\Models\National\NationalAssessment;
use App\Models\National\NationalQuestion;
use App\Models\National\NationalQuestionChoice;
use App\Models\Topic;
use App\Models\User;
use App\Services\NationalAssessmentReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NationalAssessmentReadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_index_payload(): void
    {
        $service = app(NationalAssessmentReadService::class);
        $user = User::factory()->create();

        NationalAssessment::create([
            'title' => 'National Index Exam',
            'description' => 'Index description',
            'exam_year' => 2026,
            'exam_period' => 'Q1',
            'category' => 'anatomic-pathology-theoretical',
            'duration_minutes' => 60,
            'total_points' => 40,
            'passing_score' => 24,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => true,
            'national_ranking_enabled' => true,
            'institution_comparison_enabled' => true,
            'scheduled_date' => now()->subHour(),
            'results_release_date' => now()->addHour(),
            'created_by' => $user->id,
        ]);

        $result = $service->list([]);
        $item = $result->items()[0];

        $this->assertSame('National Index Exam', $item['title']);
        $this->assertSame(2026, $item['exam_year']);
        $this->assertTrue($item['is_available']);
        $this->assertTrue($item['can_view_results']);
    }

    public function test_it_builds_edit_payload_from_normalized_topic_reference(): void
    {
        $service = app(NationalAssessmentReadService::class);
        $user = User::factory()->create();
        $topic = Topic::create(['name' => 'Toxicology']);

        $assessment = NationalAssessment::create([
            'title' => 'National Edit Exam',
            'description' => null,
            'exam_year' => 2026,
            'exam_period' => 'Q2',
            'category' => 'clinical-pathology-theoretical',
            'duration_minutes' => 45,
            'total_points' => 2,
            'passing_score' => 1,
            'randomize_questions' => true,
            'randomize_choices' => true,
            'show_results_immediately' => false,
            'allow_review' => false,
            'is_published' => false,
            'national_ranking_enabled' => true,
            'institution_comparison_enabled' => true,
            'scheduled_date' => now(),
            'results_release_date' => now()->addDay(),
            'created_by' => $user->id,
        ]);

        $question = NationalQuestion::create([
            'assessment_id' => $assessment->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Question text',
            'points' => 2,
            'topic_id' => $topic->id,
            'topic' => $topic->name,
            'image_path' => 'national-questions/example.png',
            'order' => 1,
        ]);

        NationalQuestionChoice::create([
            'question_id' => $question->id,
            'choice_text' => 'Answer',
            'is_correct' => true,
            'order' => 1,
        ]);

        $payload = $service->editPayload($assessment);

        $this->assertSame('National Edit Exam', $payload['assessment']['title']);
        $this->assertSame($topic->name, $payload['assessment']['questions'][0]['topic']);
        $this->assertSame($topic->id, $payload['assessment']['questions'][0]['topic_id']);
        $this->assertStringContainsString('national-questions/example.png', $payload['assessment']['questions'][0]['image_url']);
    }
}
