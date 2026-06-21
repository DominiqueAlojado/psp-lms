<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\QuestionBank;
use App\Models\QuestionBankChoice;
use App\Models\QuestionBankStatistic;
use App\Models\Topic;
use App\Models\User;
use App\Services\QuestionBankReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionBankReadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_index_payload_for_institution_scope(): void
    {
        $service = app(QuestionBankReadService::class);

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
        $question = QuestionBank::create([
            'organization_id' => $organization->id,
            'owner_type' => 'institution',
            'topic_id' => $topic->id,
            'created_by' => $user->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'Sample question',
            'points' => 1,
            'difficulty_level' => 'easy',
            'is_approved' => true,
        ]);
        QuestionBankChoice::create([
            'question_id' => $question->id,
            'choice_text' => 'A',
            'is_correct' => true,
            'order' => 0,
        ]);
        QuestionBankStatistic::create([
            'question_id' => $question->id,
            'scope' => 'institution',
            'institution_id' => $organization->id,
        ]);

        $payload = $service->indexPayload($user, []);

        $this->assertSame('Sample question', $payload['questions']->items()[0]['question_text']);
    }

    public function test_it_builds_list_payload_for_scope_override(): void
    {
        $service = app(QuestionBankReadService::class);

        $organization = Organization::create([
            'name' => 'National Org',
            'slug' => 'national-org',
            'type' => 'national',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $question = QuestionBank::create([
            'organization_id' => $organization->id,
            'owner_type' => 'national',
            'created_by' => $user->id,
            'question_type' => 'multiple_choice',
            'question_text' => 'National question',
            'points' => 1,
            'difficulty_level' => 'easy',
            'is_approved' => true,
        ]);
        QuestionBankChoice::create([
            'question_id' => $question->id,
            'choice_text' => 'A',
            'is_correct' => true,
            'order' => 0,
        ]);

        $payload = $service->listPayload($user, [], 'national');

        $this->assertSame('National question', $payload['data']->first()->question_text);
    }
}
