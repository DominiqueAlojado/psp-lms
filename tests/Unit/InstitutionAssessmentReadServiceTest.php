<?php

namespace Tests\Unit;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionQuestion;
use App\Models\Institution\InstitutionQuestionChoice;
use App\Models\Organization;
use App\Models\User;
use App\Services\InstitutionAssessmentReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionAssessmentReadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_redirects_national_users_to_inservice_views(): void
    {
        $service = app(InstitutionAssessmentReadService::class);

        $organization = Organization::factory()->create([
            'name' => 'National Org',
            'slug' => 'national-org',
            'type' => 'national',
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $user->setRelation('currentOrganization', $organization);

        $this->assertTrue($service->shouldRedirectToInservice($user));
    }

    public function test_it_returns_published_exam_list_payload(): void
    {
        $service = app(InstitutionAssessmentReadService::class);

        [$organization, $user] = $this->makeOrganizationAndUser('institution');

        $assessment = InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'Published Assessment',
            'description' => 'Assessment description',
            'exam_category' => 'Quiz',
            'duration_minutes' => 45,
            'total_points' => 20,
            'passing_score' => 12,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => true,
            'available_from' => now()->subHour(),
            'available_until' => now()->addHour(),
            'created_by' => $user->id,
        ]);

        $assessment->questions()->create([
            'question_type' => 'multiple_choice',
            'question_text' => 'Question 1',
            'points' => 5,
            'order' => 1,
        ]);

        $result = $service->listForPublication($user, true, []);
        $item = $result->items()[0];

        $this->assertSame('Published Assessment', $item['title']);
        $this->assertSame('Quiz', $item['exam_category']);
        $this->assertSame(1, $item['questions_count']);
        $this->assertSame(20, $item['total_points']);
        $this->assertSame(12, $item['passing_score']);
        $this->assertTrue($item['is_published']);
        $this->assertTrue($item['is_available']);
        $this->assertSame($user->name, $item['created_by']);
        $this->assertStringContainsString('T', $item['updated_at']);
    }

    public function test_it_builds_edit_payload_with_question_bank_scope(): void
    {
        $service = app(InstitutionAssessmentReadService::class);

        [$organization, $user] = $this->makeOrganizationAndUser('national');

        $assessment = InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'Editable Assessment',
            'description' => 'Editable description',
            'exam_category' => 'In-service',
            'duration_minutes' => 60,
            'total_points' => 10,
            'passing_score' => 6,
            'randomize_questions' => true,
            'randomize_choices' => true,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => false,
            'available_from' => now(),
            'available_until' => now()->addDay(),
            'created_by' => $user->id,
        ]);

        $question = InstitutionQuestion::create([
            'assessment_id' => $assessment->id,
            'topic_id' => null,
            'question_type' => 'multiple_choice',
            'question_text' => 'Editable question',
            'points' => 2,
            'explanation' => 'Because it is correct',
            'image_path' => 'questions/example.png',
            'order' => 1,
        ]);

        InstitutionQuestionChoice::create([
            'question_id' => $question->id,
            'choice_text' => 'Choice A',
            'is_correct' => true,
            'order' => 1,
        ]);

        $payload = $service->editPayload($assessment);

        $this->assertSame('national', $payload['questionBankScope']);
        $this->assertSame('Editable Assessment', $payload['assessment']['title']);
        $this->assertSame('Editable question', $payload['assessment']['questions'][0]['question_text']);
        $this->assertStringContainsString('questions/example.png', $payload['assessment']['questions'][0]['image_url']);
        $this->assertSame('Choice A', $payload['assessment']['questions'][0]['choices'][0]['choice_text']);
        $this->assertSame($user->name, $payload['assessment']['created_by']);
    }

    public function test_it_builds_show_payload(): void
    {
        $service = app(InstitutionAssessmentReadService::class);

        [$organization, $user] = $this->makeOrganizationAndUser('institution');

        $assessment = InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'Visible Assessment',
            'description' => 'Visible description',
            'exam_category' => 'Quiz',
            'duration_minutes' => 30,
            'total_points' => 5,
            'passing_score' => 3,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => true,
            'created_by' => $user->id,
        ]);

        $question = InstitutionQuestion::create([
            'assessment_id' => $assessment->id,
            'question_type' => 'true_false',
            'question_text' => 'Visible question',
            'points' => 1,
            'explanation' => 'Visible explanation',
            'order' => 1,
        ]);

        InstitutionQuestionChoice::create([
            'question_id' => $question->id,
            'choice_text' => 'True',
            'is_correct' => true,
            'order' => 1,
        ]);

        $payload = $service->showPayload($assessment);

        $this->assertSame('Visible Assessment', $payload['assessment']['title']);
        $this->assertSame('Visible question', $payload['assessment']['questions'][0]['question_text']);
        $this->assertSame('Visible explanation', $payload['assessment']['questions'][0]['explanation']);
        $this->assertTrue($payload['assessment']['questions'][0]['choices'][0]['is_correct']);
    }

    private function makeOrganizationAndUser(string $type): array
    {
        $organization = Organization::factory()->create([
            'name' => ucfirst($type) . ' Org',
            'slug' => $type . '-org',
            'type' => $type,
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        return [$organization, $user];
    }
}
