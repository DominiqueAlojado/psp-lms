<?php

namespace Tests\Unit;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Organization;
use App\Models\User;
use App\Services\InstitutionAssessmentDuplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionAssessmentDuplicationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_duplicates_assessment_questions_and_resets_publication_fields(): void
    {
        $service = app(InstitutionAssessmentDuplicationService::class);

        $organization = Organization::factory()->create([
            'name' => 'Dup Service Org',
            'slug' => 'dup-service-org',
            'type' => 'institution',
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $assessment = InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'Original Exam',
            'description' => 'Original description',
            'exam_category' => 'Midterm Exam',
            'duration_minutes' => 60,
            'total_points' => 5,
            'passing_score' => 3,
            'randomize_questions' => true,
            'randomize_choices' => true,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => true,
            'available_from' => now(),
            'available_until' => now()->addDay(),
            'created_by' => $user->id,
        ]);

        $question = $assessment->questions()->create([
            'question_type' => 'multiple_choice',
            'question_text' => 'What is 2 + 3?',
            'points' => 5,
            'order' => 1,
        ]);

        $question->choices()->createMany([
            ['choice_text' => '5', 'is_correct' => true, 'order' => 0],
            ['choice_text' => '4', 'is_correct' => false, 'order' => 1],
        ]);

        $duplicate = $service->duplicate($assessment, $user->id);

        $this->assertSame('Original Exam (Copy)', $duplicate->title);
        $this->assertFalse($duplicate->is_published);
        $this->assertNull($duplicate->available_from);
        $this->assertNull($duplicate->available_until);
        $this->assertSame(5, $duplicate->total_points);
        $this->assertCount(1, $duplicate->questions);
        $this->assertCount(2, $duplicate->questions->first()->choices);
    }

    public function test_it_generates_next_available_duplicate_title(): void
    {
        $service = app(InstitutionAssessmentDuplicationService::class);

        $organization = Organization::factory()->create([
            'name' => 'Dup Sequence Org',
            'slug' => 'dup-sequence-org',
            'type' => 'institution',
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $assessment = InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'Original Exam',
            'duration_minutes' => 60,
            'total_points' => 1,
            'passing_score' => 1,
            'randomize_questions' => false,
            'randomize_choices' => false,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => false,
            'created_by' => $user->id,
        ]);

        InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'Original Exam (Copy)',
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

        $duplicate = $service->duplicate($assessment, $user->id);

        $this->assertSame('Original Exam (Copy 2)', $duplicate->title);
    }
}
