<?php

namespace Tests\Feature;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InstitutionExamDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_duplicate_an_exam(): void
    {
        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \App\Http\Middleware\SetOrganizationFromUrl::class,
        ]);

        $organization = Organization::factory()->create([
            'name' => 'Dup Org',
            'slug' => 'dup-org',
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $user->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        Permission::firstOrCreate(['name' => 'create-assessments', 'guard_name' => 'web']);
        $user->givePermissionTo('create-assessments');

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

        $response = $this->actingAs($user)->post(route('assessments.duplicate', $assessment));

        $duplicate = InstitutionAssessment::where('title', 'Original Exam (Copy)')->first();

        $this->assertNotNull($duplicate);
        $response->assertRedirect(route('institution-exams.edit', $duplicate));

        $this->assertFalse($duplicate->is_published);
        $this->assertNull($duplicate->available_from);
        $this->assertNull($duplicate->available_until);
        $this->assertSame(5, $duplicate->total_points);

        $this->assertCount(1, $duplicate->questions);
        $dupQuestion = $duplicate->questions->first();
        $this->assertSame('What is 2 + 3?', $dupQuestion->question_text);
        $this->assertCount(2, $dupQuestion->choices);
        $this->assertTrue($dupQuestion->choices->first()->is_correct);
    }
}
