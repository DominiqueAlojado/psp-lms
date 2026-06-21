<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\QuestionBank;
use App\Models\QuestionBankChoice;
use App\Models\Topic;
use App\Models\User;
use App\Services\QuestionBankManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuestionBankManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_question_with_choices_and_statistics(): void
    {
        Storage::fake('public');

        $service = app(QuestionBankManagementService::class);

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $topic = Topic::create([
            'name' => 'Anatomy',
            'slug' => 'anatomy',
            'organization_id' => $organization->id,
        ]);
        $image = UploadedFile::fake()->image('question.png');

        $question = $service->create($user, [
            'topic_id' => $topic->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'What is anatomy?',
            'points' => 1,
            'explanation' => 'Explanation',
            'difficulty_level' => 'easy',
            'choices' => [
                ['choice_text' => 'Choice A', 'is_correct' => true],
                ['choice_text' => 'Choice B', 'is_correct' => false],
            ],
        ], $image);

        $this->assertSame('institution', $question->owner_type);
        $this->assertCount(2, $question->choices);
        $this->assertNotNull($question->statistics);
        Storage::disk('public')->assertExists($question->image_path);
    }

    public function test_it_updates_a_question_and_replaces_choices(): void
    {
        Storage::fake('public');

        $service = app(QuestionBankManagementService::class);

        $organization = Organization::create([
            'name' => 'Beta Chapter',
            'slug' => 'beta-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $question = QuestionBank::create([
            'organization_id' => $organization->id,
            'owner_type' => 'institution',
            'created_by' => $user->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Old question',
            'points' => 1,
            'difficulty_level' => 'easy',
        ]);
        QuestionBankChoice::create([
            'question_id' => $question->id,
            'choice_text' => 'Old choice',
            'is_correct' => true,
            'order' => 0,
        ]);

        $result = $service->update($question, [
            'topic_id' => null,
            'question_type' => 'multiple_choice',
            'question_text' => 'New question',
            'points' => 2,
            'explanation' => 'Updated',
            'difficulty_level' => 'medium',
            'choices' => [
                ['choice_text' => 'New A', 'is_correct' => true],
                ['choice_text' => 'New B', 'is_correct' => false],
            ],
        ]);

        $question->refresh();

        $this->assertSame('New question', $question->question_text);
        $this->assertCount(2, $question->choices()->get());
        $this->assertFalse($result['imageChanged']);
    }

    public function test_it_approves_and_checks_access(): void
    {
        $service = app(QuestionBankManagementService::class);

        $organization = Organization::create([
            'name' => 'Home Chapter',
            'slug' => 'home-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $otherOrganization = Organization::create([
            'name' => 'Other Chapter',
            'slug' => 'other-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $question = QuestionBank::create([
            'organization_id' => $organization->id,
            'owner_type' => 'institution',
            'created_by' => $user->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Question',
            'points' => 1,
        ]);
        $otherQuestion = QuestionBank::create([
            'organization_id' => $otherOrganization->id,
            'owner_type' => 'institution',
            'created_by' => $user->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Other Question',
            'points' => 1,
        ]);

        $this->assertTrue($service->approve($question, $user->id));
        $this->assertTrue($question->fresh()->is_approved);
        $this->assertTrue($service->canAccess($user, $question));
        $this->assertFalse($service->canAccess($user, $otherQuestion));
    }
}
