<?php

namespace Tests\Unit;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionAttempt;
use App\Models\Institution\InstitutionQuestion;
use App\Models\Institution\InstitutionQuestionChoice;
use App\Models\Organization;
use App\Models\User;
use App\Services\ResidentExamReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ResidentExamReadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_take_payload_for_institution_exam(): void
    {
        $service = app(ResidentExamReadService::class);

        $organization = Organization::create([
            'name' => 'Alpha Hospital',
            'slug' => 'alpha-hospital',
            'type' => 'institution',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $user->organizations()->attach($organization->id, ['joined_at' => now(), 'is_active' => true]);

        $assessment = InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'Quiz',
            'description' => 'Desc',
            'exam_category' => 'Quiz',
            'duration_minutes' => 30,
            'total_points' => 1,
            'passing_score' => 1,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'is_published' => true,
            'created_by' => $user->id,
        ]);
        $question = InstitutionQuestion::create([
            'assessment_id' => $assessment->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'What is 2 + 2?',
            'points' => 1,
            'order' => 1,
        ]);
        InstitutionQuestionChoice::create([
            'question_id' => $question->id,
            'choice_text' => '4',
            'is_correct' => true,
            'order' => 1,
        ]);

        $request = Request::create('/exams/institution/' . $assessment->id . '/take', 'GET');
        $request->setLaravelSession(app('session')->driver());
        $request->session()->start();
        $request->setUserResolver(fn () => $user);

        $payload = $service->takePayload($request, 'institution', $assessment->id);

        $this->assertSame('Quiz', $payload['exam']['title']);
        $this->assertSame('institution', $payload['exam']['type']);
        $this->assertCount(1, $payload['exam']['questions']);
    }
}
